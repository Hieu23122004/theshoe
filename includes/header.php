<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>The Shoes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">

</head>

<body>
    <!-- Top bar -->
    <div class="top-bar d-flex justify-content-between align-items-center px-4">
        <div class="d-flex align-items-center gap-3">
            <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                <span style="color:white;font-weight:600;font-size:14px;">
                    <i class="bi bi-person-circle me-1" style="font-size:17px;"></i>
                    <?php echo htmlspecialchars($_SESSION['user']['email'] ?? $_SESSION['user']['email'] ?? $_SESSION['username'] ?? ''); ?>
                </span>
            <?php endif; ?>
        </div>
        <div>
            THE SHOES STORE &nbsp;&nbsp;|&nbsp;&nbsp; Hotline: 1900 6868 &nbsp;&nbsp;|&nbsp;&nbsp; theshoe@gmail.com
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#" style="margin-left: 20px; margin-top: 8px;">
                <div class="d-flex align-items-center gap-2" style="color:#8C7E71;">
                    <svg viewBox="0 0 100 100" fill="none" stroke="#8C7E71" stroke-width="5" stroke-linejoin="round" style="width:40px;height:40px;">
                        <path d="M10 50 L30 20 L70 20 L90 50 L50 90 Z" />
                        <path d="M30 55 L40 35 L50 55 L60 35 L70 55" />
                        <path d="M60 65 Q50 75 40 65 L40 60 L50 60 L50 68" />
                    </svg>
                    <div class="d-flex flex-column align-items-center" style="line-height:1;">
                        <span style="font-family:'Montserrat',Arial,sans-serif;font-size:1.5rem;font-weight:600;letter-spacing:2px;">
                            MULGATI
                            <sup style="font-size:0.7em; position: relative; margin-left: 1px;">®</sup>
                        </span>
                        <span style="display:block;width:100%;height:1px;background:#8C7E71;margin:2px 0 2px 0;"></span>
                        <span style="font-size:0.8rem;letter-spacing:8px;">RUSSIA</span>
                    </div>

                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-3">
                    <li class="nav-item"><a class="nav-link <?php if ($current_page == 'new_products.php') echo 'active'; ?>" href="/pages/new_products.php">NEW</a></li>
                    <li class="nav-item"><a class="nav-link <?php if ($current_page == 'sale_products.php') echo 'active'; ?>" href="/pages/sale_products.php">SALE</a></li>
                    <li class="nav-item"><a class="nav-link <?php if ($current_page == 'type_products.php') echo 'active'; ?>" href="/pages/type_products.php">MEN'S SHOES</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php if ($current_page == 'type_accessories.php') echo 'active'; ?>" href="#" id="accessoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            ACCESSORIES
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="accessoriesDropdown">
                            <li><a class="dropdown-item" href="/pages/handbag_accessories.php?category=2">Handbag</a></li>
                            <li><a class="dropdown-item" href="/pages/belt_accessories.php?category=3">Belt</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link <?php if ($current_page == 'introduction.php') echo 'active'; ?>" href="/pages/introduction.php">INTRODUCTION</a></li>
                    <li class="nav-item"><a class="nav-link <?php if ($current_page == 'new_promotions.php') echo 'active'; ?>" href="/pages/new_promotions.php">PROMOTIONS</a></li>
                </ul>
                <div class="d-flex align-items-center position-relative">
                   <a href="/pages/search.php">
                        <button class="icon-btn"><i class="bi bi-search fs-5"></i></button>
                    </a>
                    <a href="/pages/cart.php" class="position-relative">
                        <button class="icon-btn position-relative" style="padding-right:0;">
                            <i class="bi bi-cart fs-5"></i>
                            <?php
                            // Hiển thị số lượng sản phẩm trong giỏ hàng
                            $cart_count = 0;
                            if (isset($_SESSION['user_id'])) {
                                // Nếu đã đăng nhập, lấy từng sản phẩm riêng biệt (theo product_id, color, size)
                                include_once __DIR__ . '/../includes/database.php';
                                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart_items WHERE user_id = ?");
                                $stmt->bind_param('i', $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($row = $result->fetch_assoc()) {
                                    $cart_count = (int)$row['total'];
                                }
                                $stmt->close();
                            } else if (isset($_SESSION['cart'])) {
                                // Đếm số sản phẩm khác nhau trong session cart
                                $cart_count = count($_SESSION['cart']);
                            }
                            if ($cart_count > 0): ?>
                                <span class="position-absolute top-3 start-100 translate-middle badge rounded-pill bg-dark" style="font-size:8px;min-width:15px;line-height:10px;">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </a>

                    <button class="icon-btn position-relative"><i class="bi bi-heart fs-5"></i>
                        <?php
                        // Hiển thị số lượng sản phẩm yêu thích
                        $fav_count = 0;
                        if (isset($_SESSION['user_id'])) {
                            include_once __DIR__ . '/../includes/database.php';
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM favorites WHERE user_id = ?");
                            $stmt->bind_param('i', $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                $fav_count = (int)$row['total'];
                            }
                            $stmt->close();
                        } else if (isset($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
                            $fav_count = count($_SESSION['favorites']);
                        }
                        if ($fav_count > 0): ?>
                            <span class="position-absolute top-3 start-100 translate-middle badge rounded-pill bg-dark" style="font-size:8px;min-width:15px;line-height:10px;">
                                <?php echo $fav_count; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <a href="/pages/login.php"><button class="icon-btn"><i class="bi bi-box-arrow-in-right fs-4"></i></button></a>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Sau khi add to cart thành công, cập nhật badge giỏ hàng trên header (nếu có)
        function updateCartBadge(newCount) {
            var badge = document.querySelector('.bi-cart').parentElement.querySelector('.badge');
            if (badge) {
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            } else if (newCount > 0) {
                // Nếu chưa có badge, tạo mới
                var btn = document.querySelector('.bi-cart').parentElement;
                var span = document.createElement('span');
                span.className = 'position-absolute top-3 start-100 translate-middle badge rounded-pill bg-dark';
                span.style.fontSize = '6px';
                span.style.minWidth = '15px';
                span.style.lineHeight = '10px';
                span.textContent = newCount;
                btn.appendChild(span);
            }
        }

        function updateFavoriteBadge(newCount) {
            var badge = document.querySelector('.bi-heart').parentElement.querySelector('.badge');
            if (badge) {
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            } else if (newCount > 0) {
                var btn = document.querySelector('.bi-heart').parentElement;
                var span = document.createElement('span');
                span.className = 'position-absolute top-3 start-100 translate-middle badge rounded-pill bg-dark';
                span.style.fontSize = '8px';
                span.style.minWidth = '15px';
                span.style.lineHeight = '10px';
                span.textContent = newCount;
                btn.appendChild(span);
            }
        }

        // Hook vào AJAX add to cart (giả sử bạn dùng fetch)
        if (window.location.pathname.includes('detail_products.php')) {
            // Lắng nghe sự kiện add to cart thành công
            document.addEventListener('addToCartSuccess', function(e) {
                // e.detail.count là số sản phẩm mới trong cart
                updateCartBadge(e.detail.count);
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Đảm bảo SweetAlert2 chỉ được load 1 lần và nằm trước các script sử dụng Swal -->
    <script>
        // Kiểm tra nếu đã có SweetAlert2 thì không load lại
        if (typeof Swal === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            document.head.appendChild(script);
        }
    </script>
    <script>
        // Mini-cart popup khi bấm vào icon giỏ hàng
        function showMiniCart() {
            fetch('/public/mini_cart.php')
                .then(r => r.text())
                .then(html => {
                    console.log('showMiniCart: Đã fetch xong mini_cart.php');
                    Swal.fire({
                        html: html,
                        showConfirmButton: false,
                        showCloseButton: true,
                        width: 520,
                        customClass: {
                            popup: 'swal2-cart-popup'
                        },
                        didOpen: () => {
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
                                        if (typeof Swal !== 'undefined') {
                                            console.log('showMiniCart: Toast max stock');
                                            Swal.fire({
                                                toast: true,
                                                position: 'top-end',
                                                icon: 'info',
                                                title: 'This is the maximum quantity available in stock.',
                                                showConfirmButton: false,
                                                timer: 2200,
                                                background: '#222',
                                                color: '#fff',
                                                customClass: { popup: 'swal2-toast-custom' }
                                            });
                                        }
                                        return;
                                    }
                                    updateMiniCartQtyDOM(pid, color, size, qty, qtySpan, max);
                                });
                            });
                            document.querySelectorAll('.cart-mini-decrease').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    var pid = this.getAttribute('data-pid');
                                    var color = this.getAttribute('data-color');
                                    var size = this.getAttribute('data-size');
                                    var qtySpan = this.parentElement.querySelector('span');
                                    var qty = parseInt(qtySpan.textContent) - 1;
                                    if (qty > 0) updateMiniCartQtyDOM(pid, color, size, qty, qtySpan);
                                });
                            });
                            document.querySelectorAll('.cart-mini-remove').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    var pid = this.getAttribute('data-pid');
                                    var color = this.getAttribute('data-color');
                                    var size = this.getAttribute('data-size');
                                    removeMiniCartItemDOM(pid, color, size, this);
                                });
                            });
                            // Gắn lại sự kiện cho nút checkout trong mini cart popup
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
                                        console.log('miniCartCheckoutBtn: Không chọn sản phẩm, show toast');
                                        if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
                                            Swal.fire({
                                                toast: true,
                                                position: 'top-end',
                                                icon: 'info',
                                                title: 'You have not selected any products for checkout!',
                                                showConfirmButton: false,
                                                timer: 2000,
                                                background: '#222',
                                                color: '#fff',
                                                timerProgressBar: true,
                                                didOpen: (toast) => {
                                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                                },
                                                customClass: {
                                                    popup: 'swal2-toast-custom-compact'
                                                }
                                            });
                                        } else {
                                            alert('You have not selected any products for checkout!');
                                        }
                                        return false;
                                    }
                                    try {
                                        localStorage.setItem('checkout_selected', JSON.stringify(selected));
                                        console.log('miniCartCheckoutBtn: Đã lưu sản phẩm chọn, chuyển sang trang checkout.');
                                        window.location.href = '/pages/checkout.php';
                                    } catch (err) {
                                        console.error('miniCartCheckoutBtn: localStorage error:', err);
                                        alert('Có lỗi khi lưu thông tin sản phẩm.');
                                    }
                                    return false;
                                };
                            }
                            // --- ĐỒNG BỘ CHECKBOX MINI CART VỚI LOCALSTORAGE ---
                            if (typeof syncMiniCartCheckboxesWithStorage === 'function') {
                                syncMiniCartCheckboxesWithStorage();
                            } else if (window.syncMiniCartCheckboxesWithStorage) {
                                window.syncMiniCartCheckboxesWithStorage();
                            }
                            // Gắn lại sự kiện cho checkbox để cập nhật localStorage khi tích/bỏ tích
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
                                });
                            });
                        }
                    });
                });
        }
        document.querySelectorAll('.bi-cart').forEach(function(icon) {
            icon.parentElement.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = '/pages/cart.php';
            });
        });
    </script>

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
                    showMiniCart();
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
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    showMiniCart();
                }
            });
        }

        function updateMiniCartQtyDOM(pid, color, size, qty, qtySpan, maxQty) {
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
                    // Chỉ cập nhật số lượng và tổng tiền, không reload lại popup
                    if (qtySpan) qtySpan.textContent = data.quantity;
                    // Cập nhật lại tổng tiền bằng giá trị gốc từ data-price
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
            });
        }

        function removeMiniCartItemDOM(pid, color, size, btn) {
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
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    var item = btn.closest('.d-flex.align-items-center');
                    if (item) item.remove();
                    updateMiniCartTotal();
                }
            });
        }

        function updateMiniCartTotal() {
            let total = 0;
            let checkedCount = 0;
            document.querySelectorAll('.cart-mini-list > .d-flex.align-items-center').forEach(function(item) {
                var checkbox = item.querySelector('.mini-cart-item-select');
                if (checkbox && checkbox.checked) {
                    checkedCount++;
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
            var count = document.querySelectorAll('.cart-mini-list .d-flex.align-items-center').length;
            updateCartBadge(count);
        }
    </script>
    <script>
        function removeMiniFavoriteItemDOM(pid, btn) {
            fetch('/public/remove_from_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    product_id: pid
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    var item = btn.closest('.d-flex.align-items-center');
                    if (item) item.remove();
                    if (typeof updateMiniFavoriteTotal === 'function') updateMiniFavoriteTotal();

                    // Update heart icon on product detail page if present
                    var favoriteBtn = document.getElementById('favoriteBtn');
                    if (favoriteBtn && String(favoriteBtn.getAttribute('data-product-id')) === String(pid)) {
                        var icon = favoriteBtn.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            if (!icon.classList.contains('fa-heart')) {
                                icon.classList.add('fa-heart');
                            }
                        }
                        favoriteBtn.classList.remove('active');
                    }

                    // Update heart icons in product list (if any)
                    document.querySelectorAll('.favorite-btn[data-product-id="' + pid + '"]').forEach(function(btn) {
                        var icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            if (!icon.classList.contains('fa-heart')) icon.classList.add('fa-heart');
                        }
                        btn.classList.remove('active');
                    });

                    // Cập nhật badge số lượng yêu thích trên header ngay lập tức
                    fetch('/public/get_favorite_count.php')
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && typeof updateFavoriteBadge === 'function') updateFavoriteBadge(data.count);
                        });
                }
            });
        }

        // --- Toggle favorite from product list (heart icon) ---
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.favorite-btn[data-product-id]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var pid = btn.getAttribute('data-product-id');
                    fetch('/pages/new_products.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'toggle_favorite=1&product_id=' + encodeURIComponent(pid)
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                var icon = btn.querySelector('i');
                                if (icon) {
                                    icon.classList.toggle('fas');
                                    icon.classList.toggle('far');
                                }
                                // Cập nhật badge số lượng yêu thích trên header
                                fetch('/public/get_favorite_count.php')
                                    .then (r => r.json())
                                    .then(data => {
                                        if (data.success && typeof updateFavoriteBadge === 'function') updateFavoriteBadge(data.count);
                                    });
                            }
                        });
                });
            });

            // Chọn đúng button chứa icon tim ở header (không phải các .favorite-btn)
            var headerHeartBtn = null;
            document.querySelectorAll('.navbar .icon-btn.position-relative').forEach(function(btn) {
                if (
                    btn.querySelector('.bi-heart') &&
                    !btn.hasAttribute('data-product-id') &&
                    !btn.classList.contains('favorite-btn') &&
                    !btn.closest('a')
                ) {
                    headerHeartBtn = btn;
                }
            });
            if (!headerHeartBtn) {
                var allHeartBtns = document.querySelectorAll('.bi-heart');
                if (allHeartBtns.length) {
                    headerHeartBtn = allHeartBtns[0].closest('.icon-btn');
                }
            }
            if (headerHeartBtn) {
                headerHeartBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        window.location.href = '/pages/login.php';
                    <?php else: ?>
                        showMiniFavorite();
                    <?php endif; ?>
                });
            }
        }); // <-- Thêm dấu đóng này để kết thúc DOMContentLoaded
        // --- Mini-favorite popup khi bấm vào icon yêu thích ---
        function showMiniFavorite() {
            fetch('/public/mini_favorite.php')
                .then(r => r.text())
                .then(html => {
                    Swal.fire({
                        html: html,
                        showConfirmButton: false,
                        showCloseButton: true,
                        width: 520,
                        customClass: {
                            popup: 'swal2-cart-popup'
                        },
                        didOpen: () => {
                            document.querySelectorAll('.favorite-mini-remove').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    var pid = this.getAttribute('data-pid');
                                    removeMiniFavoriteItemDOM(pid, this);
                                });
                            });
                        }
                    });
                });
        }
    </script>
    <script>
        // Ví dụ: hàm gọi khi thêm sản phẩm vào giỏ hàng
        function addToCart(product_id, color, size, quantity) {
            fetch('/public/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    add_to_cart: 1,
                    product_id: product_id,
                    color: color,
                    size: size,
                    quantity: quantity
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // ...existing code: cập nhật badge, reload mini cart, v.v...
                    if (data.maxed) {
                        alert('Bạn đã thêm tối đa số lượng sản phẩm này trong kho.');
                    }
                } else if (data.maxed) {
                    alert('Bạn đã thêm tối đa số lượng sản phẩm này trong kho.');
                } else if (data.message) {
                    alert(data.message);
                }
            });
        }
    </script>


</body>
</html>