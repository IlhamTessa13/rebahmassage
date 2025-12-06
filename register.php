<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($name && $email && $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
      $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, phone, role) VALUES (?,?,?,?, "customer")');
      $stmt->execute([$name, $email, $hash, $phone]);
      header('Location: /php/login.php');
      exit;
    } catch (PDOException $e) {
      $error = 'Email sudah terdaftar.';
    }
  } else {
    $error = 'Mohon lengkapi data.';
  }
}

$title = 'Register';
require __DIR__ . '/includes/header.php';
?>
  <h1>Register</h1>
  <?php if (!empty($error)): ?><p class="help" style="color:#dc2626"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="post" class="card" autocomplete="off">
    <label>Nama</label>
    <input type="text" name="name" required />
    <label>Email</label>
    <input type="email" name="email" required />
    <label>No. HP</label>
    <input type="text" name="phone" />
    <label>Password</label>
    <input type="password" name="password" required />
    <button type="submit">Daftar</button>
  </form>
<?php require __DIR__ . '/includes/footer.php'; ?>
