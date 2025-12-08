<?php
require_once 'includes/db.php';

// Get blog post
try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM blog WHERE id = 2");
    $stmt->execute();
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blog) {
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching blog: " . $e->getMessage());
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($blog['title']); ?> - Rebah Blog</title>
    <link rel="stylesheet" href="/php/css/blog-detail.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="/php/index.php" class="nav-link">← Back to Home</a>
            <a href="/php/index.php#blog" class="nav-link">All Blogs</a>
        </div>
    </nav>

    <!-- Blog Content -->
    <main class="blog-detail">
        <div class="blog-container">
            <article class="blog-article">
                <header class="blog-header">
                    <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
                </header>

                <div class="blog-image-container">
                    <img src="/php/public/<?php echo htmlspecialchars($blog['image']); ?>" 
                         alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                         class="blog-featured-image">
                </div>

                <div class="blog-content">
                    <?php echo nl2br(htmlspecialchars($blog['description'])); ?>
                </div>

                <div class="blog-actions">
                    <a href="/php/index.php#blog" class="btn-back">← Back to Blog</a>
                    <a href="/php/booking.php" class="btn-booking">Book Now</a>
                </div>
            </article>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <img src="/php/public/logobrown.png" class="footer-logo" alt="Rebah Logo">
                <p class="footer-text">
                    Book your appointment via WhatsApp. We recommend booking in advance to ensure your preferred time slot.
                </p>
                <div class="social-links">
                    <a href="https://www.instagram.com/rebahmassage?igsh=MWJvbjJ6aHMxM2g0cw==" class="social-icon">
                        <img src="/php/public/instagram.png" alt="Instagram">
                    </a>
                    <a href="https://www.facebook.com/share/14QipLmP3zo/" class="social-icon">
                        <img src="/php/public/facebook.png" alt="Facebook">
                    </a>
                </div>
            </div>
            
            <div class="footer-right">
                <div class="footer-column">
                    <h4 class="footer-heading">Navigation</h4>
                    <a href="/php/index.php#home" class="footer-link">Home</a>
                    <a href="/php/index.php#about" class="footer-link">About</a>
                    <a href="/php/index.php#service" class="footer-link">Service</a>
                    <a href="/php/index.php#blog" class="footer-link">Blog</a>
                </div>
                
                <div class="footer-column">
                    <h4 class="footer-heading">Rebah Fatmawati</h4>
                    <p class="footer-info">Everyday - 10AM - 10PM</p>
                </div>
                
                <div class="footer-column">
                    <h4 class="footer-heading">Rebah Menteng</h4>
                    <p class="footer-info">Everyday - 10AM - 10PM</p>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            @2025 Rebah Massage. All rights reserved
        </div>
    </footer>
</body>
</html>