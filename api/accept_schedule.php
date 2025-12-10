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

// Validate
if ($booking_id <= 0 || !in_array($booking_type, ['online', 'offline'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID or type']);
    exit();
}

try {
    $pdo = db();
    
    // Only online bookings can be accepted (offline are auto-approved)
    if ($booking_type !== 'online') {
        echo json_encode(['success' => false, 'message' => 'Offline bookings are already approved']);
        exit();
    }
    
    // Update status to approved
    $update = "UPDATE bookings 
               SET status = 'approved', updated_at = NOW()
               WHERE id = :booking_id 
               AND branch_id = :branch_id 
               AND status = 'pending'";
    
    $stmt = $pdo->prepare($update);
    $result = $stmt->execute([
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Send response IMMEDIATELY
        echo json_encode([
            'success' => true, 
            'message' => 'Booking accepted successfully'
        ]);
        
        // Trigger background email sending
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
        
        $email_url = $base_url . '/send-email-background.php?type=accept&booking_id=' . $booking_id . '&booking_type=online';
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'ignore_errors' => true
            ]
        ]);
        
        @file_get_contents($email_url, false, $context);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
    }
    
} catch (Exception $e) {
    error_log("Error in accept_schedule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>