<?php
include '../includes/header.php';
include '../includes/database.php';

// Lấy giỏ hàng từ session hoặc DB
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
    $ids = implode(',', array_map(function($item) { return intval($item['product_id']); }, $cart));
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
    $price = isset($product_map[$pid]) ? $product_map[$pid]['price'] : 0;
    $subtotal += $price * $qty;
}
$shipping_fee = 0; // Có thể tính động sau
$total = $subtotal + $shipping_fee;
?>

<div class="container-fluid" style="margin-top:100px;padding:0;">
    <div class="row g-0 bg-white" style="min-height:100vh;">
        <div class="col-md-7 p-5" style="min-height:100vh;">
            
            <nav style="font-size:14px;margin-bottom:18px;">
                <a href="/pages/cart.php" class="text-decoration-none text-primary">My Cart</a>
                <span class="mx-1 text-muted">&gt;</span>
                <span class="text-dark">Shipping Information</span>
                <span class="mx-1 text-muted">&gt;</span>
                <span class="text-muted">Payment Method</span>
            </nav>
            <h4 class="mb-3 fw-bold" style="font-size:1.3rem;">Shipping Information</h4>
            <div class="mb-3" style="font-size:15px;">
                Do you have an account? <a href="/pages/login.php" class="text-primary">Login Now</a>
            </div>
            <form id="checkoutForm" autocomplete="off">
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <input type="text" class="form-control" name="fullname" placeholder="Họ và tên" required>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="col-6">
                        <input type="tel" class="form-control" name="phone" placeholder="Số điện thoại" required>
                    </div>
                </div>
                <div class="mb-2">
                    <input type="text" class="form-control" name="address" placeholder="Địa chỉ" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <select class="form-select" name="province" required>
                            <option value="">Tỉnh / thành</option>
                            <option>Bắc Kạn</option>
                            <!-- ...other provinces... -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="district" required>
                            <option value="">Quận / huyện</option>
                            <option>Huyện Bạch Thông</option>
                            <!-- ...other districts... -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="ward" required>
                            <option value="">Phường / xã</option>
                            <option>Xã Mỹ Thanh</option>
                            <!-- ...other wards... -->
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="/pages/cart.php" class="text-primary text-decoration-none">My Cart</a>
                    <button type="submit" class="btn btn-primary px-4" style="font-size:17px;">Continue to Payment Method</button>
                </div>
            </form>
        </div>
        <!-- Right: Order Summary -->
        <div class="col-md-5 p-5 border-start" style="background:#fafbfc;min-height:100vh;position:relative;">
            <div style="position:sticky;top:0;">
                <div id="checkoutCartList" style="max-height:270px;overflow-y:auto;scrollbar-width:thin;">
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
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" style="width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
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
<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let discountValue = 0;
let discountType = null;
let discountMsg = '';
let shippingDiscount = 0;
const DEFAULT_SHIPPING = 35000;

// Khi trang vừa load, tính tổng cộng luôn có phí ship mặc định
document.addEventListener('DOMContentLoaded', function() {
    bindRemoveCheckoutEvents();
    updateCheckoutTotals();
});

function bindRemoveCheckoutEvents() {
    document.querySelectorAll('.btn-remove-checkout-item').forEach(btn => {
        btn.onclick = function() {
            const pid = this.getAttribute('data-pid');
            const color = this.getAttribute('data-color');
            const size = this.getAttribute('data-size');
            fetch('/public/remove_from_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    product_id: pid,
                    color: color,
                    size: size
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Xóa sản phẩm khỏi DOM
                    const item = document.querySelector('.checkout-cart-item[data-pid="' + pid + '"][data-color="' + color + '"][data-size="' + size + '"]');
                    if (item) item.remove();
                    updateCheckoutTotals();
                    // Gắn lại sự kiện xóa cho các nút còn lại (nếu DOM thay đổi)
                    bindRemoveCheckoutEvents();
                    if (typeof updateCartBadge === 'function') {
                        fetch('/public/get_cart_count.php')
                            .then (r => r.json())
                            .then(res => { if (res.success) updateCartBadge(res.count); });
                    }
                } else {
                    Swal.fire('Lỗi', data.message || 'Không thể xóa sản phẩm', 'error');
                }
            });
        };
    });
}

document.getElementById('applyDiscountBtn').addEventListener('click', function() {
    const code = document.getElementById('discountCodeInput').value.trim();
    const msgEl = document.getElementById('discountCodeMsg');
    msgEl.textContent = '';
    if (!code) {
        msgEl.textContent = 'Please enter a discount code.';
        msgEl.style.color = '#e74c3c';
        discountValue = 0;
        discountType = null;
        shippingDiscount = 0;
        updateCheckoutTotals();
        return;
    }
    fetch('/public/check_discount_code.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ code })
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            discountValue = parseFloat(data.discount_value);
            discountType = data.discount_type;
            shippingDiscount = parseFloat(data.shipping_discount || 0);
            msgEl.textContent = 'Successfully applied the code!';
            msgEl.style.color = '#27ae60';
            updateCheckoutTotals();
        } else {
            discountValue = 0;
            discountType = null;
            shippingDiscount = 0;
            msgEl.textContent = data.message || 'Invalid or expired discount code.';
            msgEl.style.color = '#e74c3c';
            updateCheckoutTotals();
        }
    })
    .catch(() => {
        msgEl.textContent = 'Error checking discount code.';
        msgEl.style.color = '#e74c3c';
    });
});

function updateCheckoutTotals() {
    let subtotal = 0;
    document.querySelectorAll('.checkout-cart-item').forEach(item => {
        // Lấy giá từng sản phẩm (giá 1 sản phẩm)
        const priceDivs = item.querySelectorAll('div[style*="font-weight:600;"]');
        let price = 0;
        let qty = 1;
        priceDivs.forEach(div => {
            if (div.textContent.includes('₫')) {
                price = parseInt(div.textContent.replace(/[^\d]/g, '')) || 0;
            }
        });
        const badge = item.querySelector('.badge');
        if (badge) {
            qty = parseInt(badge.textContent) || 1;
        }
        subtotal += price;
    });
    document.getElementById('checkoutSubtotal').textContent = subtotal.toLocaleString('vi-VN') + '₫';

    // Áp dụng giảm giá
    let discount = 0;
    let discountLabel = '';
    let discountDisplay = '';
    let showDiscountRow = false;

    if (discountType === 'percent') {
        discount = Math.round(subtotal * discountValue / 100);
        if (discount > 0) {
            discountLabel = 'Giảm giá';
            discountDisplay = `- ${discountValue}%`;
            showDiscountRow = true;
        }
    } else if (discountType === 'fixed' && shippingDiscount === 0) {
        discount = Math.round(discountValue);
        if (discount > 0) {
            discountLabel = 'Giảm giá';
            discountDisplay = `- ${discount.toLocaleString('vi-VN')}₫`;
            showDiscountRow = true;
        }
    } else if (discountType === 'fixed' && shippingDiscount > 0) {
        // Mã freeship
        discount = 0;
        discountLabel = 'Giảm phí vận chuyển';
        discountDisplay = `- ${shippingDiscount.toLocaleString('vi-VN')}₫`;
        showDiscountRow = true;
    }

    // Hiển thị dòng giảm giá nếu có
    const discountRow = document.getElementById('discountRow');
    if (showDiscountRow) {
        discountRow.style.display = '';
        document.getElementById('discountLabel').textContent = discountLabel;
        document.getElementById('discountValue').textContent = discountDisplay;
    } else {
        discountRow.style.display = 'none';
    }

    // Phí vận chuyển
    let shipping = DEFAULT_SHIPPING;
    if (shippingDiscount > 0) {
        shipping = Math.max(0, DEFAULT_SHIPPING - shippingDiscount);
    }
    document.getElementById('checkoutShipping').textContent = shipping > 0 ? shipping.toLocaleString('vi-VN') + '₫' : 'Miễn phí';

    // Tổng cộng
    let total = subtotal - discount + shipping;
    if (total < 0) total = 0;
    document.getElementById('checkoutTotal').textContent = total.toLocaleString('vi-VN');
}
</script>