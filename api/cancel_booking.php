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
        echo json_encode([
            'success' => true,
            'message' => 'Booking cancelled successfully'
        ]);
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