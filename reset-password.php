<?php
require_once __DIR__ . '/includes/db.php';
session_start();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$validToken = false;

// Validate token
if ($token) {
  $stmt = db()->prepare('SELECT id, full_name, reset_token_expires FROM users WHERE reset_token = ? LIMIT 1');
  $stmt->execute([$token]);
  $user = $stmt->fetch();
  
  if ($user) {
    // Check if token is expired
    if (strtotime($user['reset_token_expires']) > time()) {
      $validToken = true;
    } else {
      $error = 'Link reset password telah kadaluarsa. Silakan minta link baru.';
    }
  } else {
    $error = 'Link reset password tidak valid.';
  }
} else {
  $error = 'Token tidak ditemukan.';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';
  
  if ($password && $confirmPassword) {
    if ($password === $confirmPassword) {
      if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
      } else {
        // Update password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        
        $_SESSION['success'] = 'Password berhasil diubah! Silakan login dengan password baru Anda.';
        header('Location: /php/login.php');
        exit;
      }
    } else {
      $error = 'Password tidak cocok.';
    }
  } else {
    $error = 'Mohon isi semua field.';
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - Rebah Massage</title>
  <link rel="stylesheet" href="css/auth-style.css">
</head>
<body>
  <div class="auth-container single-form">
    <div class="form-container centered-form">
      <form method="post" class="auth-form">
        <h1 class="form-title">Reset Password</h1>
        <p class="form-subtitle">Enter your new password.</p>
        
        <?php if ($error): ?>
          <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($validToken): ?>
          <div class="form-group">
            <label>New Password</label>
            <div class="input-wrapper">
              <input type="password" name="password" placeholder="Masukkan password baru" required minlength="6" />
            </div>
          </div>
          
          <div class="form-group">
            <label>Confirm Password</label>
            <div class="input-wrapper">
              <input type="password" name="confirm_password" placeholder="Konfirmasi password" required minlength="6" />
            </div>
          </div>
          
          <button type="submit" class="submit-btn">Reset Password</button>
        <?php else: ?>
          <div class="info-box">
            <p>The password reset link is invalid or has expired.</p>
            <a href="forgot-password.php" class="submit-btn" style="display: inline-block; text-align: center; text-decoration: none; margin-top: 15px;">Minta Link Baru</a>
          </div>
        <?php endif; ?>
        
        <p class="toggle-text">
          <a href="login.php" class="toggle-link">Return to Login</a>
        </p>
      </form>
    </div>
  </div>
  
  <script src="js/auth-script.js"></script>
</body>
</html>