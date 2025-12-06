<?php
/**
 * Google OAuth Callback Handler
 * This file handles the redirect from Google after user authorization
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/google-config.php';

// Check if code is present
if (!isset($_GET['code'])) {
    $_SESSION['error'] = 'Authorization failed. Please try again.';
    header('Location: /php/login.php');
    exit;
}

// Get Google user info
$userInfo = getGoogleUserInfo($_GET['code']);

if (!$userInfo) {
    $_SESSION['error'] = 'Failed to get user information from Google.';
    header('Location: /php/login.php');
    exit;
}

try {
    $pdo = db();
    
    // Check if user already exists by email
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, gender, google_id, email_verified FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$userInfo['email']]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // User exists - update google_id if not set and auto-verify email
        if (empty($existingUser['google_id']) || $existingUser['email_verified'] == 0) {
            $updateStmt = $pdo->prepare('UPDATE users SET google_id = ?, email_verified = 1, verification_token = NULL WHERE id = ?');
            $updateStmt->execute([$userInfo['google_id'], $existingUser['id']]);
        }
        
        // Log user in
        $_SESSION['user'] = [
            'id' => (int)$existingUser['id'],
            'full_name' => $existingUser['full_name'],
            'email' => $existingUser['email'],
            'role' => $existingUser['role'],
            'gender' => $existingUser['gender'],
        ];
        
        // Redirect based on role
        if ($existingUser['role'] === 'admin') {
            header('Location: /php/dashboard.php');
        } else {
            header('Location: /php/home-customer.php');
        }
        exit;
    } else {
        // New user - create account
        // Note: Gender is not provided by Google, so we'll set it as NULL or show a form to complete profile
        // Google users are automatically verified
        
        $insertStmt = $pdo->prepare('
            INSERT INTO users (full_name, email, google_id, role, email_verified, created_at) 
            VALUES (?, ?, ?, "customer", 1, NOW())
        ');
        
        $insertStmt->execute([
            $userInfo['name'],
            $userInfo['email'],
            $userInfo['google_id']
        ]);
        
        $newUserId = $pdo->lastInsertId();
        
        // Create session for new user
        $_SESSION['user'] = [
            'id' => (int)$newUserId,
            'full_name' => $userInfo['name'],
            'email' => $userInfo['email'],
            'role' => 'customer',
            'gender' => null,
        ];
        
        // Flag to complete profile (gender and phone)
        $_SESSION['needs_profile_completion'] = true;
        
        // Redirect to profile completion or home
        // Check if complete-profile.php exists, otherwise go to home
        if (file_exists(__DIR__ . '/complete-profile.php')) {
            header('Location: /php/complete-profile.php');
        } else {
            header('Location: /php/home-customer.php');
        }
        exit;
    }
    
} catch (PDOException $e) {
    error_log('Database error in Google callback: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: /php/login.php');
    exit;
}
?>