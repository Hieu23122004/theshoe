<?php
include '../../includes/auth.php';
include '../../includes/database.php';
include '../../includes/header_ad.php';
$message = '';
$hasError = false;
// Handle Delete
if (isset($_POST['delete'])) {
    $code_id = (int)$_POST['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM discount_codes WHERE code_id = ?");
        $stmt->bind_param("i", $code_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Discount code deleted successfully!</div>";
        } else {
            throw new Exception("Could not delete discount code");
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
// Handle Create/Update
if (isset($_POST['submit'])) {
    $code_id = isset($_POST['code_id']) ? (int)$_POST['code_id'] : null;
    $code = trim($_POST['code']);
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $min_order_amount = floatval($_POST['min_order_amount']);
    $valid_from = $_POST['valid_from'];
    $valid_until = $_POST['valid_until'];
    try {
        $conn->begin_transaction();
        // Validate inputs
        if (empty($code)) throw new Exception("Code cannot be empty");
        if ($discount_value <= 0) throw new Exception("Discount value must be greater than 0");
        // Validate discount value based on type
        if ($discount_type === 'percent') {
            if ($discount_value > 100) {
                throw new Exception("Percentage discount cannot exceed 100%");
            }
        } else {
            if ($discount_value < 35000) {
                throw new Exception("Fixed discount amount must be at least 35,000₫");
            }
        }
        if ($min_order_amount < 0) throw new Exception("Minimum order amount cannot be negative");
        if (strtotime($valid_until) <= strtotime($valid_from)) {
            throw new Exception("Valid until date must be after valid from date");
        }
        // Check for duplicate code
        $stmt = $conn->prepare("SELECT code_id FROM discount_codes WHERE code = ? AND (code_id != ? OR ? IS NULL)");
        $stmt->bind_param("sii", $code, $code_id, $code_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("This discount code already exists");
        }
        if ($code_id) {
            // Update
            $stmt = $conn->prepare("UPDATE discount_codes SET 
                code = ?, discount_type = ?, discount_value = ?, 
                max_uses = ?, min_order_amount = ?, 
                valid_from = ?, valid_until = ? 
                WHERE code_id = ?");
            $stmt->bind_param(
                "sssdsssi",
                $code,
                $discount_type,
                $discount_value,
                $max_uses,
                $min_order_amount,
                $valid_from,
                $valid_until,
                $code_id
            );
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO discount_codes 
                (code, discount_type, discount_value, max_uses, min_order_amount, valid_from, valid_until) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "sssdsss",
                $code,
                $discount_type,
                $discount_value,
                $max_uses,
                $min_order_amount,
                $valid_from,
                $valid_until
            );
        }
        if (!$stmt->execute()) {
            throw new Exception("Error saving discount code: " . $stmt->error);
        }
        $conn->commit();
        $message = "<div class='alert alert-success'>Discount saved success!</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $hasError = true;
    }
}
// Fetch all discount codes
$result = $conn->query("SELECT * FROM discount_codes ORDER BY created_at DESC");
$discount_codes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<style>
    .discount-value {
        font-weight: bold;
        color: #e74c3c;
    }

    .code-badge {
        font-family: monospace;
        font-size: 1.1em;
        letter-spacing: 1px;
    }

    .status-badge {
        font-size: 0.85em;
    }

    .table td {
        vertical-align: middle;
    }
</style>
<div class="container-fluid px-2" style="margin-top: 110px;">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var msg = <?= json_encode(strip_tags($message)) ?>;
        if (msg) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: msg.includes('success') ? 'success' : (msg.includes('error') || msg.includes('danger') ? 'error' : 'info'),
                title: msg,
                showConfirmButton: false,
                timer: 2000,
                background: '#fff',
                color: '#8c7e71',
                customClass: {popup: 'swal2-toast-custom'}
            });
        }
    });
    </script>
    <style>
    .swal2-toast-custom {
        border-radius: 0.75rem !important;
        box-shadow: 0 0.2rem 1.5rem 0 rgba(140,126,113,0.12) !important;
        font-size: 1.1rem;
    }
    </style>
    <div class="card mb-4">
        <div class="card-header" style="background-color: #8c7e71;">
            <strong>Add / Edit Discount Code</strong>
        </div>
        <div class="card-body">
            <form method="POST" id="discountForm">
                <input type="hidden" name="code_id" id="edit_id">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Discount Code</label>
                        <input type="text" name="code" id="code" class="form-control"
                            pattern="[A-Za-z0-9]+" title="Only letters and numbers allowed"
                            placeholder="Tên mã giảm giá" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Discount Type</label>
                        <select name="discount_type" id="discount_type" class="form-select" required>
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Discount Value</label>
                        <div class="input-group">
                            <input type="number" name="discount_value" id="discount_value"
                                class="form-control" min="0" step="0.01"
                                placeholder="Nhập giá trị giảm" required>
                            <span class="input-group-text" id="discount-unit">%</span>
                        </div>
                        <div class="form-text text-muted" id="discount-help"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Maximum Uses</label>
                        <input type="number" name="max_uses" id="max_uses"
                            class="form-control" min="1" placeholder="Số lượng sử dụng tối đa">
                    </div>
                   
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Valid From</label>
                        <input type="datetime-local" name="valid_from" id="valid_from"
                            class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valid Until</label>
                        <input type="datetime-local" name="valid_until" id="valid_until"
                            class="form-control" required>
                    </div>
                     <div class="col-md-3">
                        <label class="form-label">Minimum Order Amount</label>
                        <input type="number" name="min_order_amount" id="min_order_amount"
                            class="form-control" min="0" step="1000" value="0" required>
                    </div>
                </div>
                <button type="submit" name="submit" class="btn" style="background-color: #8c7e71;">Save Discount Code</button>
                <button type="button" onclick="resetForm()" class="btn btn-secondary">Reset</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <strong>Discount Codes List</strong>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Usage</th>
                        <th>Min. Order</th>
                        <th>Valid Period</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discount_codes as $code): ?>
                        <tr>
                            <td>
                                <span class="code-badge badge bg-light text-dark border">
                                    <?= htmlspecialchars($code['code']) ?>
                                </span>
                            </td>
                            <td class="discount-value">
                                <?php if ($code['discount_type'] === 'percent'): ?>
                                    <?= $code['discount_value'] ?>%
                                <?php else: ?>
                                    <?= number_format($code['discount_value']) ?>₫
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $code['used_count'] ?> /
                                <?= $code['max_uses'] ? $code['max_uses'] : '∞' ?>
                            </td>
                            <td><?= number_format($code['min_order_amount']) ?>₫</td>
                            <td>
                                <small>
                                    From: <?= date('d/m/Y H:i', strtotime($code['valid_from'])) ?><br>
                                    Until: <?= date('d/m/Y H:i', strtotime($code['valid_until'])) ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                $now = time();
                                $valid_from = strtotime($code['valid_from']);
                                $valid_until = strtotime($code['valid_until']);
                                $is_active = $now >= $valid_from && $now <= $valid_until;
                                $has_uses = !$code['max_uses'] || $code['used_count'] < $code['max_uses'];
                                ?>
                                <?php if ($is_active && $has_uses): ?>
                                    <span class="status-badge badge bg-success">Active</span>
                                <?php elseif ($now < $valid_from): ?>
                                    <span class="status-badge badge bg-info">Pending</span>
                                <?php elseif ($now > $valid_until): ?>
                                    <span class="status-badge badge bg-secondary">Expired</span>
                                <?php elseif (!$has_uses): ?>
                                    <span class="status-badge badge bg-warning text-dark">Used Up</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning"
                                    onclick='editCode(<?= json_encode($code) ?>)'>
                                    Edit
                                </button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="delete" value="<?= $code['code_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editCode(code) {
        document.getElementById('edit_id').value = code.code_id;
        document.getElementById('code').value = code.code;
        document.getElementById('discount_type').value = code.discount_type;
        document.getElementById('discount_value').value = code.discount_value;
        document.getElementById('max_uses').value = code.max_uses;
        document.getElementById('min_order_amount').value = code.min_order_amount;
        document.getElementById('valid_from').value = code.valid_from.slice(0, 16);
        document.getElementById('valid_until').value = code.valid_until.slice(0, 16);
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function resetForm() {
        document.getElementById('discountForm').reset();
        document.getElementById('edit_id').value = '';
    }
    // Set min datetime for valid_from to current time
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('valid_from').min = now.toISOString().slice(0, 16);
    // Update valid_until min when valid_from changes
    document.getElementById('valid_from').addEventListener('change', function() {
        document.getElementById('valid_until').min = this.value;
    });
    // Validate discount value based on type
    function updateDiscountValidation() {
        const discountType = document.getElementById('discount_type').value;
        const valueInput = document.getElementById('discount_value');
        const unitText = document.getElementById('discount-unit');
        const helpText = document.getElementById('discount-help');
        if (discountType === 'percent') {
            valueInput.min = "0";
            valueInput.max = "100";
            valueInput.step = "0.1";
            unitText.textContent = '%';
            helpText.textContent = 'Nhập giá trị từ 0 đến 100%';
            // Validate current value if it exceeds 100
            if (parseFloat(valueInput.value) > 100) {
                valueInput.value = 100;
            }
        } else {
            valueInput.min = "35000";
            valueInput.max = ""; // Remove max limit for fixed amount
            valueInput.step = "1000";
            unitText.textContent = '₫';
            helpText.textContent = 'Tối thiểu 35.000₫';
            // Only validate minimum value
            if (parseFloat(valueInput.value) < 35000) {
                valueInput.value = 35000;
            }
        }
    }
    // Add event listeners
    const discountTypeSelect = document.getElementById('discount_type');
    const discountValueInput = document.getElementById('discount_value');
    discountTypeSelect.addEventListener('change', updateDiscountValidation);
    // Validate on input (while typing)
    discountValueInput.addEventListener('input', function() {
        const discountType = discountTypeSelect.value;
        const value = parseFloat(this.value);
        if (discountType === 'percent') {
            if (value > 100) {
                this.value = 100;
            }
        }
    });
    // Validate when leaving the input field
    discountValueInput.addEventListener('blur', function() {
        const discountType = discountTypeSelect.value;
        const value = parseFloat(this.value);
        if (discountType === 'fixed' && (isNaN(value) || value < 35000)) {
            this.value = 35000;
        } else if (discountType === 'percent' && (isNaN(value) || value > 100)) {
            this.value = 100;
        }
    });
    // Initialize validation on page load
    updateDiscountValidation();
</script>

<?php include '../../includes/footer.php'; ?>