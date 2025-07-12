<?php
session_start();
include '../includes/database.php';

// Lấy thông tin user nếu đã đăng nhập
$user_info = [
    'fullname' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT fullname, email, phone, address FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($user_info['fullname'], $user_info['email'], $user_info['phone'], $user_info['address']);
    $stmt->fetch();
    $stmt->close();
}

// Nếu là POST (AJAX) và có selected, chỉ render sản phẩm đã chọn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $selected = $input['selected'] ?? [];
    include '../includes/database.php';
    $products = [];
    foreach ($selected as $item) {
        $pid = intval($item['pid']);
        $color = $item['color'];
        $size = $item['size'];
        // Lấy số lượng đúng từ cart/session hoặc DB
        $quantity = 1;
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $stmt2 = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
            $stmt2->bind_param('iiss', $user_id, $pid, $color, $size);
            $stmt2->execute();
            $stmt2->bind_result($quantity_db);
            if ($stmt2->fetch()) $quantity = $quantity_db;
            $stmt2->close();
        } else if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $cart_item) {
                if (
                    $cart_item['product_id'] == $pid &&
                    $cart_item['color'] == $color &&
                    $cart_item['size'] == $size
                ) {
                    $quantity = $cart_item['quantity'];
                    break;
                }
            }
        }
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $products[] = [
                'product_id' => $pid,
                'name' => $row['name'],
                'image_url' => $row['image_url'],
                'price' => $row['price'],
                'color' => $color,
                'size' => $size,
                'quantity' => $quantity
            ];
        }
        $stmt->close();
    }
    // Render lại chỉ danh sách sản phẩm đã chọn (KHÔNG render Discount Code, subtotal, shipping, total)
    echo '<div id="checkoutCartListInner">';
    foreach ($products as $p) {
        echo '<div class="checkout-cart-item d-flex align-items-center mb-3" data-pid="' . htmlspecialchars($p['product_id']) . '" data-color="' . htmlspecialchars($p['color']) . '" data-size="' . htmlspecialchars($p['size']) . '" style="gap:16px;">';
        echo '<div class="position-relative me-3">';
        echo '<img src="' . htmlspecialchars($p['image_url']) . '" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #eee;">';
        if ($p['quantity'] > 1) {
            echo '<span class="position-absolute" style="top:3px;left:3px;z-index:2;background:#fff;border:1.5px solid #bbb;padding:2px 8px;font-size:13px;font-weight:600;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.07);">' . $p['quantity'] . '</span>';
        }
        echo '</div>';
        echo '<div style="flex:1">';
        echo '<div style="font-weight:700;font-size:15px;">' . htmlspecialchars($p['name']) . '</div>';
        echo '<div style="font-size:14px;">Color: ' . htmlspecialchars($p['color']) . ' | Size: ' . htmlspecialchars($p['size']) . '</div>';
        echo '</div>';
        echo '<div style="font-weight:600; margin-top: 6px;">' . number_format($p['price'] * $p['quantity'], 0, ',', '.') . '₫';
        echo '</div>';
        echo '<button type="button" class="btn btn-link text-danger ms-2 btn-remove-checkout-item" data-pid="' . htmlspecialchars($p['product_id']) . '" data-color="' . htmlspecialchars($p['color']) . '" data-size="' . htmlspecialchars($p['size']) . '" style="font-size:18px;"><i class="fa fa-trash"></i></button>';
        echo '</div>';
    }
    echo '</div>';
    exit;
}

// Nếu có checkout_selected trong localStorage (qua JS), ưu tiên render sản phẩm đó
$checkout_selected = [];
if (isset($_COOKIE['checkout_selected'])) {
    $checkout_selected = json_decode($_COOKIE['checkout_selected'], true);
}
if ($checkout_selected && is_array($checkout_selected)) {
    $cart = [];
    foreach ($checkout_selected as $item) {
        $cart[] = [
            'product_id' => $item['pid'],
            'color' => $item['color'],
            'size' => $item['size'],
            'quantity' => $item['qty'] ?? 1
        ];
    }
}

// Lấy giỏ hàng từ session hoặc DB (chỉ render nếu KHÔNG có checkout_selected)
$cart = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM cart_items WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart[] = $row;
    }
    $stmt->close();
} else if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cart[] = $item;
}

// Lấy thông tin sản phẩm
$product_map = [];
if ($cart) {
    $ids = implode(',', array_map(function ($item) {
        return intval($item['product_id']);
    }, $cart));
    $result = $conn->query("SELECT * FROM products WHERE product_id IN ($ids)");
    while ($row = $result->fetch_assoc()) {
        $product_map[$row['product_id']] = $row;
    }
}

// Tính tổng tiền
$subtotal = 0;
foreach ($cart as $item) {
    $pid = $item['product_id'];
    $qty = $item['quantity'];
    // Lấy giá sản phẩm từ DB (bảng products)
    $price = 0;
    if (isset($product_map[$pid])) {
        $price = (float)$product_map[$pid]['price'];
    }
    $subtotal += $price * $qty;
}
$shipping_fee = 0; // Có thể tính động sau
$total = $subtotal + $shipping_fee;
// Nếu không có sản phẩm nào để thanh toán thì redirect về trang chủ
if (empty($cart)) {
    header('Location: /pages/home.php');
    exit;
}
include '../includes/header.php';
?>
<!-- Thêm div thông báo khi không có sản phẩm được chọn -->

<div class="container-fluid" style="margin-top:20px;padding:0;">
    <div class="row g-0 bg-white" style="min-height:100vh;">
        <div class="col-md-7 p-5" style="min-height:100vh;">

            <nav style="font-size:14px;margin-bottom:18px;">
                <a href="/pages/cart.php" class="text-decoration-none checkout-nav-step inactive" id="navCart">My Cart</a>
                <span class="mx-1 text-muted">&gt;</span>
                <span class="checkout-nav-step active" id="navShipping" style="cursor:pointer;">Shipping Information</span>
                <span class="mx-1 text-muted"></span>

            </nav>
            <h4 class="mb-3 fw-bold" style="font-size:1.3rem;">Shipping Information</h4>
            <div class="mb-3" style="font-size:15px;">
                Do you have an account? <a href="/pages/login.php?redirect=/pages/checkout.php?step=payment" class="text-primary">Login Now</a>
            </div>
            <form id="checkoutForm" autocomplete="off">
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <input type="text" class="form-control" name="fullname" placeholder="Họ và tên" required value="<?php echo htmlspecialchars($user_info['fullname']); ?>">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <input type="email" class="form-control" name="email" id="emailInput" placeholder="Email" required pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" value="<?php echo htmlspecialchars($user_info['email']); ?>">
                        <div class="invalid-feedback" id="emailError" style="display:none;">Invalid email</div>
                    </div>
                    <div class="col-6">
                        <input type="tel" class="form-control" name="phone" placeholder="Số điện thoại" required value="<?php echo htmlspecialchars($user_info['phone']); ?>">
                    </div>
                </div>
                <div class="mb-2">
                    <input type="text" class="form-control" name="address" placeholder="Địa chỉ" required value="<?php echo htmlspecialchars($user_info['address']); ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <select class="form-select" name="province" id="provinceSelect" required>
                            <option value="">Province / City</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="district" id="districtSelect" required>
                            <option value="">Urban / Rural</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="ward" id="wardSelect" required>
                            <option value="">Ward / Commune</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="bank_name" id="bankNameInput" value="">
                <div class="d-flex justify-content-between align-items-center mt-4">

                    <button type="submit" class="btn px-4" style="font-size:17px; color:white; background-color:black;border:1px solid #dee2e6;">Continue to Payment</button>
                </div>
            </form>
            <!-- Payment Method Section (ẩn mặc định, hiện khi submit form) -->
            <div id="paymentMethodSection" style="display:none; margin-top:10px;">
                <h5 class="mb-3 fw-bold">Choose Payment Method</h5>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="payment_method" id="payCOD" value="COD" checked>
                    <label class="form-check-label" for="payCOD">Cash on Delivery</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="payBank" value="Bank">
                    <label class="form-check-label" for="payBank">Bank Wire Transfer</label>
                </div>
                <div id="bankTransferOptions" style="display:none; margin-bottom:20px;">
                    <label class="mb-2 fw-bold">Select Bank:</label>
                    <select id="bankSelect" class="form-select mb-2" style="max-width:300px;">
                        <option value="">-- Select Bank --</option>
                        <option value="vcb">Vietcombank</option>
                        <option value="tcb">Techcombank</option>                   
                        <option value="bidv">BIDV</option>
                        <option value="mb">MB Bank</option>
                    </select>
                    <div id="qrSection" style="display:none;">
                        <div class="mb-2">Scan QR Code to Pay <span id="qrBankName"></span>:</div>
                        <img id="qrImage" src="" alt="QR code" style="width:280px;height:280px;border:1px solid #eee; margin-left:170px;">
                        <div class="mt-2 fw-bold"><span id="qrAmount"></span></div>
                    </div>
                </div>
                <button id="confirmOrderBtn" class="btn btn-dark px-4" style="margin-left:480px">Submit Order</button>
            </div>
        </div>
        <!-- Right: Order Summary -->
        <div class="col-md-5 p-5 border-start" style="background:#fafbfc;min-height:100vh;position:relative;">
            <div style="position:sticky;top:0;">
                <div id="checkoutCartList" style="max-height:240px;overflow-y:auto;scrollbar-width:thin;">
                    <div id="checkoutCartListInner">
                        <?php if ($cart): ?>
                            <?php foreach ($cart as $item):
                                $pid = $item['product_id'];
                                $product = $product_map[$pid];
                            ?>
                                <div class="d-flex align-items-center mb-3 checkout-cart-item"
                                    data-pid="<?php echo $pid; ?>"
                                    data-color="<?php echo htmlspecialchars($item['color']); ?>"
                                    data-size="<?php echo htmlspecialchars($item['size']); ?>"
                                    style="cursor:pointer; padding: 10px 0 10px 0; min-height: 70px;"
                                    onclick="if(event.target.closest('.btn-remove-checkout-item')) return; window.open('/pages/detail_products.php?id=<?php echo $pid; ?>', '_blank');">
                                    <div class="position-relative me-3" style="margin-top: 6px;">
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                                        <?php if ($item['quantity'] > 1): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary" style="font-size:13px;"><?php echo $item['quantity']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex:1; margin-top: 6px;">
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($item['color']); ?> / <?php echo htmlspecialchars($item['size']); ?></div>
                                    </div>
                                    <div style="font-weight:600; margin-top: 6px;"><?php echo number_format($product['price'] * $item['quantity'], 0, ',', '.'); ?>₫</div>
                                    <button type="button" class="btn btn-link text-danger ms-2 btn-remove-checkout-item"
                                        data-pid="<?php echo $pid; ?>"
                                        data-color="<?php echo htmlspecialchars($item['color']); ?>"
                                        data-size="<?php echo htmlspecialchars($item['size']); ?>"
                                        style="font-size:18px;"
                                        onclick="event.stopPropagation();"><i class="fa fa-trash"></i></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="discountCodeInput" placeholder="Discount Code" style="font-size:15px;">
                        <button class="btn btn-dark border" type="button" id="applyDiscountBtn">Apply</button>
                    </div>
                    <div id="discountCodeMsg" class="mt-1" style="font-size:14px;"></div>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size:15px;">
                    <span>Subtotal</span>
                    <span id="checkoutSubtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</span>
                </div>
                <div id="discountRow" class="d-flex justify-content-between mb-2" style="font-size:15px;display:none;">
                    <span id="discountLabel"></span>
                    <span id="discountValue" style="color:#e74c3c;"></span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size:15px;">
                    <span>Shipping Free</span>
                    <span id="checkoutShipping">35,000₫</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center" style="font-size:18px;font-weight:700;">
                    <span>Total Amount</span>
                    <span><span style="font-size:13px;font-weight:400;color:#888;">VND</span> <span id="checkoutTotal"><?php echo number_format($total, 0, ',', '.'); ?></span>₫</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/truck.php'; ?>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/floating_contact.php'; ?>
<script src="/assets/js/auto_logout.js"></script>
<script src="/assets/js/checkout.js"></script>
<!-- Bootstrap 5 JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Kiểm tra realtime trường email, báo lỗi nếu sai định dạng
document.addEventListener('DOMContentLoaded', function() {
    var emailInput = document.getElementById('emailInput');
    var emailError = document.getElementById('emailError');
    var provinceSelect = document.getElementById('provinceSelect');
    var districtSelect = document.getElementById('districtSelect');
    var wardSelect = document.getElementById('wardSelect');
    function setAddressSelectsDisabled(disabled) {
        if (provinceSelect) provinceSelect.disabled = disabled;
        if (districtSelect) districtSelect.disabled = true;
        if (wardSelect) wardSelect.disabled = true;
        if (disabled) {
            if (provinceSelect) provinceSelect.value = '';
            if (districtSelect) districtSelect.value = '';
            if (wardSelect) wardSelect.value = '';
        }
    }
    if (emailInput && emailError) {
        // Kiểm tra lần đầu khi load
        if (!emailInput.validity.valid) {
            emailError.style.display = 'block';
            emailInput.classList.add('is-invalid');
            setAddressSelectsDisabled(true);
        } else {
            emailError.style.display = 'none';
            emailInput.classList.remove('is-invalid');
            setAddressSelectsDisabled(false);
        }
        emailInput.addEventListener('input', function() {
            if (emailInput.validity.valid) {
                emailError.style.display = 'none';
                emailInput.classList.remove('is-invalid');
                setAddressSelectsDisabled(false);
            } else {
                emailError.style.display = 'block';
                emailInput.classList.add('is-invalid');
                setAddressSelectsDisabled(true);
            }
        });
    }
});
// --- Thêm đoạn này để cập nhật tên ngân hàng vào input ẩn ---
document.addEventListener('DOMContentLoaded', function() {
    var bankSelect = document.getElementById('bankSelect');
    var bankNameInput = document.getElementById('bankNameInput');
    if (bankSelect && bankNameInput) {
        bankSelect.addEventListener('change', function() {
            let bankName = '';
            switch (bankSelect.value) {
                case 'vcb': bankName = 'Vietcombank'; break;
                case 'tcb': bankName = 'Techcombank'; break;
                case 'mb': bankName = 'MB Bank'; break;
                case 'bidv': bankName = 'BIDV'; break;
                default: bankName = '';
            }
            bankNameInput.value = bankName;
        });
    }
});
</script>
<!-- Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">