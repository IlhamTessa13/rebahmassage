<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_login(): void {
  if (!current_user()) {
    header('Location: /php/login.php');
    exit;
  }
}

function is_admin(): bool {
  return (current_user()['role'] ?? '') === 'admin';
}

function is_customer(): bool {
  return (current_user()['role'] ?? '') === 'customer';
}
