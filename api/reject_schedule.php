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
        // TODO: Send notification to customer about rejection
        // if (!empty($reason)) {
        //     // Send email/SMS with reason
        // }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking rejected successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Booking not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>