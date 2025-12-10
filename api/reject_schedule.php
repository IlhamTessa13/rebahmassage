<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

// Check admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin = current_user();
$branch_id = $admin['branch_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$booking_id = isset($input['id']) ? intval($input['id']) : 0;
$booking_type = isset($input['type']) ? $input['type'] : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

// Validate
if ($booking_id <= 0 || !in_array($booking_type, ['online', 'offline'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID or type']);
    exit();
}

try {
    $pdo = db();
    
    // Update status to cancelled
    if ($booking_type === 'online') {
        $update = "UPDATE bookings 
                   SET status = 'cancelled', updated_at = NOW()
                   WHERE id = :booking_id 
                   AND branch_id = :branch_id";
    } else {
        $update = "UPDATE offline_bookings 
                   SET status = 'cancelled', updated_at = NOW()
                   WHERE id = :booking_id 
                   AND branch_id = :branch_id";
    }
    
    $stmt = $pdo->prepare($update);
    $result = $stmt->execute([
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Send response IMMEDIATELY
        echo json_encode([
            'success' => true, 
            'message' => 'Booking rejected successfully'
        ]);
        
        // Trigger background email sending (only for online bookings)
        if ($booking_type === 'online') {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
            
            $email_url = $base_url . '/send-email-background.php?type=reject&booking_id=' . $booking_id . '&booking_type=online&reason=' . urlencode($reason);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 1,
                    'ignore_errors' => true
                ]
            ]);
            
            @file_get_contents($email_url, false, $context);
        }
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Booking not found'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in reject_schedule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>