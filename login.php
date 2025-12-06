<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/google-config.php';
require_once __DIR__ . '/includes/email-config.php';

$loginError = '';
$registerError = '';
$successMessage = '';

// Get messages from session
if (isset($_SESSION['error'])) {
    $loginError = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  
  if ($email && $password) {
    $stmt = db()->prepare('SELECT id, full_name, email, password, role, gender, email_verified FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
      // Check if email is verified
      if ($user['email_verified'] == 0) {
        $loginError = 'Email Anda belum diverifikasi. Silakan cek inbox email Anda.';
      } else {
        $_SESSION['user'] = [
          'id' => (int)$user['id'],
          'full_name' => $user['full_name'],
          'email' => $user['email'],
          'role' => $user['role'],
          'gender' => $user['gender'],
        ];
        
        if ($user['role'] === 'admin') header('Location: /php/dashboard.php');
        else header('Location: /php/home-customer.php');
        exit;
      }
    } else {
      $loginError = 'Email atau password salah.';
    }
  } else {
    $loginError = 'Mohon isi email dan password.';
  }
}

// Handle Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $gender = $_POST['gender'] ?? '';
  $password = $_POST['password'] ?? '';
  
  if ($name && $email && $password && $gender) {
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $registerError = 'Format email tidak valid.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $verificationToken = bin2hex(random_bytes(32));
      
      try {
        $stmt = db()->prepare('INSERT INTO users (full_name, email, password, phone, gender, role, email_verified, verification_token) VALUES (?,?,?,?,?, "customer", 0, ?)');
        $stmt->execute([$name, $email, $hash, $phone, $gender, $verificationToken]);
        
        // Send verification email
        if (sendVerificationEmail($email, $name, $verificationToken)) {
          $_SESSION['success'] = 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi akun.';
          header('Location: /php/login.php');
          exit;
        } else {
          $registerError = 'Registrasi berhasil, tapi gagal mengirim email verifikasi. Silakan hubungi admin.';
        }
      } catch (PDOException $e) {
        $registerError = 'Email sudah terdaftar.';
      }
    }
  } else {
    $registerError = 'Mohon lengkapi data yang diperlukan.';
  }
}

// Get Google OAuth URL
$googleLoginUrl = getGoogleLoginUrl();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Rebah Massage</title>
  <link rel="stylesheet" href="css/auth-style.css">
</head>
<body>
  <div class="auth-container">
    
    <!-- Sliding Panel (Logo Background Image) -->
    <div class="sliding-panel" style="background-image: url('/php/public/loginbg.png');">
      <!-- Background image dari file PNG -->
    </div>
    
    <!-- Login Form -->
    <div class="form-container login-container">
      <form method="post" class="auth-form">
        <h1 class="form-title">Welcome Back!</h1>
        <p class="form-subtitle">We missed you! Please enter your details.</p>
        
        <?php if ($loginError): ?>
          <div class="error-message"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
          <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        
        <!-- Google Sign In Button -->
        <a href="<?= htmlspecialchars($googleLoginUrl) ?>" class="google-btn">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19.6 10.227c0-.709-.064-1.39-.182-2.045H10v3.868h5.382a4.6 4.6 0 01-1.996 3.018v2.51h3.232c1.891-1.742 2.982-4.305 2.982-7.35z" fill="#4285F4"/>
            <path d="M10 20c2.7 0 4.964-.895 6.618-2.423l-3.232-2.509c-.895.6-2.04.955-3.386.955-2.605 0-4.81-1.76-5.595-4.123H1.064v2.59A9.996 9.996 0 0010 20z" fill="#34A853"/>
            <path d="M4.405 11.9c-.2-.6-.314-1.24-.314-1.9 0-.66.114-1.3.314-1.9V5.51H1.064A9.996 9.996 0 000 10c0 1.614.386 3.14 1.064 4.49l3.34-2.59z" fill="#FBBC05"/>
            <path d="M10 3.977c1.468 0 2.786.505 3.823 1.496l2.868-2.868C14.959.99 12.695 0 10 0 6.09 0 2.71 2.24 1.064 5.51l3.34 2.59C5.19 5.736 7.395 3.977 10 3.977z" fill="#EA4335"/>
          </svg>
          Sign in with Google
        </a>
        
        <div class="divider">
          <span>OR</span>
        </div>
        
        <div class="form-group">
          <label>Email</label>
          <div class="input-wrapper">
            <input type="email" name="email" placeholder="Enter your Email" required />
          </div>
        </div>
        
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrapper">
            <input type="password" name="password" placeholder="Enter password" required />
          </div>
        </div>
        
        <div class="forgot-password-link">
          <a href="forgot-password.php">Lupa Password?</a>
        </div>
        
        <button type="submit" name="login" class="submit-btn">Sign In</button>
        
        <p class="toggle-text">
          Don't have an account? 
          <a href="#" id="signUpLink" class="toggle-link">Sign up</a>
        </p>
      </form>
    </div>
    
    <!-- Register Form -->
    <div class="form-container register-container">
      <form method="post" class="auth-form">
        <!-- Mobile Back Button (only visible on mobile) -->
        <a href="#" class="mobile-back-btn" onclick="document.getElementById('signInLink').click(); return false;">
          Kembali ke Login
        </a>
        
        <h1 class="form-title">Create Account</h1>
        
        <?php if ($registerError): ?>
          <div class="error-message"><?= htmlspecialchars($registerError) ?></div>
        <?php endif; ?>
        
        <!-- Google Sign Up Button -->
        <a href="<?= htmlspecialchars($googleLoginUrl) ?>" class="google-btn">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19.6 10.227c0-.709-.064-1.39-.182-2.045H10v3.868h5.382a4.6 4.6 0 01-1.996 3.018v2.51h3.232c1.891-1.742 2.982-4.305 2.982-7.35z" fill="#4285F4"/>
            <path d="M10 20c2.7 0 4.964-.895 6.618-2.423l-3.232-2.509c-.895.6-2.04.955-3.386.955-2.605 0-4.81-1.76-5.595-4.123H1.064v2.59A9.996 9.996 0 0010 20z" fill="#34A853"/>
            <path d="M4.405 11.9c-.2-.6-.314-1.24-.314-1.9 0-.66.114-1.3.314-1.9V5.51H1.064A9.996 9.996 0 000 10c0 1.614.386 3.14 1.064 4.49l3.34-2.59z" fill="#FBBC05"/>
            <path d="M10 3.977c1.468 0 2.786.505 3.823 1.496l2.868-2.868C14.959.99 12.695 0 10 0 6.09 0 2.71 2.24 1.064 5.51l3.34 2.59C5.19 5.736 7.395 3.977 10 3.977z" fill="#EA4335"/>
          </svg>
          Sign up with Google
        </a>
        
        <div class="divider">
          <span>OR</span>
        </div>
        
        <div class="form-group">
          <label>Nama Lengkap</label>
          <div class="input-wrapper">
            <input type="text" name="name" placeholder="Enter your name" required />
          </div>
        </div>
        
        <div class="form-group">
          <label>Email</label>
          <div class="input-wrapper">
            <input type="email" name="email" placeholder="Enter your email" required />
          </div>
        </div>
        
        <div class="form-group">
          <label>No. HP</label>
          <div class="input-wrapper">
            <input type="tel" name="phone" placeholder="08xxxxxxxxxx" />
          </div>
        </div>
        
        <div class="form-group">
          <label>Jenis Kelamin</label>
          <div class="gender-group">
            <div class="radio-wrapper">
              <input type="radio" id="male" name="gender" value="male" required />
              <label for="male">Laki-laki</label>
            </div>
            <div class="radio-wrapper">
              <input type="radio" id="female" name="gender" value="female" required />
              <label for="female">Perempuan</label>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrapper">
            <input type="password" name="password" placeholder="Enter password" required />
          </div>
        </div>
        
        <button type="submit" name="register" class="submit-btn">Sign Up</button>
        
        <p class="toggle-text">
          Already have an account? 
          <a href="#" id="signInLink" class="toggle-link">Sign in</a>
        </p>
      </form>
    </div>
    
  </div>
  
  <script src="js/auth-script.js"></script>
</body>
</html>