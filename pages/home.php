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
            <!-- Bootstrap Icons -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">


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
                        <img src="/assets/images/hom1.jpeg" class="d-block w-100" alt="Banner 2">
                    </div>
                </div>
            </div>

            <!-- Sản phẩm nổi bật marquee -->
            <div class="container-fluid my-3">
                <h2 class="text-center fw-bold mb-4" style="font-size:1.5rem; color:#000;">Featured Products</h2>
                <div class="w-100" style="overflow:hidden;">
                    <div class="row w-100 justify-content-center" style="overflow:hidden;" id="featuredRow">
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
            <div class="container-fluid my-2" style="margin-bottom:10px !important; margin-top:10px !important;">
                <div class="row align-items-center g-0" style="background:#f8f9fa;overflow:hidden;">
                    <div class="col-12 col-md-6 px-4 py-4 d-flex flex-column justify-content-center align-items-center text-center">
                        <span class="follow-title" style="font-size:1.5rem;font-weight:700;letter-spacing:1px;">FOLLOW MULGATI FOR NEW PRODUCT</span>
                        <span class="follow-title mt-2" style="font-size:1.3rem;font-weight:700;">AND GET COUPON</span>
                    </div>
                    <div class="col-12 col-md-6 p-0">
                        <img src="/assets/images/qc.jpg" alt="Mulgati Store" class="img-fluid w-100" style="max-height:320px;object-fit:cover;">
                    </div>
                </div>
            </div>


            <!-- Brand Story Section -->
            <div class="brand-story-section" style="background: linear-gradient(135deg, #8c7e71 0%, #8c7e71 100%); margin:30px 0;">
                <div class="container-fluid py-5 brand-story-content">
                    <div class="row align-items-center text-white">
                        <div class="col-md-6">
                            <h2 class="fw-bold mb-3 fade-in-up" style="font-size:2rem;">The Mulgati Legacy</h2>
                            <p class="mb-4 fade-in-up" style="font-size:1.1rem; line-height:1.6; animation-delay:0.2s;">
                                From the heart of Russia comes a brand that embodies sophistication, craftsmanship, and timeless elegance. 
                                Every Mulgati shoe tells a story of passion, precision, and the pursuit of perfection.
                            </p>
                            <p class="mb-4 fade-in-up" style="opacity:0.9; animation-delay:0.3s;">
                                "Every step tells a story — a journey of craftsmanship, passion, and timeless elegance."
                            </p>
                            <div class="text-left" style="padding-left: 10px; position: relative; z-index: 1000;">
                                <a href="/pages/introduction.php" class="btn btn-outline-light btn-lg" 
                                   style="padding: 10px 25px; font-weight: 600; border: 2px solid #fff; color: #fff; text-decoration: none; border-radius: 8px; white-space: nowrap; position: relative; z-index: 1001; pointer-events: auto;">
                                    learn more
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 text-center">
                            <div class="brand-story-emoji" style="font-size:10rem; opacity:0.3;"><img src="/assets/images/homqc.png" alt="" style="width:300px; height:300px;" ></div>
                        </div>
                    </div>
                </div>
            </div>

    
            <div class="container my-3">
                <h2 class="fw-bold mb-4 section-title fade-in-up text-center" style="font-size:1.5rem; color:#000;">New news about Mulgati</h2>
                <div class="w-100" style="overflow:hidden;">
                    <div class="row w-100 justify-content-center" style="overflow:hidden;" id="newsRow">
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


            <!-- Bootstrap 5 JS Bundle (with Popper) -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <!-- Custom JavaScript -->
            <script src="/assets/js/home.js"></script>
            
            <!-- Enhanced Animation Script -->
            <script>
                // Intersection Observer for animations
                const animateElements = document.querySelectorAll('.fade-in-up');
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.style.animationPlayState = 'running';
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });

                animateElements.forEach((el) => {
                    observer.observe(el);
                });

                // Enhanced carousel auto-play with better UX
                const carousel = document.querySelector('#bannerCarousel');
                if (carousel) {
                    const bsCarousel = new bootstrap.Carousel(carousel, {
                        interval: 4000,
                        ride: 'carousel',
                        pause: 'hover'
                    });
                }

                // Add smooth scroll behavior
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                });

                // Enhanced hover effects for product cards
                document.querySelectorAll('.featured-product-card, .promotion-card').forEach(card => {
                    card.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-8px) scale(1.02)';
                        this.style.boxShadow = '0 20px 40px rgba(139, 69, 19, 0.25)';
                    });
                    
                    card.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0) scale(1)';
                        this.style.boxShadow = '';
                    });
                });
            </script>

            <?php include '../includes/truck.php'; ?>
            <?php include '../includes/floating_contact.php'; ?>
            <script src="/assets/js/auto_logout.js"></script>
            <?php include '../includes/footer.php'; ?>