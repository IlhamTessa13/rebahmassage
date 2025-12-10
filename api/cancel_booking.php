<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user || !is_customer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$booking_id = isset($input['booking_id']) ? intval($input['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit();
}

try {
    $pdo = db();
    $user_id = $user['id'];
    
    // Check if booking belongs to user and can be cancelled
    $check_query = "SELECT status FROM bookings 
                    WHERE id = :booking_id AND user_id = :user_id";
    
    $stmt = $pdo->prepare($check_query);
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':user_id' => $user_id
    ]);
    
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    // Only pending and approved can be cancelled
    if ($booking['status'] !== 'pending' && $booking['status'] !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'This booking cannot be cancelled']);
        exit();
    }
    
    // Update status to cancelled
    $update_query = "UPDATE bookings 
                     SET status = 'cancelled', updated_at = NOW() 
                     WHERE id = :booking_id AND user_id = :user_id";
    
    $stmt = $pdo->prepare($update_query);
    $result = $stmt->execute([
        ':booking_id' => $booking_id,
        ':user_id' => $user_id
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Send response IMMEDIATELY
        echo json_encode([
            'success' => true,
            'message' => 'Booking cancelled successfully'
        ]);
        
        // Trigger background email sending (non-blocking)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
        
        $email_url = $base_url . '/send-email-background.php?type=cancel_customer&booking_id=' . $booking_id . '&booking_type=online';
        
        // Call in background using stream context (non-blocking)
        $context = stream_context_create([
            'http' => [
                'timeout' => 1, // 1 second timeout - script will continue in background
                'ignore_errors' => true
            ]
        ]);
        
        @file_get_contents($email_url, false, $context);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to cancel booking'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>