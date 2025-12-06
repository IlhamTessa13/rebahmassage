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
    
    // Update status to cancelled for no-show (only for approved bookings)
    if ($booking_type === 'online') {
        $update = "UPDATE bookings 
                   SET status = 'cancelled', updated_at = NOW()
                   WHERE id = :booking_id 
                   AND branch_id = :branch_id 
                   AND status = 'approved'";
    } else {
        $update = "UPDATE offline_bookings 
                   SET status = 'cancelled', updated_at = NOW()
                   WHERE id = :booking_id 
                   AND branch_id = :branch_id 
                   AND status = 'approved'";
    }
    
    $stmt = $pdo->prepare($update);
    $result = $stmt->execute([
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Booking marked as no-show (cancelled) successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Booking not found or not in approved status'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in mark_noshow: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>