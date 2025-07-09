<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Promotion Detail</title>
  <link rel="stylesheet" href="/assets/css/home.css">
  <link rel="stylesheet" href="/assets/css/promotions_detail.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700,800&display=swap" rel="stylesheet">
</head>
<body>
  <div class="container-fluid py-4 main-promotions-content" style="background:#fff;">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-lg-2 col-md-3 mb-4">
        <div class="card shadow-sm mb-4">
          <div class="card-body p-3">
            <h5 class="sidebar-title mb-3">Categories</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><a href="/pages/type_products.html" class="text-dark text-decoration-none">All Products</a></li>
              <li class="list-group-item"><a href="/pages/new_products.html" class="text-dark text-decoration-none">New Products</a></li>
              <li class="list-group-item"><a href="/pages/sale_products.html" class="text-dark text-decoration-none">Sale Products</a></li>
              <li class="list-group-item"><a href="/pages/belt_accessories.html" class="text-dark text-decoration-none">Belt Accessories</a></li>
              <li class="list-group-item"><a href="/pages/handbag_accessories.html" class="text-dark text-decoration-none">Handbag Accessories</a></li>
              <li class="list-group-item"><a href="/pages/detail_orders.html" class="text-dark text-decoration-none">Detail Order</a></li>
              <li class="list-group-item"><a href="/pages/introduction.html" class="text-dark text-decoration-none">Introduction</a></li>
              <li class="list-group-item"><a href="/pages/new_promotions.html" class="text-dark text-decoration-none">Promotions</a></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="col-lg-10 col-md-9">
        <div class="intro-content-wrapper">
          <!-- Nếu có lỗi -->
          <!-- <div class="alert alert-danger">Promotion not found or unpublished.</div> -->

          <!-- Nếu có promotion -->
          <div class="intro-logo mb-3 text-center">
            <img src="/uploads/promo_image.jpg" alt="Promotion Image" class="img-fluid rounded" style="max-height:350px;">
          </div>
          <h2 class="intro-title mb-3">Mulgati Summer Sale 2025</h2>
          <div class="mb-2 text-muted" style="font-size:0.95em;">Created at: 09/07/2025 21:30</div>
          <div class="mb-3"><em>Get ready for a sizzling summer with up to 35% off all items!</em></div>
          <div class="intro-section">
            <p>Join us this season for a special treat! Discover our latest collection of stylish shoes and accessories with unbeatable summer discounts. This is your chance to upgrade your wardrobe with premium items at low prices.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- News Marquee -->
  <div class="container my-3">
    <h2 class="text-center fw-bold mb-4" style="font-size:1.5rem;">New news about Mulgati</h2>
    <div class="d-flex justify-content-center">
      <div class="row w-100 justify-content-center" style="max-width:1200px;overflow:hidden;" id="newsRow">
        <div class="news-marquee-wrapper d-flex flex-nowrap">
          <!-- Bài viết 1 -->
          <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex justify-content-center news-card-item">
            <div class="card promotion-card shadow-sm border-0" style="width: 100%; max-width: 270px; text-align:center; cursor:pointer;">
              <a href="promotions_detail.html">
                <img src="/uploads/news1.jpg" class="card-img-top" style="height:160px;object-fit:cover;">
              </a>
              <div class="card-body">
                <a href="promotions_detail.html"
                  class="fw-bold text-dark d-block mb-2 news-title-2line"
                  style="font-size:1rem; text-transform:uppercase; text-decoration:underline;">
                  Summer Flash Sale!
                </a>
                <div>
                  <span class="badge promotion-date-fixed" style="font-size:0.95rem; padding:6px 18px; border-radius:20px; background:#fff; border:1px solid #e0e0e0; font-weight:600;">
                    08/07/2025
                  </span>
                </div>
                <div class="card-text text-muted mt-2 news-excerpt-2line" style="font-size:0.98rem;">
                  Shop now and get the biggest deals of the year.
                </div>
              </div>
            </div>
          </div>

          <!-- Duplicate this block for more news items... -->
        </div>
      </div>
    </div>
  </div>

  <script src="https://kit.fontawesome.com/4e9c2b6e8b.js" crossorigin="anonymous"></script>
  <script src="/assets/js/auto_logout.js"></script>
</body>
</html>
