<?php
session_start();
include_once __DIR__ . '/../includes/database.php';

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
    foreach ($_SESSION['cart'] as $item) {
        $cart[] = $item;
    }
}
$product_ids = array_column($cart, 'product_id');
$products = [];
if (!empty($product_ids)) {
    $ids = implode(',', array_map('intval', array_unique($product_ids)));
    $result = $conn->query("SELECT * FROM products WHERE product_id IN ($ids)");
    while ($row = $result->fetch_assoc()) {
        $products[$row['product_id']] = $row;
    }
}
$grand_total = 0;
?>
<style>
    .cart-mini-list .d-flex.align-items-center {
        display: flex;
        align-items: center;
        gap: 16px;
        border-bottom: 1px solid #eee;
        padding: 14px 0 10px 0;
        position: relative;
        min-height: 80px;
    }

    .cart-mini-list img.rounded.me-3 {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #eee;
        margin-right: 12px;
        flex-shrink: 0;
        background: #fafafa;
    }

    .cart-mini-list .flex-grow-1 {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 2px;
    }

    .cart-mini-list .fw-bold.mb-1 {
        font-size: 1.08rem;
        font-weight: 700;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2;
        word-break: break-word;
    }

    .cart-mini-list .text-muted.small.mb-1 {
        font-size: 14px;
        color: #888;
        margin-bottom: 8px;
        line-height: 1.1;
    }

    .cart-mini-list .d-flex.align-items-center.gap-2 {
        gap: 0;
        margin-bottom: 0;
        align-items: center;
    }

    .cart-mini-list .cart-mini-decrease,
    .cart-mini-list .cart-mini-increase {
        width: 32px;
        height: 32px;
        border: 1.5px solid #bbb;
        background: #fff;
        color: #222;
        font-size: 20px;
        font-weight: 700;
        border-radius: 4px;
        padding: 0;
        transition: background 0.2s, color 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 2px;
    }

    .cart-mini-list .cart-mini-decrease:hover,
    .cart-mini-list .cart-mini-increase:hover {
        background: #f7f7f7;
        color: #000;
    }

    .cart-mini-list .mx-1 {
        min-width: 28px;
        display: inline-block;
        text-align: center;
        font-size: 17px;
        font-weight: 600;
        margin: 0 4px;
    }

    .cart-mini-list .ms-2.fw-bold.text-danger {
        font-size: 1.18rem;
        font-weight: 800;
        color: #e74c3c !important;
        margin-left: 16px;
        min-width: 80px;
        text-align: right;
        display: inline-block;
    }

    .cart-mini-list .cart-mini-remove {
        position: absolute;
        right: 0;
        top: 10px;
        color: #e74c3c;
        font-size: 22px;
        background: none;
        border: none;
        padding: 0 8px;
        cursor: pointer;
        z-index: 2;
        line-height: 1;
    }

    .cart-mini-list .cart-mini-remove:hover {
        color: #b71c1c;
    }

    @media (max-width: 500px) {
        .cart-mini-list img.rounded.me-3 {
            width: 54px;
            height: 54px;
        }

        .cart-mini-list .ms-2.fw-bold.text-danger {
            font-size: 1rem;
            min-width: 60px;
        }

        .cart-mini-list .fw-bold.mb-1 {
            font-size: 0.98rem;
        }
    }

    /* Thêm CSS này để checkbox luôn nền trắng, không bị nền đen */
    .mini-cart-item-select[type="checkbox"] {
        accent-color: #222 !important; /* màu viền/tích, nền vẫn trắng */
        background: #fff !important;
        border: 1.5px solid #bbb !important;
        box-shadow: none !important;
    }
    .mini-cart-item-select[type="checkbox"]:checked {
        background: #fff !important;
    }
</style>
<div class="cart-mini-title">Shopping Cart</div>
<hr class="my-1">
<div class="cart-mini-list">
    <?php if (empty($cart)): ?>
        <div class="text-center text-muted py-4">Your cart is empty.</div>
    <?php else: ?>
        <?php foreach ($cart as $item):
            $p = $products[$item['product_id']] ?? null;
            if (!$p) continue;
            $color = $item['color'] ?? '';
            $size = $item['size'] ?? '';
            $qty = (int)($item['quantity'] ?? 1);
            $max_qty = 99;
            if (!empty($p['size_stock'])) {
                $size_stock = json_decode($p['size_stock'], true);
                if (
                    isset($size_stock[$color]) &&
                    isset($size_stock[$color][$size])
                ) {
                    $max_qty = (int)$size_stock[$color][$size];
                }
            }
            if ($qty > $max_qty) $qty = $max_qty;
            $price = is_numeric($p['price']) ? (float)$p['price'] : floatval(preg_replace('/[^\d.]/', '', $p['price']));
            $total = $price * $qty;
            $grand_total += $total;
        ?>
            <div class="d-flex align-items-center border-bottom py-2 mb-2" data-price="<?php echo $price; ?>">
                <!-- Checkbox bên trái ảnh, ảnh giữ nguyên kích thước -->
                <input type="checkbox" class="mini-cart-item-select" style="margin-right:8px;width:17px;height:17px;"
                    data-pid="<?php echo $item['product_id']; ?>"
                    data-color="<?php echo htmlspecialchars($color); ?>"
                    data-size="<?php echo htmlspecialchars($size); ?>"
                />
                <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="rounded me-3" style="width:120px;height:120px;object-fit:cover;">
                <div class="flex-grow-1">
                    <div class="fw-bold" style="font-size:1rem;line-height:1.2;white-space:nowrap;padding-right:140px;margin-top:10px;">
                        <?php echo htmlspecialchars($p['name']); ?>
                    </div>
                    <div class="text-muted small" style="font-size:1rem;line-height:1.2;white-space:nowrap;padding-right:240px; margin-top:4px;">
                        <?php echo htmlspecialchars($color); ?><?php if ($color && $size) echo ' / '; ?><?php echo htmlspecialchars($size); ?>
                    </div>
                    <div class="d-flex align-items-center gap-1" style="font-size: 0.8rem; margin-bottom: 10px;">
                        <button class="btn btn-outline-secondary btn-sm px-1 py-0 cart-mini-decrease" style="font-size: 1rem; width: 26px; height: 26px;"
                            data-pid="<?php echo $item['product_id']; ?>"
                            data-color="<?php echo htmlspecialchars($color); ?>"
                            data-size="<?php echo htmlspecialchars($size); ?>">-</button>
                        <span class="mx-1" style="min-width:16px;display:inline-block;text-align:center;"><?php echo $qty; ?></span>
                        <button class="btn btn-outline-secondary btn-sm px-1 py-0 cart-mini-increase" style="font-size: 1rem; width: 26px; height: 26px;"
                            data-pid="<?php echo $item['product_id']; ?>"
                            data-color="<?php echo htmlspecialchars($color); ?>"
                            data-size="<?php echo htmlspecialchars($size); ?>"
                            data-max="<?php echo $max_qty; ?>">+</button>
                        <span class="ms-2 fw-bold text-danger" style="font-size:1.1rem;"><?php echo number_format($price, 0, ',', '.'); ?>₫</span>
                    </div>
                </div>
                <button class="btn btn-link text-dark cart-mini-remove ms-2" data-pid="<?php echo $item['product_id']; ?>" data-color="<?php echo htmlspecialchars($color); ?>" data-size="<?php echo htmlspecialchars($size); ?>" title="Delete"><i class="bi bi-x-lg"></i></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<hr class="my-1">
<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="fw-bold" style="font-size:1.1rem;">Total Amount:</span>
    <span class="cart-mini-total fw-bold text-danger" style="font-size:1.3rem;">0₫</span>
</div>
<div class="cart-mini-btns d-flex gap-3" style="margin-bottom: 10px;">
    <a href="/pages/cart.php" class="btn btn-dark w-50" style="width:50px; height: 50px;">View Cart</a>
    <button id="miniCartCheckoutBtn" class="btn btn-outline-dark w-50" style="width:50px; height: 50px;">Checkout</button>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function updateMiniCartQty(pid, color, size, qty) {
        fetch('/public/update_cart_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                product_id: pid,
                color: color,
                size: size,
                quantity: qty
            })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                const btns = document.querySelectorAll('.cart-mini-increase, .cart-mini-decrease');
                btns.forEach(btn => {
                    if (
                        btn.getAttribute('data-pid') == pid &&
                        btn.getAttribute('data-color') == color &&
                        btn.getAttribute('data-size') == size
                    ) {
                        let qtySpan = btn.parentElement.querySelector('span');
                        if (qtySpan) qtySpan.textContent = data.quantity;
                    }
                });
                // Không reload lại popup, chỉ cập nhật số lượng và tổng tiền
                updateMiniCartTotal();
            }
        });
    }

    function removeMiniCartItem(pid, color, size) {
        fetch('/public/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                product_id: pid,
                color: color,
                size: size
            })
        }).then (r => r.json()).then(data => {
            if (data.success) {
                document.querySelectorAll('.cart-mini-remove').forEach(btn => {
                    if (
                        btn.getAttribute('data-pid') == pid &&
                        btn.getAttribute('data-color') == color &&
                        btn.getAttribute('data-size') == size
                    ) {
                        let item = btn.closest('.d-flex.align-items-center');
                        if (item) item.remove();
                    }
                });
                updateMiniCartTotal();
            }
        });
    }

    function updateMiniCartTotal() {
        let total = 0;
        document.querySelectorAll('.cart-mini-list > .d-flex.align-items-center').forEach(function(item) {
            var checkbox = item.querySelector('.mini-cart-item-select');
            if (checkbox && checkbox.checked) {
                var priceVal = parseFloat(item.getAttribute('data-price'));
                var qtyEl = item.querySelector('span.mx-1');
                var qtyVal = qtyEl ? parseInt(qtyEl.textContent) : 1;
                if (!isNaN(priceVal) && !isNaN(qtyVal)) total += priceVal * qtyVal;
            }
        });
        var totalEl = document.querySelector('.cart-mini-total');
        if (totalEl) {
            if (total === 0) {
                totalEl.textContent = '0₫';
            } else {
                totalEl.textContent = total.toLocaleString('vi-VN') + '₫';
            }
        }
    }

    function initMiniCartCheckboxes() {
        document.querySelectorAll('.mini-cart-item-select').forEach(function(checkbox) {
            checkbox.checked = false;
        });
        updateMiniCartTotal();
    }

    // XÓA đoạn này để không tự động reset checkbox khi render mini cart
    // syncMiniCartCheckboxesWithStorage();

    // Khi tích/bỏ tích checkbox trong mini cart, cập nhật lại localStorage
    document.querySelectorAll('.mini-cart-item-select').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const selected = [];
            document.querySelectorAll('.mini-cart-item-select').forEach(function(cb) {
                if (cb.checked) {
                    selected.push({
                        pid: cb.getAttribute('data-pid'),
                        color: cb.getAttribute('data-color'),
                        size: cb.getAttribute('data-size')
                    });
                }
            });
            localStorage.setItem('checkout_selected', JSON.stringify(selected));
            updateMiniCartTotal();
        });
    });

    document.querySelectorAll('.cart-mini-increase').forEach(btn => {
        btn.addEventListener('click', function() {
            var pid = this.getAttribute('data-pid');
            var color = this.getAttribute('data-color');
            var size = this.getAttribute('data-size');
            var qtySpan = this.parentElement.querySelector('span');
            var qty = parseInt(qtySpan.textContent) + 1;
            var max = parseInt(this.getAttribute('data-max'));
            if (!isNaN(max) && qty > max) {
                qtySpan.textContent = max;
                return;
            }
            updateMiniCartQty(pid, color, size, qty);
            // KHÔNG reload lại popup, KHÔNG gọi showMiniCart()
        });
    });
    document.querySelectorAll('.cart-mini-decrease').forEach(btn => {
        btn.addEventListener('click', function() {
            var pid = this.getAttribute('data-pid');
            var color = this.getAttribute('data-color');
            var size = this.getAttribute('data-size');
            var qty = parseInt(this.parentElement.querySelector('span').textContent) - 1;
            if (qty > 0) updateMiniCartQty(pid, color, size, qty);
            // KHÔNG reload lại popup, KHÔNG gọi showMiniCart()
        });
    });
    document.querySelectorAll('.cart-mini-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            var pid = this.getAttribute('data-pid');
            var color = this.getAttribute('data-color');
            var size = this.getAttribute('data-size');
            removeMiniCartItem(pid, color, size);
        });
    });
    // Đảm bảo chỉ gắn sự kiện sau khi DOM đã render xong
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.mini-cart-item-select').forEach(function(checkbox) {
            checkbox.addEventListener('change', updateMiniCartTotal);
            checkbox.addEventListener('input', updateMiniCartTotal);
        });
    });

    (function() {
        var checkoutBtn = document.getElementById('miniCartCheckoutBtn');
        if (checkoutBtn) {
            checkoutBtn.onclick = function(e) {
                e.preventDefault();
                const selected = [];
                document.querySelectorAll('.mini-cart-item-select').forEach(function(checkbox) {
                    if (checkbox.checked) {
                        selected.push({
                            pid: checkbox.getAttribute('data-pid'),
                            color: checkbox.getAttribute('data-color'),
                            size: checkbox.getAttribute('data-size')
                        });
                    }
                });
                if (selected.length === 0) {
                    if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'warning',
                            title: 'Bạn chưa chọn sản phẩm nào để thanh toán!',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                    } else {
                        alert('Bạn chưa chọn sản phẩm nào để thanh toán!');
                    }
                    return false;
                }
                try {
                    localStorage.setItem('checkout_selected', JSON.stringify(selected));
                    window.location.href = '/pages/checkout.php';
                } catch (err) {
                    alert('Có lỗi khi lưu thông tin sản phẩm.');
                }
                return false;
            };
        }
    })();

    initMiniCartCheckboxes();
</script>