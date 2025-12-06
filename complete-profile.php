<?php
/**
 * Complete Profile Page
 * For users who sign in with Google and need to complete their profile
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
$user = current_user();
if (!$user) {
    header('Location: /php/login.php');
    exit;
}

// Check if profile completion is needed
if (!isset($_SESSION['needs_profile_completion'])) {
    header('Location: /php/home-customer.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';
    
    if ($phone && $gender) {
        try {
            $stmt = db()->prepare('UPDATE users SET phone = ?, gender = ? WHERE id = ?');
            $stmt->execute([$phone, $gender, $user['id']]);
            
            // Update session
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['gender'] = $gender;
            
            // Remove completion flag
            unset($_SESSION['needs_profile_completion']);
            
            $success = 'Profile completed successfully!';
            header('refresh:2;url=/php/home-customer.php');
            
        } catch (PDOException $e) {
            $error = 'Failed to update profile. Please try again.';
        }
    } else {
        $error = 'Please complete all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - Rebah Massage</title>
    <link rel="stylesheet" href="/php/css/auth-style.css">
    <style>
        .complete-profile-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-header h1 {
            color: #4a5568;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .profile-header p {
            color: #718096;
            font-size: 14px;
        }
        
        .user-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .user-info p {
            color: #4a5568;
            margin: 5px 0;
            font-size: 14px;
        }
        
        .user-info strong {
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="complete-profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <h1>Complete Your Profile</h1>
                <p>We need a few more details to complete your registration</p>
            </div>
            
            <div class="user-info">
                <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message" style="background: #fed7d7; color: #c53030; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message" style="background: #c6f6d5; color: #2f855a; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Phone Number</label>
                    <div class="input-wrapper">
                        <input type="tel" name="phone" placeholder="08xxxxxxxxxx" required />
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Gender</label>
                    <div class="gender-group">
                        <div class="radio-wrapper">
                            <input type="radio" id="male" name="gender" value="male" required />
                            <label for="male">Male</label>
                        </div>
                        <div class="radio-wrapper">
                            <input type="radio" id="female" name="gender" value="female" required />
                            <label for="female">Female</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Complete Profile</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: #718096; font-size: 14px;">
                <a href="/php/logout.php" style="color: #667eea; text-decoration: none;">Sign out</a>
            </p>
        </div>
    </div>
</body>
</html>