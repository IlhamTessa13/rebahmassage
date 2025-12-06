<?php
require_once __DIR__ . '/includes/db.php';
session_start();

$message = '';
$success = false;
$token = $_GET['token'] ?? '';

if ($token) {
  $stmt = db()->prepare('SELECT id, full_name, email, email_verified FROM users WHERE verification_token = ? LIMIT 1');
  $stmt->execute([$token]);
  $user = $stmt->fetch();
  
  if ($user) {
    if ($user['email_verified'] == 1) {
      $message = 'Your email has been verified. Please log in.';
      $success = true;
    } else {
      // Verify email
      $stmt = db()->prepare('UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?');
      $stmt->execute([$user['id']]);
      
      $message = 'Email successfully verified! You can now log in.';
      $success = true;
    }
  } else {
    $message = 'The verification token is invalid or has expired.';
  }
} else {
  $message = 'Token not found.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Verification - Rebah Massage</title>
  <link rel="stylesheet" href="css/auth-style.css">
  <style>
    .verification-container {
      text-align: center;
      max-width: 500px;
      margin: 0 auto;
    }
    
    .verification-icon {
      font-size: 80px;
      margin-bottom: 20px;
    }
    
    .verification-icon.success {
      color: #48bb78;
    }
    
    .verification-icon.error {
      color: #f56565;
    }
    
    .info-box {
      background: #f7fafc;
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
    }
  </style>
</head>
<body>
  <div class="auth-container single-form">
    <div class="form-container centered-form">
      <div class="auth-form verification-container">
        <div class="verification-icon <?= $success ? 'success' : 'error' ?>">
          <?= $success ? '✓' : '✗' ?>
        </div>
        
        <h1 class="form-title">Email Verification</h1>
        
        <div class="info-box">
          <p style="margin: 0; font-size: 16px; color: #2d3748;">
            <?= htmlspecialchars($message) ?>
          </p>
        </div>
        
        <?php if ($success): ?>
          <a href="login.php" class="submit-btn" style="display: inline-block; text-decoration: none;">
            Login Now
          </a>
        <?php else: ?>
          <a href="login.php" class="submit-btn" style="display: inline-block; text-decoration: none;">
            Return to Login
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>