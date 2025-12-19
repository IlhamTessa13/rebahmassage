<?php
// Include auth helper
require_once 'includes/auth.php';
require_once 'includes/db.php';

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, title, clickbait, image FROM blog ORDER BY id LIMIT 3");
    $stmt->execute();
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching blogs: " . $e->getMessage());
    $blogs = [];
}

// Require login and check if customer
require_login();
if (!is_customer()) {
    header('Location: home-customer.php');
    exit();
}

// Get user data from session
$user = current_user();
$user_id = $user['id'];
$user_name = $user['full_name'];
$user_gender = $user['gender'];

// Include database connection
require_once 'includes/db.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Family Massage & Reflexology Jakarta | Rebah Massage</title>
  <meta name="description" content="Rebah Massage & Reflexology menyediakan layanan family massage dan reflexology profesional. Cabang Menteng Jakarta Pusat & Fatmawati Jakarta Selatan. Booking mudah.">
  <meta name="keywords" content="family massage jakarta, reflexology jakarta, massage menteng, massage fatmawati, rebah massage">
  <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
  <link rel="stylesheet" href="/php/css/home-customer.css">
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');
  </style>
</head>
<body>
  
  <!-- Navbar -->
  <nav class="navbar">
    <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <img src="/php/public/logobrown.png" alt="Rebah Logo" class="nav-logo-mobile">

    <div class="nav-container" id="navContainer">
    <button class="nav-close" id="navClose" aria-label="Close Menu">×</button>
    
      <a href=#home" class="nav-link active">Home</a>
      <a href="#about" class="nav-link">About</a>
      <a href="#service" class="nav-link">Service</a>
      <a href="#blog" class="nav-link">Blog</a>
      <a href="#maps" class="nav-link">Maps</a>
      <a href="booking.php" class="nav-link">Booking</a>
      <a href="history.php" class="nav-link">History</a>
      <a href="logout.php" class="nav-link">Logout</a>
    </div>
    <div class="nav-overlay" id="navOverlay"></div>
  </nav>

  <!-- Hero Section -->
  <section id="home" class="hero-section">
    <div class="hero-card"></div>
  </section>

  <!-- About Section -->
  <section id="about" class="about-section">
    <div class="about-container">
      <div class="about-content">
        <h2 class="about-title">About</h2>
        <p class="about-text">
          Welcome to Rebah Massage & Reflexology, where we blend healing and relaxation to guide you toward optimal wellness. With over a decade of experience, our licensed therapists specialize in personalized techniques for pain relief, stress reduction, and self-care. We believe in the restorative power of touch and provide every session in a peaceful, calming environment tailored to your individual needs.
        </p>
      </div>
      <div class="about-images">
        <img src="/php/public/aboutoverview.webp" alt="Massage Room" class="about-img">
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <section id="service" class="services-section">
    <div class="services-header">
      <h2 class="services-title">Our Massage Services</h2>
      <p class="services-subtitle">
        Choose from our wide range of therapeutic massage treatments, each designed to address specific needs and promote overall wellness.
      </p>
    </div>

    <div class="services-carousel-wrapper">
      <button class="carousel-btn carousel-prev" onclick="slideServices(-1)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>
      
      <div class="services-carousel">
        <div class="services-track" id="servicesTrack">
          <!-- Services will be loaded dynamically via JavaScript -->
          <div class="service-card skeleton">
            <div class="service-image-wrapper">
              <div class="skeleton-img"></div>
            </div>
          </div>
          <div class="service-card skeleton">
            <div class="service-image-wrapper">
              <div class="skeleton-img"></div>
            </div>
          </div>
          <div class="service-card skeleton">
            <div class="service-image-wrapper">
              <div class="skeleton-img"></div>
            </div>
          </div>
        </div>
      </div>
      
      <button class="carousel-btn carousel-next" onclick="slideServices(1)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>

    <div class="carousel-dots" id="carouselDots"></div>
  </section>

  <!-- Blog Section -->
  <section id="blog" class="blog-section">
    <div class="blog-header">
      <h2 class="blog-title">Wellness Blog</h2>
      <p class="blog-subtitle">
        Stay informed about wellness, massage therapy techniques, and tips for better health. Our blog features expert insights and practical advice for your wellness journey.
      </p>
    </div>
    <div class="blog-container">
      <?php foreach ($blogs as $blog): ?>
      <div class="blog-card">
        <img src="/php/public/<?php echo htmlspecialchars($blog['image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" class="blog-image">
        <div class="blog-content">
          <h3 class="blog-card-title"><?php echo htmlspecialchars($blog['title']); ?></h3>
          <p class="blog-excerpt">
            <?php echo htmlspecialchars($blog['clickbait']); ?>
          </p>
          <a href="/php/blog<?php echo $blog['id']; ?>.php" class="blog-link">Read More →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="blog-view-all">
      <a href="#blog" class="view-all-btn">View All Articles</a>
    </div>
  </section>

<!-- Maps Section -->
  <section id="maps" class="maps-section">
    <h2 class="maps-title">Our Location.</h2>
    <div class="maps-container">
      
      <!-- Fatmawati Location -->
      <div class="map-card">
        <div class="map-iframe-wrapper">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7249558385656!2d106.7928821740082!3d-6.299826461654416!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f1b52276d8b1%3A0x51c77535cab150f0!2sREBAH%20Massage%20%26%20Reflexology%20Fatmawati!5e0!3m2!1sid!2sid!4v1760755952029!5m2!1sid!2sid" 
            class="map-image" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
        <div class="map-info">
          <h3 class="map-location">Rebah Fatmawati</h3>
          <p class="map-address">
            Fatmawati Raya Street, West Cilandak,<br>
            Cilandak, South Jakarta, 12430<br>
            <strong>Everyday - 10AM - 10PM<br>last order 9PM (1 hour treatment)</strong>
          </p>
        </div>
      </div>

      <!-- Menteng Location -->
      <div class="map-card">
        <div class="map-iframe-wrapper">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.477757749146!2d106.83503077400685!3d-6.2005280607401385!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5c5486c8763%3A0x6e842df6e88b88af!2sREBAH%20Massage%20%26%20Reflexology%20Menteng!5e0!3m2!1sid!2sid!4v1760756488042!5m2!1sid!2sid" 
            class="map-image" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
        <div class="map-info">
          <h3 class="map-location">Rebah Menteng</h3>
          <p class="map-address">
            51 Teuku Cik Ditiro Street, Menteng,<br>
            Central Jakarta, 10310<br>
            <strong>Everyday - 10AM - 10PM<br>last order 9PM (1 hour treatment)</strong>
          </p>
        </div>
      </div>

    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-left">
        <img src="/php/public/logobrown.png" class="footer-logo" alt="Rebah Logo">
        <p class="footer-text">
          Book your appointment via WhatsApp. We recommend booking in advance to ensure your preferred time slot.
        </p>
        <div class="social-links">
          <a href="https://www.instagram.com/rebahmassage?igsh=MWJvbjJ6aHMxM2g0cw==" class="social-icon" aria-label="Instagram">
            <img src="/php/public/instagram.webp" alt="Instagram">
          </a>
          <a href="https://www.facebook.com/share/14QipLmP3zo/" class="social-icon" aria-label="Facebook">
            <img src="/php/public/facebook.webp" alt="Facebook">
          </a>
        </div>
      </div>
      
      <div class="footer-right">
        
        <div class="footer-column">
          <h4 class="footer-heading">Rebah Fatmawati</h4>
          <p class="footer-info">Everyday - 9AM - 9PM, last order<br>8PM (1 hour treatment)</p>
        </div>
        
        <div class="footer-column">
          <h4 class="footer-heading">Rebah Menteng</h4>
          <p class="footer-info">Everyday - 9AM - 9PM, last order<br>8PM (1 hour treatment)</p>
        </div>

        <div class="footer-column">
          <h4 class="footer-heading">Navigation</h4>
          <a href="#home" class="footer-link">Home</a>
          <a href="#about" class="footer-link">About</a>
          <a href="#service" class="footer-link">Service</a>
          <a href="#blog" class="footer-link">Blog</a>
          <a href="#maps" class="footer-link">Maps</a>
          <a href="booking.php" class="footer-link">Booking</a>
          <a href="history.php" class="footer-link">History</a>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      @2025 Rebah Massage. All rights reserved
    </div>
  </footer>

    <!-- WhatsApp Floating Button -->
  <div class="whatsapp-float" id="whatsappFloat">
    <img src="/php/public/walogo.webp" alt="WhatsApp" class="wa-icon">
    <div class="wa-bubble-container" id="waBubbleContainer">
      <div class="wa-bubble">
        <a href="https://wa.me/6282299994259" target="_blank" class="wa-bubble-item">
          <span class="wa-bubble-text">Booking via WhatsApp</span>
          <span class="wa-bubble-location">Fatmawati</span>
        </a>
      </div>
      <div class="wa-bubble">
        <a href="https://wa.me/6282299994263" target="_blank" class="wa-bubble-item">
          <span class="wa-bubble-text">Booking via WhatsApp</span>
          <span class="wa-bubble-location">Menteng</span>
        </a>
      </div>
    </div>
  </div>


  <script src="js/landing-page.js"></script>
</body>
</html>