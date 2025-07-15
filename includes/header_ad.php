<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('INCLUDED_HEADER', true);
$current_page = basename($_SERVER['PHP_SELF']);
?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/header.css">
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
                   <!-- Dashboard nên đứng đầu -->
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'dashboard.php') echo 'active'; ?>" href="/admin/pages/dashboard.php">DASHBOARD</a></li>

                <!-- Sản phẩm và danh mục sản phẩm -->
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'products.php') echo 'active'; ?>" href="/admin/pages/products.php">PRODUCT</a></li>
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'categories.php') echo 'active'; ?>" href="/admin/pages/categories.php">CATEGORY</a></li>

                <!-- Chương trình khuyến mãi -->
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'promotions.php') echo 'active'; ?>" href="/admin/pages/promotions.php">PROMOTIONS</a></li>
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'discount_codes.php') echo 'active'; ?>" href="/admin/pages/discount_codes.php">DISCOUNT</a></li>
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'reviews.php') echo 'active'; ?>" href="/admin/pages/reviews.php">REVIEWS</a></li>

                <!-- Đơn hàng và theo dõi -->
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'orders.php') echo 'active'; ?>" href="/admin/pages/orders.php">ORDERS</a></li>
                <li class="nav-item"><a class="nav-link <?php if ($current_page == 'login_attempts.php') echo 'active'; ?>" href="/admin/pages/login_attempts.php">ATTEMPTS</a></li>

                </ul>
                

                   <a href="/admin/pages/account.php"><button class="icon-btn position-relative"><i class="bi bi-person fs-4"></i>
                    </button>
                    <a href="/pages/login.php"><button class="icon-btn"><i class="bi bi-box-arrow-in-right fs-4"></i></button></a>
                </div>
            </div>
        </div>
    </nav>