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
    if ($booking_type === 'online') {
        $update = "UPDATE bookings 
                   SET status = 'approved', updated_at = NOW()
                   WHERE id = :booking_id 
                   AND branch_id = :branch_id 
                   AND status = 'pending'";
    } else {
        echo json_encode(['success' => false, 'message' => 'Offline bookings are already approved']);
        exit();
    }
    
    $stmt = $pdo->prepare($update);
    $result = $stmt->execute([
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking accepted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>