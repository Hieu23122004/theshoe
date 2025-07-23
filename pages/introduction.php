<?php include '../includes/header.php'; ?>
<!-- Preload Critical Resources -->
<link rel="preload" href="/assets/css/introduction.css" as="style">
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" as="style">
<link rel="preload" href="/assets/images/access1.jpg" as="image">

<link rel="stylesheet" href="/assets/css/introduction.css">
<!-- Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- AOS Animation Library -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<!-- Hero Banner Section -->
<section class="hero-banner" style="margin-top: 50px;" >
  <div class="hero-overlay">
    <div class="container">
      <div class="row align-items-center min-vh-100">
        <div class="col-lg-8 mx-auto text-center text-white">
          <h1 class="hero-title mb-4" data-aos="fade-up" data-aos-duration="1000">
            MULGATI
          </h1>
          <p class="hero-subtitle mb-4" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
            Premium Leather Shoes Brand from Russia
          </p>
          <p class="hero-description mb-5" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
            Discover the perfect blend of Russian craftsmanship and timeless elegance in every step
          </p>
          <div data-aos="fade-up" data-aos-delay="600" data-aos-duration="1000">
            <a href="/pages/type_products.php" class="btn btn-luxury btn-lg me-3 mb-3">
              <i class="bi bi-bag-heart me-2"></i>Shop Collection
            </a>
            <a href="#story" class="btn btn-outline-light btn-lg mb-3">
              <i class="bi bi-book me-2"></i>Our Story
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container-fluid py-5 introduction-main-bg">
  <div class="row">
    <!-- Enhanced Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4">
      <div class="sidebar-card" data-aos="fade-right" data-aos-duration="800">
        <div class="card-body p-4">
          <h5 class="sidebar-title mb-4">
            <i class="bi bi-grid-3x3-gap me-2"></i>Categories
          </h5>
          <ul class="modern-nav-list">
          <li class="nav-item">
              <a href="/pages/home.php" class="nav-link">
                <i class="bi bi-house me-3"></i>Home
              </a>
            </li> 
          <li class="nav-item">
              <a href="/pages/type_products.php" class="nav-link">
                <i class="bi bi-collection me-3"></i>All Products
              </a>
            </li>
            <li class="nav-item">
              <a href="/pages/new_products.php" class="nav-link">
                <i class="bi bi-star me-3"></i>New Products
              </a>
            </li>
            <li class="nav-item">
              <a href="/pages/sale_products.php" class="nav-link">
                <i class="bi bi-percent me-3"></i>Sale Products
              </a>
            </li>
            <li class="nav-item">
              <a href="/pages/belt_accessories.php" class="nav-link">
                <i class="bi bi-circle me-3"></i>Belt Accessories
              </a>
            </li>
            <li class="nav-item">
              <a href="/pages/handbag_accessories.php" class="nav-link">
                <i class="bi bi-handbag me-3"></i>Handbag Accessories
              </a>
            </li>
            <li class="nav-item">
              <a href="/pages/detail_orders.php" class="nav-link">
                <i class="bi bi-receipt me-3"></i>Detail Order
              </a>
            </li>
            <li class="nav-item ">
              <a href="/pages/introduction.php" class="nav-link">
                <i class="bi bi-info-circle me-3"></i>Introduction
              </a>
            </li>
            <li class="nav-item">
              <a href="/pages/new_promotions.php" class="nav-link">
                <i class="bi bi-gift me-3"></i>Promotions
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Quick Contact Card -->
      <div class="sidebar-card mt-4" data-aos="fade-right" data-aos-delay="200" data-aos-duration="800">
        <div class="card-body p-4">
          <h6 class="sidebar-title mb-3">
            <i class="bi bi-telephone me-2"></i>Quick Contact
          </h6>
          <div class="quick-contact">
            <a href="tel:19006868" class="contact-item">
              <i class="bi bi-telephone-fill"></i>
              <span>1900 6868</span>
            </a>
            <a href="mailto:theshoe@gmail.com" class="contact-item">
              <i class="bi bi-envelope-fill"></i>
              <span>theshoe@gmail.com</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9 col-md-8">
      <!-- Story Section -->
      <section id="story" class="content-section" data-aos="fade-up" data-aos-duration="800">
        <div class="section-header text-center mb-4">
          <h2 class="section-title">Our Story</h2>
          <div class="title-divider"></div>
          <p class="section-subtitle">Every step tells a story — a journey of craftsmanship, passion, and timeless elegance.</p>
        </div>

        <div class="row align-items-center mb-4">
          <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
            <div class="story-content">
              <h3 class="story-heading">How was Mulgati founded?</h3>
              <p class="story-text">"I can’t quite recall when my love for shoes began but one day I realized that every time I stepped outside, my eyes were drawn to what men wore on their feet." Fueled by passion and a deep understanding of the Russian fashion scene, <strong>Mulgati</strong> was born, embodying the essence of royal heritage: strength, elegance, and refined masculinity in every design.</p>

              <p class="story-text">That’s why every Mulgati pair is more than just footwear — it’s a statement of class, a symbol of confidence, and a reflection of the unique soul of every true gentleman.</p>
            </div>
          </div>
          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="400">
            <div class="story-image">
              <img src="/assets/images/intro1.jpg" alt="Mulgati Founder" class="img-fluid rounded-lg shadow-lg">
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- Full Width Philosophy Section -->
<div class="container-fluid py-5" style="background: linear-gradient(to bottom, #faf9f7, #f5f3f0);">
  <div class="container-fluid">
    <!-- Philosophy Section -->
    <section class="content-section" data-aos="fade-up" data-aos-duration="800">
      <div class="philosophy-card">
        <div class="row">
          <div class="col-lg-8">
            <h3 class="card-title">Our Philosophy</h3>
            <p class="card-text">With the philosophy of "putting customers at the heart of every activity," every Mulgati product is meticulously invested in, and the shopping experience as well as after-sales services will satisfy every customer.</p>
            <p class="card-text">With a symbol of a faceted diamond stylized from the letters M (Manners) - G (Gentleman) - T (Timeless), these are also the three qualities every true gentleman aspires to—strength, power, and class.</p>
          </div>
          <div class="col-lg-4 text-center">
            <div class="brand-symbol">
              <div class="mulgati-logo" style="display: flex; flex-direction: column; align-items: center;">
                <svg viewBox="0 0 100 100" fill="none" stroke="#DAA520" stroke-width="5" stroke-linejoin="round" style="width:80px;height:80px;margin-bottom:1rem;">
                  <path d="M10 50 L30 20 L70 20 L90 50 L50 90 Z" />
                  <path d="M30 55 L40 35 L50 55 L60 35 L70 55" />
                  <path d="M60 65 Q50 75 40 65 L40 60 L50 60 L50 68" />
                </svg>
                <span style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;color:var(--text-primary);letter-spacing:2px;">
                  MULGATI<sup style="font-size:0.7em;">®</sup>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- What Makes Us Different -->
    <section class="content-section" data-aos="fade-up" data-aos-duration="800">
      <div class="row">
        <div class="col-lg-6 order-lg-2" data-aos="fade-left" data-aos-delay="200">
          <div class="feature-image">
            <img src="/assets/images/intro2.jpg" alt="Mulgati Craftsmanship" class="img-fluid rounded-lg shadow-lg" >
          </div>
        </div>
        <div class="col-lg-6 order-lg-1" data-aos="fade-right" data-aos-delay="400">
          <div class="feature-content">
            <h3 class="feature-title">What makes Mulgati products different?</h3>
            <p class="feature-text">Throughout its development, Mulgati has focused mainly on premium leather shoes, constantly exploring, learning, and researching to bring customers the best quality products. Every pair of Mulgati leather shoes is made from 100% genuine leather with meticulous attention to every stitch.</p>
            <p class="feature-text">That's why the leather shoes here have a natural shine, are sturdy, and remain beautiful over time. With high-quality materials and the skillful hands of experienced craftsmen, the final products not only meet quality standards but also fit the feet of Vietnamese people.</p>
            <p class="feature-text">Every pair of Mulgati shoes is more than just footwear — it's a fusion of artisanal craftsmanship and the modern gentleman’s lifestyle philosophy. From carefully selected leathers to each stitch executed by skilled hands.</p>

            <p class="feature-text">Rather than chasing fleeting trends, Mulgati focuses on timeless elegance — where durability, refinement, and style converge to create a product that not only complements your wardrobe, but defines your presence.</p>

          </div>
        </div>
      </div>
    </section>

    <!-- Vision & Mission Grid -->
    <section class="content-section">
      <div class="row g-4">
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
          <div class="vision-mission-card h-100">
            <div class="card-icon">
              <i class="bi bi-eye"></i>
            </div>
            <h4 class="card-title">Brand Vision</h4>
            <p class="card-text">Mulgati aims to become the number one leather shoe brand in Vietnam, offering the best quality products with the style of an elegant, luxurious, and trendy gentleman.</p>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="400">
          <div class="vision-mission-card h-100">
            <div class="card-icon">
              <i class="bi bi-bullseye"></i>
            </div>
            <h4 class="card-title">Mulgati's Mission</h4>
            <p class="card-text">"A good pair of shoes will take you to wonderful places." Mulgati will always strive to improve product quality, services, and put our heart into bringing real value to every customer.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Core Values -->
    <section class="content-section" data-aos="fade-up" data-aos-duration="800">
      <div class="values-card">
        <h3 class="section-title text-center mb-4">Core Values</h3>
        <p class="text-center mb-4">Throughout its brand development journey, Mulgati always puts absolute customer satisfaction at the center of all business activities. We always strive to understand and listen to customers to create stylish, trendy, and quality product lines for our valued customers.</p>
      </div>
    </section>

    <!-- Why Choose Us -->
    <section class="content-section" data-aos="fade-up" data-aos-duration="800">
      <h3 class="section-title text-center mb-5">Why choose Mulgati?</h3>
      <div class="row g-4">
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
          <div class="feature-item">
            <div class="feature-icon">
              <i class="bi bi-palette"></i>
            </div>
            <div class="feature-info">
              <h5>Minimalist design with timeless beauty</h5>
              <p>Clean lines and classic aesthetics that never go out of style</p>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
          <div class="feature-item">
            <div class="feature-icon">
              <i class="bi bi-award"></i>
            </div>
            <div class="feature-info">
              <h5>Premium leather material</h5>
              <p>100% genuine leather sourced from the finest suppliers</p>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="400">
          <div class="feature-item">
            <div class="feature-icon">
              <i class="bi bi-shield-check"></i>
            </div>
            <div class="feature-info">
              <h5>Superior comfort & durability</h5>
              <p>Soft, lightweight, waterproof, and super comfortable soles</p>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="500">
          <div class="feature-item">
            <div class="feature-icon">
              <i class="bi bi-brush"></i>
            </div>
            <div class="feature-info">
              <h5>Versatile styling</h5>
              <p>Elegant, masculine colors, easy to mix and match</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- Contact Section with Map -->
<section class="contact-section py-5" data-aos="fade-up" data-aos-duration="800">
  <div class="container">
    <div class="row">
      <div class="col-lg-8 mx-auto text-center mb-5">
        <h2 class="section-title text-white">Get In Touch</h2>
        <div class="title-divider title-divider-light"></div>
        <p class="section-subtitle text-light">Visit our stores or contact us for personalized service</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
        <div class="contact-info">
          <h4 class="contact-title">Store Locations</h4>
          <div class="store-list">
            <div class="store-item">
              <i class="bi bi-geo-alt-fill"></i>
              <div class="store-details">
                <h6>Ha Dong, Hanoi</h6>
                <p>T102 - The Shoes Ha Dong</p>
              </div>
            </div>
            <div class="store-item">
              <i class="bi bi-geo-alt-fill"></i>
              <div class="store-details">
                <h6>Long Bien, Hanoi</h6>
                <p>T243 - The Shoes Long Bien</p>
              </div>
            </div>
            <div class="store-item">
              <i class="bi bi-geo-alt-fill"></i>
              <div class="store-details">
                <h6>Hai Phong Le Chan</h6>
                <p>T240 - The Shoes Hai Phong</p>
              </div>
            </div>
            <div class="store-item">
              <i class="bi bi-geo-alt-fill"></i>
              <div class="store-details">
                <h6>Dong Ha, Quang Tri</h6>
                <p>L1-08 - The Shoes Dong Ha</p>
              </div>
            </div>
            <div class="store-item">
              <i class="bi bi-geo-alt-fill"></i>
              <div class="store-details">
                <h6>Vincom Da Nang</h6>
                <p>L2-05A - The Shoes Vincom</p>
              </div>
            </div>
          </div>

        </div>
      </div>

      <div class="col-lg-6" data-aos="fade-left" data-aos-delay="400">
        <div class="map-container">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3723.8639810448!2d105.748634!3d20.9903367!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x313453f1b9e9cd79%3A0x36f7fa1f6c6ff368!2sGi%C3%A0y%20da%20nam%20Mulgati%20Aeon%20Mall%20H%C3%A0%20%C4%90%C3%B4ng!5e0!3m2!1sen!2s!4v1642678901234!5m2!1sen!2s"
            width="100%"
            height="580"
            style="border:0; border-radius: 15px;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </div>
  </div>
</section>
<script src="https://kit.fontawesome.com/4e9c2b6e8b.js" crossorigin="anonymous"></script>
<!-- Bootstrap 5 JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  // Optimize AOS for faster loading
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS with optimized settings
    AOS.init({
      duration: 600,        // Reduced from 800
      once: true,
      offset: 50,          // Reduced from 100
      delay: 0,            // Remove default delay
      easing: 'ease-out-cubic'
    });
  });

  // Optimized smooth scrolling
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

  // Optimized parallax effect with requestAnimationFrame
  let ticking = false;
  function updateParallax() {
    const scrolled = window.pageYOffset;
    const rate = scrolled * -0.3; // Reduced effect for better performance
    const heroOverlay = document.querySelector('.hero-overlay');
    if (heroOverlay) {
      heroOverlay.style.transform = `translate3d(0, ${rate}px, 0)`;
    }
    ticking = false;
  }

  window.addEventListener('scroll', function() {
    if (!ticking) {
      requestAnimationFrame(updateParallax);
      ticking = true;
    }
  });

  // Optimized navbar scroll effect
  let navbarTicking = false;
  function updateNavbar() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      if (window.scrollY > 100) {
        navbar.classList.add('navbar-scrolled');
      } else {
        navbar.classList.remove('navbar-scrolled');
      }
    }
    navbarTicking = false;
  }

  window.addEventListener('scroll', function() {
    if (!navbarTicking) {
      requestAnimationFrame(updateNavbar);
      navbarTicking = true;
    }
  });

  // Preload next section images when they come into view
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          observer.unobserve(img);
        }
      }
    });
  }, {
    rootMargin: '50px 0px' // Start loading 50px before image comes into view
  });

  // Observe all lazy load images
  document.querySelectorAll('img[loading="lazy"]').forEach(img => {
    imageObserver.observe(img);
  });
</script>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/floating_contact.php'; ?>
<script src="/assets/js/auto_logout.js"></script>