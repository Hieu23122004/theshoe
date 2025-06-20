<!-- includes/header.php -->
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - The Shoes' : 'The Shoes'; ?></title>
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">

</head>
<body>
    <!-- Top bar -->
    <div class="top-bar text-end px-4">
        THE SHOES STORE &nbsp;&nbsp;|&nbsp;&nbsp; Hotline: 1900 6868 &nbsp;&nbsp;|&nbsp;&nbsp; theshoe@gmail.com
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">
                <img src="/assets/images/logo.png" alt="The Shoes">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-3">
                    <li class="nav-item"><a class="nav-link <?php if($current_page == 'new_products.php') echo 'active'; ?>" href="/pages/new_products.php">NEW</a></li>
                    <li class="nav-item"><a class="nav-link <?php if($current_page == 'sale_products.php') echo 'active'; ?>" href="/pages/sale_products.php">SALE</a></li>
                    <li class="nav-item"><a class="nav-link <?php if($current_page == 'type_products.php') echo 'active'; ?>" href="/pages/type_products.php">MEN'S SHOES</a></li>
                    <li class="nav-item"><a class="nav-link <?php if($current_page == 'type_accessories.php') echo 'active'; ?>" href="/pages/type_accessories.php">ACCESSORIES</a></li>
                    <li class="nav-item"><a class="nav-link <?php if($current_page == 'introduction.php') echo 'active'; ?>" href="/pages/introduction.php">INTRODUCTION</a></li>
                    <li class="nav-item"><a class="nav-link <?php if($current_page == 'new_promotions.php') echo 'active'; ?>" href="/pages/new_promotions.php">PROMOTIONS</a></li>
                </ul>
                <div class="d-flex align-items-center position-relative">
                    <button class="icon-btn"><i class="bi bi-search fs-5"></i></button>
                    <button class="icon-btn"><i class="bi bi-cart fs-5"></i></button>
                    <button class="icon-btn"><i class="bi bi-heart fs-5"></i></button>
                    <a href="/pages/register.php"><button class="icon-btn"><i class="bi bi-box-arrow-in-right fs-4"></i></button></a>
                </div>
            </div>
        </div>
    </nav>
