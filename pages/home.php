<?php
include '../includes/database.php';
include '../includes/header.php';

// Fetch latest 5 articles for sidebar
$latest_sql = "SELECT post_id, title, image_url, created_at FROM promotions WHERE is_published = 1 ORDER BY created_at DESC";
$latest_result = $conn->query($latest_sql);

// Fetch all promotions for main grid
$main_sql = "SELECT post_id, title, excerpt, image_url, created_at FROM promotions WHERE is_published = 1 ORDER BY created_at DESC";
$main_result = $conn->query($main_sql);
?>
<link rel="stylesheet" href="/assets/css/home.css">
<div class="container-fluid py-4 main-promotions-content" style="background:#fff;">
    <div class="row">


        <?php

        // Lấy tất cả bài báo mới nhất (để hiệu ứng chuyển tiếp hoạt động đúng)
        $news_sql = "SELECT post_id, title, image_url, created_at, excerpt FROM promotions WHERE is_published = 1 ORDER BY created_at DESC";
        $news_result = $conn->query($news_sql);
        $newsList = [];
        if ($news_result && $news_result->num_rows > 0) {
            while ($row = $news_result->fetch_assoc()) {
                $newsList[] = $row;
            }
        }

        // Lấy sản phẩm nổi bật
        $featured_sql = "SELECT product_id, name, description, price, image_url FROM products WHERE is_featured = 1";
        $featured_result = $conn->query($featured_sql);
        $featuredList = [];
        if ($featured_result && $featured_result->num_rows > 0) {
            while ($row = $featured_result->fetch_assoc()) {
                $featuredList[] = $row;
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <script src="/assets/js/home.js"></script>
            <link rel="stylesheet" href="/assets/css/home.css">
            <!-- Bootstrap 5 CSS -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


            <title>home</title>
        </head>

        <body>
            <!-- Banner Carousel sát viền, không bị padding -->
            <div id="bannerCarousel" class="carousel slide container-fluid p-0" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="/assets/images/hom1.jpeg" class="d-block w-100" alt="Banner 1">
                    </div>
                    <div class="carousel-item">
                        <img src="/assets/images/hom2.jpeg" class="d-block w-100" alt="Banner 2">
                    </div>
                </div>
            </div>

            <!-- Sản phẩm nổi bật marquee -->
            <div class="container my-3">
                <h2 class="text-center fw-bold mb-4" style="font-size:1.5rem;">Featured Products</h2>
                <div class="d-flex justify-content-center">
                    <div class="row w-100 justify-content-center" style="max-width:1200px;overflow:hidden;" id="featuredRow">
                        <div class="news-marquee-wrapper">
                            <?php foreach ($featuredList as $product): ?>
                                <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex justify-content-center news-card-item">
                                    <a class="product-link w-100 h-100" href="detail_products.php?id=<?= $product['product_id'] ?>" style="text-decoration:none;color:inherit;">
                                        <div class="featured-product-card">
                                            <?php if (!empty($product['discount_percent']) && $product['discount_percent'] > 0): ?>
                                                <span class="featured-discount-badge">-<?= (int)$product['discount_percent'] ?>%</span>
                                            <?php endif; ?>
                                            <div class="featured-product-image-container">
                                                <img src="<?= htmlspecialchars($product['image_url']) ?>" class="featured-product-img" alt="<?= htmlspecialchars($product['name']) ?>">
                                            </div>
                                            <div class="card-body">
                                                <?php if (!empty($product['material'])): ?>
                                                    <span class="featured-product-brand"><?= htmlspecialchars($product['material']) ?></span>
                                                <?php endif; ?>
                                                <div class="featured-product-title">
                                                    <?= htmlspecialchars($product['name']) ?>
                                                </div>
                                                <div class="card-text text-muted mt-2 news-excerpt-2line" style="font-size:0.98rem;">
                                                    <?= htmlspecialchars($product['description']) ?>
                                                </div>
                                                <div class="featured-price-container mt-2">
                                                    <span class="featured-product-price"><?= number_format($product['price'], 0, ',', '.') ?>₫</span>
                                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                                        <span class="featured-product-original"><?= number_format($product['original_price'], 0, ',', '.') ?>₫</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($featuredList as $product): ?>
                                <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex justify-content-center news-card-item">
                                    <a class="product-link w-100 h-100" href="detail_products.php?id=<?= $product['product_id'] ?>" style="text-decoration:none;color:inherit;">
                                        <div class="featured-product-card">
                                            <?php if (!empty($product['discount_percent']) && $product['discount_percent'] > 0): ?>
                                                <span class="featured-discount-badge">-<?= (int)$product['discount_percent'] ?>%</span>
                                            <?php endif; ?>
                                            <div class="featured-product-image-container">
                                                <img src="<?= htmlspecialchars($product['image_url']) ?>" class="featured-product-img" alt="<?= htmlspecialchars($product['name']) ?>">
                                            </div>
                                            <div class="card-body">
                                                <?php if (!empty($product['material'])): ?>
                                                    <span class="featured-product-brand"><?= htmlspecialchars($product['material']) ?></span>
                                                <?php endif; ?>
                                                <div class="featured-product-title">
                                                    <?= htmlspecialchars($product['name']) ?>
                                                </div>
                                                <div class="card-text text-muted mt-2 news-excerpt-2line" style="font-size:0.98rem;">
                                                    <?= htmlspecialchars($product['description']) ?>
                                                </div>
                                                <div class="featured-price-container mt-2">
                                                    <span class="featured-product-price"><?= number_format($product['price'], 0, ',', '.') ?>₫</span>
                                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                                        <span class="featured-product-original"><?= number_format($product['original_price'], 0, ',', '.') ?>₫</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Block 2 cột: Bên trái là chữ, bên phải là ảnh (Bootstrap chuẩn, sát nhau tuyệt đối) -->
            <div class="container my-2" style="margin-bottom:10px !important; margin-top:10px !important;">
                <div class="row align-items-center g-0" style="background:#f8f9fa;overflow:hidden;">
                    <div class="col-12 col-md-6 px-4 py-4 d-flex flex-column justify-content-center align-items-center text-center">
                        <span class="follow-title" style="font-size:1.5rem;font-weight:700;letter-spacing:1px;">FOLLOW MULGATI FOR NEW PRODUCT</span>
                        <span class="follow-title mt-2" style="font-size:1.3rem;font-weight:700;">AND GET COUPON</span>
                    </div>
                    <div class="col-12 col-md-6 p-0">
                        <img src="https://scontent.fhan14-2.fna.fbcdn.net/v/t39.30808-6/316293206_113733768226071_7477217734953569236_n.png?_nc_cat=111&ccb=1-7&_nc_sid=cc71e4&_nc_ohc=e-dFnh-4QQUQ7kNvwG4Ay-f&_nc_oc=Admiek7aYDNRdLVwykCByEahn95eQbfEgZTnP1DJF5XSptvO07H-7rwdo7iMfSVSz7I&_nc_zt=23&_nc_ht=scontent.fhan14-2.fna&_nc_gid=KvfDJsSNgRkLCfgOZ0nwfw&oh=00_AfSXno_xO4LwJyw-SijakudSM5EkDGQwrGdrkhl6TUOPMA&oe=68727AB5" alt="Mulgati Store" class="img-fluid w-100" style="max-height:320px;object-fit:cover;">
                    </div>
                </div>
            </div>

            <div class="container my-3">
                <h2 class="text-center fw-bold mb-4" style="font-size:1.5rem;">New news about Mulgati</h2>
                <div class="d-flex justify-content-center">
                    <div class="row w-100 justify-content-center" style="max-width:1200px;overflow:hidden;" id="newsRow">
                        <div class="news-marquee-wrapper">
                            <?php foreach ($newsList as $news): ?>
                                <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex justify-content-center news-card-item">
                                    <div class="card promotion-card shadow-sm border-0" style="width: 100%; max-width: 270px; text-align:center; cursor:pointer;">
                                        <a href="promotions_detail.php?id=<?= $news['post_id'] ?>">
                                            <img src="<?= htmlspecialchars($news['image_url']) ?>" class="card-img-top" style="height:160px;object-fit:cover;">
                                        </a>
                                        <div class="card-body">
                                            <a href="promotions_detail.php?id=<?= $news['post_id'] ?>"
                                                class="fw-bold text-dark d-block mb-2 news-title-2line"
                                                style="font-size:1rem; text-transform:uppercase; text-decoration:underline;">
                                                <?= htmlspecialchars($news['title']) ?>
                                            </a>
                                            <div>
                                                <span class="badge promotion-date-fixed" style="font-size:0.95rem; padding:6px 18px; border-radius:20px; background:#fff; border:1px solid #e0e0e0; font-weight:600;">
                                                    <?= date('d/m/Y', strtotime($news['created_at'])) ?>
                                                </span>
                                            </div>
                                            <div class="card-text text-muted mt-2 news-excerpt-2line" style="font-size:0.98rem;">
                                                <?= htmlspecialchars($news['excerpt']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($newsList as $news): ?>
                                <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex justify-content-center news-card-item">
                                    <div class="card promotion-card shadow-sm border-0" style="width: 100%; max-width: 270px; text-align:center; cursor:pointer;">
                                        <a href="promotions_detail.php?id=<?= $news['post_id'] ?>">
                                            <img src="<?= htmlspecialchars($news['image_url']) ?>" class="card-img-top" style="height:160px;object-fit:cover;">
                                        </a>
                                        <div class="card-body">
                                            <a href="promotions_detail.php?id=<?= $news['post_id'] ?>"
                                                class="fw-bold text-dark d-block mb-2 news-title-2line"
                                                style="font-size:1rem; text-transform:uppercase; text-decoration:underline;">
                                                <?= htmlspecialchars($news['title']) ?>
                                            </a>
                                            <div>
                                                <span class="badge promotion-date-fixed" style="font-size:0.95rem; padding:6px 18px; border-radius:20px; background:#fff; border:1px solid #e0e0e0; font-weight:600;">
                                                    <?= date('d/m/Y', strtotime($news['created_at'])) ?>
                                                </span>
                                            </div>
                                            <div class="card-text text-muted mt-2 news-excerpt-2line" style="font-size:0.98rem;">
                                                <?= htmlspecialchars($news['excerpt']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>


        </body>
        <!-- Bootstrap 5 JS Bundle (with Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </html>
        <?php include '../includes/truck.php'; ?>
        <?php include '../includes/footer.php'; ?>
        <?php include '../includes/floating_contact.php'; ?>
        <script src="/assets/js/auto_logout.js"></script>