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
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- AOS Animation Library -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/promotions.css">

<!-- Hero Banner Section -->
<section class="promotions-hero-banner" style="margin-top: 50px;">
  <div class="hero-overlay">
    <div class="container">
      <div class="row align-items-center min-vh-50">
        <div class="col-lg-8 mx-auto text-center text-white">
          <h1 class="hero-title mb-3" data-aos="fade-up" data-aos-duration="1000">
            MULGATI NEWS
          </h1>
          <p class="hero-subtitle mb-3" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
            Latest Updates & Promotions
          </p>
          <p class="hero-description mb-3" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
            Stay updated with our latest news, promotions, and exclusive offers from Mulgati
          </p>
          <div data-aos="fade-up" data-aos-delay="600" data-aos-duration="000">
            <a href="/pages/new_products.php" class="btn btn-luxury btn-lg me-3 mb-3">
              <i class="bi bi-star me-2"></i>New Products
            </a>
            <a href="#articles" class="btn btn-outline-light btn-lg mb-3">
              <i class="bi bi-newspaper me-2"></i>View Articles
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<div class="container-fluid py-4 main-promotions-content" id="articles" style="background:#fff;">
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
<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  // Initialize AOS
  document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
      duration: 1000,
      once: true,
      offset: 100,
      delay: 0,
      easing: 'ease-out-cubic'
    });
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
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
</script>