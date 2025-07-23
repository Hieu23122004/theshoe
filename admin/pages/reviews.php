<?php
include '../../includes/auth.php';
include '../../includes/database.php';
$message = '';
if (isset($_GET['deleted']) && $_GET['deleted'] == 'success') {
    $message = 'Review deleted successfully!';
} elseif (isset($_GET['error'])) {
    $message = 'Error: ' . htmlspecialchars($_GET['error']);
}
$parentCategories = $conn->query("SELECT category_id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
$selectedParent = $_GET['parent_id'] ?? '';
$selectedChild = $_GET['child_id'] ?? '';
$selectedProduct = $_GET['product_id'] ?? '';
$selectedRating = $_GET['rating'] ?? '';
$childCategories = [];
if ($selectedParent) {
    $childCategories = $conn->query("SELECT category_id, name FROM categories WHERE parent_id = " . intval($selectedParent));
}
$products = [];
if ($selectedChild) {
    $products = $conn->query("SELECT product_id, name FROM products WHERE category_id = " . intval($selectedChild));
}
$sql = "SELECT pr.*, p.name AS product_name, u.fullname AS user_name, c.parent_id 
        FROM product_reviews pr
        JOIN products p ON pr.product_id = p.product_id
        JOIN users u ON pr.user_id = u.user_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE 1=1";
$params = [];
$types = "";
if ($selectedProduct) {
    $sql .= " AND pr.product_id = ?";
    $params[] = intval($selectedProduct);
    $types .= "i";
} elseif ($selectedChild) {
    $sql .= " AND p.category_id = ?";
    $params[] = intval($selectedChild);
    $types .= "i";
} elseif ($selectedParent) {
    $sql .= " AND c.parent_id = ?";
    $params[] = intval($selectedParent);
    $types .= "i";
}
if ($selectedRating) {
    $sql .= " AND pr.rating = ?";
    $params[] = intval($selectedRating);
    $types .= "i";
}
$sql .= " ORDER BY pr.created_at DESC";
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $reviews = $stmt->get_result();
} else {
    $reviews = $conn->query($sql);
}
include '../../includes/header_ad.php';
?>

<div class="container-fluid px-2" style="margin-top: 110px;">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var msg = <?= json_encode($message) ?>;
        if (msg) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: msg.toLowerCase().includes('success') ? 'success' : (msg.toLowerCase().includes('error') ? 'error' : 'info'),
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
        font-size: 1.35rem !important;
        width: 420px !important;
        height: 70px !important;
        padding: 1.5rem 2.5rem !important;
        text-align: center !important;
    }
    </style>
    <div class="card mb-4">
        <div class="card-header" style="background-color: #8c7e71;"><strong>Filter Product Reviews</strong></div>
        <div class="card-body">
            <form method="GET" id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="parent_id" class="form-label">Main Category</label>
                    <select name="parent_id" id="parent_id" class="form-select">
                        <option value="">-- All --</option>
                        <?php
                        $parentCategories->data_seek(0);
                        while ($row = $parentCategories->fetch_assoc()):
                        ?>
                            <option value="<?= $row['category_id'] ?>" <?= $row['category_id'] == $selectedParent ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="child_id" class="form-label">Sub Category</label>
                    <select name="child_id" id="child_id" class="form-select">
                        <option value="">-- All --</option>
                        <?php if (!empty($childCategories)): ?>
                            <?php
                            $childCategories->data_seek(0);
                            while ($row = $childCategories->fetch_assoc()):
                            ?>
                                <option value="<?= $row['category_id'] ?>" <?= $row['category_id'] == $selectedChild ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="product_id" class="form-label">Product</label>
                    <select name="product_id" id="product_id" class="form-select">
                        <option value="">-- All --</option>
                        <?php if (!empty($products)): ?>
                            <?php
                            $products->data_seek(0);
                            while ($row = $products->fetch_assoc()):
                            ?>
                                <option value="<?= $row['product_id'] ?>" <?= $row['product_id'] == $selectedProduct ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="rating" class="form-label">Stars</label>
                    <select name="rating" id="rating" class="form-select">
                        <option value="">-- All --</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>" <?= $i == $selectedRating ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2" style="margin-top: 47px;">
                    <button type="button" id="resetBtn" class="btn btn-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><strong>Reviews List</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Reviewer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $reviews->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['review_id'] ?></td>
                            <td><?= htmlspecialchars($r['product_name']) ?></td>
                            <td><?= htmlspecialchars($r['user_name']) ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill" style="color: <?= $i <= $r['rating'] ? '#ffc107' : '#dee2e6' ?>;"></i>
                                <?php endfor; ?>
                            </td>
                            <td><?= htmlspecialchars($r['comment']) ?></td>
                            <td><?= $r['created_at'] ?></td>
                            <td>
                                <button onclick="deleteReview(<?= $r['review_id'] ?>)" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../../assets/js/ad_review.js"></script>
<?php include '../../includes/footer.php'; ?>