<?php
include '../includes/database.php';
include '../includes/header.php';
$latest_sql = "SELECT post_id, title, image_url, created_at FROM promotions WHERE is_published = 1 ORDER BY created_at DESC LIMIT 5";
$latest_result = $conn->query($latest_sql);
$main_sql = "SELECT post_id, title, excerpt, image_url, created_at FROM promotions WHERE is_published = 1 ORDER BY created_at DESC";
$main_result = $conn->query($main_sql);
?>
<!-- Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/new_promotions.css">
<div class="container-fluid py-4 main-promotions-content" style="background:#fff;">
  <div class="row">
    <!-- Sidebar Latest Articles -->
    <div class="col-md-3">
      <div class="latest-articles-box p-3 mb-4 bg-white rounded shadow-sm">
        <h3 class="mb-3 fw-bold">Latest Articles</h3>
        <hr>
        <?php while ($row = $latest_result->fetch_assoc()): ?>
          <div class="d-flex mb-3 align-items-start latest-article-item">
            <a href="promotions_detail.php?id=<?= $row['post_id'] ?>">
              <img src="<?= htmlspecialchars($row['image_url']) ?>" class="rounded me-2" style="width:60px;height:60px;object-fit:cover;">
            </a>
            <div>
              <a href="promotions_detail.php?id=<?= $row['post_id'] ?>" class="fw-semibold text-dark latest-article-title" style="text-decoration:none;">
                <?= htmlspecialchars($row['title']) ?>
              </a>
              <div class="text-muted small"><?= date('d.m.Y', strtotime($row['created_at'])) ?></div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
    <!-- Main Promotions Grid -->
    <div class="col-md-9">
      <h1 class="mb-4 fw-bold" style="font-size:1.5rem;">New news about Mulgati</h1>
      <div class="row g-4 d-flex justify-content-start">
        <?php while ($row = $main_result->fetch_assoc()): ?>
          <div class="col-md-4 d-flex justify-content-center">
            <div class="card promotion-card h-100 shadow-sm border-0">
              <a href="promotions_detail.php?id=<?= $row['post_id'] ?>">
                <img src="<?= htmlspecialchars($row['image_url']) ?>" class="card-img-top promotion-img-fixed" alt="" />
              </a>
              <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <a href="promotions_detail.php?id=<?= $row['post_id'] ?>" class="card-title fw-bold text-dark d-block mb-2 promotion-title-fixed promotion-title-2line"><?= htmlspecialchars($row['title']) ?></a>
                <div class="promotion-date-row w-100">
                  <span class="line"></span>
                  <span class="badge promotion-date-fixed"><?= date('d/m/Y', strtotime($row['created_at'])) ?></span>
                  <span class="line"></span>
                </div>
                <div class="card-text text-muted promotion-excerpt-fixed">
                  <?= htmlspecialchars(mb_strimwidth($row['excerpt'], 0, 110, '...')) ?>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/floating_contact.php'; ?>
<script src="/assets/js/auto_logout.js"></script>
<!-- Bootstrap 5 JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>