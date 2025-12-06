<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email-config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  
  if ($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Format email tidak valid.';
    } else {
      // Check if email exists
      $stmt = db()->prepare('SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      $user = $stmt->fetch();
      
      if ($user) {
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token to database
        $stmt = db()->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
        $stmt->execute([$resetToken, $expiresAt, $user['id']]);
        
        // Send reset email
        if (sendPasswordResetEmail($user['email'], $user['full_name'], $resetToken)) {
          $success = 'Link reset password telah dikirim ke email Anda. Silakan cek inbox Anda.';
        } else {
          $error = 'Gagal mengirim email. Silakan coba lagi.';
        }
      } else {
        // Don't reveal if email exists or not (security best practice)
        $success = 'Jika email terdaftar, link reset password akan dikirim ke email Anda.';
      }
    }
  } else {
    $error = 'Mohon masukkan email Anda.';
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Password - Rebah Massage</title>
  <link rel="stylesheet" href="css/auth-style.css">
</head>
<body>
  <div class="auth-container single-form">
    <div class="form-container centered-form">
      <form method="post" class="auth-form">
        <h1 class="form-title">Lupa Password?</h1>
        <p class="form-subtitle">Masukkan email Anda dan kami akan mengirimkan link untuk reset password.</p>
        
        <?php if ($error): ?>
          <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="form-group">
          <label>Email</label>
          <div class="input-wrapper">
            <input type="email" name="email" placeholder="Enter your email" required />
          </div>
        </div>
        
        <button type="submit" class="submit-btn">Kirim Link Reset</button>
        
        <p class="toggle-text">
          Ingat password Anda? 
          <a href="login.php" class="toggle-link">Kembali ke Login</a>
        </p>
      </form>
    </div>
  </div>
</body>
</html>