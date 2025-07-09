<?php
include '../includes/header.php';
require_once '../includes/database.php';

// Lấy id từ URL
$promotion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$promotion = null;
$error = '';
if ($promotion_id > 0) {
  $stmt = $conn->prepare('SELECT * FROM promotions WHERE post_id = ? AND is_published = 1');
  $stmt->bind_param('i', $promotion_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $promotion = $result->fetch_assoc();
  } else {
    $error = 'Promotion not found or unpublished.';
  }
  $stmt->close();
} else {
  $error = 'Invalid promotion ID.';
}

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
    <link rel="stylesheet" href="/assets/css/promotions_detail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700,800&display=swap" rel="stylesheet">
    <div class="container-fluid py-4 introduction-main-bg">
      <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 col-md-3 mb-4">
          <div class="card shadow-sm mb-4">
            <div class="card-body p-3">
              <h5 class="sidebar-title mb-3">Categories</h5>
              <ul class="list-group list-group-flush">
                <li class="list-group-item"><a href="/pages/type_products.php" class="text-dark text-decoration-none">All Products</a></li>
                <li class="list-group-item"><a href="/pages/new_products.php" class="text-dark text-decoration-none">New Products</a></li>
                <li class="list-group-item"><a href="/pages/sale_products.php" class="text-dark text-decoration-none">Sale Products</a></li>
                <li class="list-group-item"><a href="/pages/belt_accessories.php" class="text-dark text-decoration-none">Belt Accessories</a></li>
                <li class="list-group-item"><a href="/pages/handbag_accessories.php" class="text-dark text-decoration-none">Handbag Accessories</a></li>
                <li class="list-group-item"><a href="/pages/detail_orders.php" class="text-dark text-decoration-none">Detail Order</a></li>
                <li class="list-group-item"><a href="/pages/introduction.php" class="text-dark text-decoration-none">Introduction</a></li>
                <li class="list-group-item"><a href="/pages/new_promotions.php" class="text-dark text-decoration-none">Promotions</a></li>
              </ul>
            </div>
          </div>
        </div>
        <!-- Main Content -->
        <div class="col-lg-10 col-md-9">
          <div class="intro-content-wrapper">
            <?php if ($error): ?>
              <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
            <?php elseif ($promotion): ?>
              <div class="intro-logo mb-3 text-center">
                <img src="<?= htmlspecialchars($promotion['image_url']) ?>" alt="Promotion Image" class="img-fluid rounded" style="max-height:350px;">
              </div>
              <h2 class="intro-title mb-3"> <?= htmlspecialchars($promotion['title']) ?> </h2>
              <div class="mb-2 text-muted" style="font-size:0.95em;">Created at: <?= date('d/m/Y H:i', strtotime($promotion['created_at'])) ?></div>
              <?php if (!empty($promotion['excerpt'])): ?>
                <div class="mb-3"><em><?= htmlspecialchars($promotion['excerpt']) ?></em></div>
              <?php endif; ?>
              <div class="intro-section">
                <?= $promotion['content'] ?>
              </div>
            <?php endif; ?>
          </div>
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


    <script src="https://kit.fontawesome.com/4e9c2b6e8b.js" crossorigin="anonymous"></script>
    <?php include '../includes/footer.php'; ?>

    <?php include '../includes/floating_contact.php'; ?>
    <script src="/assets/js/auto_logout.js"></script>