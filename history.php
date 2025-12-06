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
  <title>History - Rebah Massage</title>
  <link rel="stylesheet" href="css/history.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');
  </style>
</head>
<body>
  
  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="home-customer.php" class="nav-link">Home</a>
      <a href="home-customer.php#about" class="nav-link">About</a>
      <a href="home-customer.php#service" class="nav-link">Service</a>
      <a href="home-customer.php#maps" class="nav-link">Maps</a>
      <a href="home-customer.php#blog" class="nav-link">Blog</a>
      <a href="booking.php" class="nav-link">Booking</a>
      <a href="history.php" class="nav-link active">History</a>
      <a href="logout.php" class="nav-link">Logout</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <section id="home" class="hero-section">
    <div class="hero-card">
      <div class="hero-content">
      </div>
    </div>
  </section>

  <!-- History Container -->
  <div class="history-container">
    <div class="history-table-wrapper">
      <table class="history-table">
        <thead>
          <tr>
            <th>Booking Code</th>
            <th>Branch</th>
            <th>Services Category</th>
            <th>Duration</th>
            <th>Room</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="bookingsTableBody">
          <!-- Will be loaded via JavaScript -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-left">
        <img src="/php/public/logorebah.png" class="footer-logo" alt="Rebah Logo">
        <p class="footer-text">
          Book your appointment via WhatsApp. We recommend booking in advance to ensure your preferred time slot.
        </p>
        <div class="social-links">
          <a href="https://www.instagram.com/rebahmassage?igsh=MWJvbjJ6aHMxM2g0cw==" class="social-icon" aria-label="Instagram">
            <img src="/php/public/instagram.png" alt="Instagram">
          </a>
          <a href="https://www.facebook.com/share/14QipLmP3zo/" class="social-icon" aria-label="Facebook">
            <img src="/php/public/facebook.png" alt="Facebook">
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

  <script src="js/history.js"></script>
</body>
</html>