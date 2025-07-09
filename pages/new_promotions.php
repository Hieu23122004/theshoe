<?php
require_once '../includes/database.php';
require_once '../includes/header.php';

// Lấy 5 bài viết mới nhất cho thanh bên
$recentPromotionsQuery = "SELECT post_id, title, image_url, created_at FROM promotions WHERE is_published = 1 ORDER BY created_at DESC LIMIT 5";
$recentPromotions = $conn->query($recentPromotionsQuery);

// Lấy toàn bộ bài viết đã xuất bản
$allPromotionsQuery = "SELECT post_id, title, excerpt, image_url, created_at FROM promotions WHERE is_published = 1 ORDER BY created_at DESC";
$allPromotions = $conn->query($allPromotionsQuery);
?>
<link rel="stylesheet" href="/assets/css/new_promotions.css">

<div class="container-fluid py-4 main-promotions-content bg-white">
  <div class="row">
    
    <!-- Sidebar: Bài viết gần đây -->
    <aside class="col-md-3">
      <div class="p-3 mb-4 bg-white rounded shadow-sm">
        <h3 class="fw-bold mb-3">Recent News</h3>
        <hr>
        <?php while ($article = $recentPromotions->fetch_assoc()): ?>
          <div class="d-flex align-items-start mb-3">
            <a href="promotions_detail.php?id=<?= $article['post_id'] ?>">
              <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="Thumbnail" class="rounded me-2" style="width:60px;height:60px;object-fit:cover;">
            </a>
            <div>
              <a href="promotions_detail.php?id=<?= $article['post_id'] ?>" class="text-dark fw-semibold text-decoration-none">
                <?= htmlspecialchars($article['title']) ?>
              </a>
              <div class="small text-muted"><?= date('d.m.Y', strtotime($article['created_at'])) ?></div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </aside>

    <!-- Main Content: Lưới khuyến mãi -->
    <section class="col-md-9">
      <h1 class="mb-4 fw-bold fs-4">Latest Updates from Mulgati</h1>
      <div class="row g-4">
        <?php while ($promo = $allPromotions->fetch_assoc()): ?>
          <div class="col-md-4 d-flex">
            <div class="card h-100 border-0 shadow-sm promotion-card">
              <a href="promotions_detail.php?id=<?= $promo['post_id'] ?>">
                <img src="<?= htmlspecialchars($promo['image_url']) ?>" class="card-img-top promotion-img-fixed" alt="Promotion Image">
              </a>
              <div class="card-body d-flex flex-column align-items-center">
                <a href="promotions_detail.php?id=<?= $promo['post_id'] ?>" class="card-title fw-bold text-dark mb-2 text-center promotion-title-fixed promotion-title-2line">
                  <?= htmlspecialchars($promo['title']) ?>
                </a>
                <div class="promotion-date-row w-100 d-flex align-items-center justify-content-center">
                  <span class="line flex-grow-1"></span>
                  <span class="badge promotion-date-fixed mx-2"><?= date('d/m/Y', strtotime($promo['created_at'])) ?></span>
                  <span class="line flex-grow-1"></span>
                </div>
                <p class="card-text text-muted text-center promotion-excerpt-fixed mt-2">
                  <?= htmlspecialchars(mb_strimwidth($promo['excerpt'], 0, 110, '...')) ?>
                </p>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </section>
    
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/floating_contact.php'; ?>
<script src="/assets/js/auto_logout.js"></script>
