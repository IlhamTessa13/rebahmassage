<?php
// Include auth helper
require_once 'includes/auth.php';

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
  <link rel="stylesheet" href="css/booking.css">
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
    
    <a href="home-customer.php" class="nav-link">Home</a>
    <a href="home-customer.php#about" class="nav-link">About</a>
    <a href="home-customer.php#service" class="nav-link">Service</a>
    <a href="home-customer.php#maps" class="nav-link">Maps</a>
    <a href="home-customer.php#blog" class="nav-link">Blog</a>
    <a href="booking.php" class="nav-link active">Booking</a>
    <a href="history.php" class="nav-link">History</a>
    <a href="logout.php" class="nav-link">Logout</a>
  </div>
  
  <div class="nav-overlay" id="navOverlay"></div>
</nav>

  <!-- Hero Section -->
  <section id="home" class="hero-section">
    <div class="hero-card">
      <div class="hero-content">
      </div>
    </div>
  </section>

  <!-- Booking Content -->
  <div class="booking-container">
    

    <!-- Step 1: Select Branch -->
    <div class="booking-section" id="branchSection">
      <h2 class="section-title">Select Branch</h2>
      <div class="branch-grid" id="branchGrid">
        <!-- Will be loaded via AJAX -->
      </div>
    </div>

    <!-- Step 2: Select Category (Hidden initially) -->
    <div class="booking-section hidden" id="categorySection">
      <h2 class="section-title">Select Services Category</h2>
      <div class="category-grid" id="categoryGrid">
        <!-- Will be loaded via AJAX after branch selected -->
      </div>
    </div>

    <!-- Step 3: Booking Form (Hidden initially) -->
    <div class="booking-section hidden" id="formSection">
      <h2 class="section-title">Booking Form</h2>
      
      <form id="bookingForm" class="booking-form">
        <input type="hidden" id="selectedBranch" name="branch_id">
        <input type="hidden" id="selectedCategory" name="category_id">
        
        <!-- Duration -->
        <div class="form-group">
          <label for="duration">Duration</label>
          <select id="duration" name="duration" required>
            <option value="">Select duration</option>
            <option value="60">60 minutes</option>
            <option value="90">90 minutes</option>
            <option value="120">120 minutes</option>
          </select>
        </div>

        <!-- Room -->
        <div class="form-group">
          <div class="label-with-info">
            <label for="room">Room</label>
            <button type="button" class="info-btn" onclick="showRoomInfo()" aria-label="Room Information" title="Important information about room assignment">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4M12 8h.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          <select id="room" name="room_id" required>
            <option value="">Select room</option>
            <!-- Will be loaded dynamically -->
          </select>
        </div>

        <!-- Date and Time -->
        <div class="form-row">
          <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="booking_date" required>
          </div>
          
          <div class="form-group">
            <label for="time">Time Start</label>
            <select id="time" name="start_time" required>
              <option value="">Select time</option>
              <!-- Will be loaded dynamically -->
            </select>
          </div>
        </div>

        <!-- Therapist -->
        <div class="form-group">
          <div class="label-with-info">
            <label for="therapist">Therapist</label>
            <button type="button" class="info-btn" onclick="showTherapistInfo()" aria-label="Therapist Information" title="Important information about therapist assignment">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4M12 8h.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          <select id="therapist" name="therapist_id" required>
            <option value="">Select therapist</option>
            <!-- Will be loaded dynamically -->
          </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-confirm" id="btnSubmit">
          <span id="btnText">Confirm Booking</span>
          <span id="btnLoader" class="loader hidden"></span>
        </button>
      </form>
    </div>

  </div>

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
          <a href="home-customer.php#about" class="footer-link">About</a>
          <a href="home-customer.php#service" class="footer-link">Service</a>
          <a href="home-customer.php#blog" class="footer-link">Blog</a>
          <a href="home-customer.php#maps" class="footer-link">Maps</a>
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


  <script src="js/booking.js"></script>
</body>
</html>