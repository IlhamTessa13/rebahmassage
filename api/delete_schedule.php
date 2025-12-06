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
    
    // Delete booking
    if ($booking_type === 'online') {
        $delete = "DELETE FROM bookings WHERE id = :booking_id AND branch_id = :branch_id";
    } else {
        $delete = "DELETE FROM offline_bookings WHERE id = :booking_id AND branch_id = :branch_id";
    }
    
    $stmt = $pdo->prepare($delete);
    $result = $stmt->execute([
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already deleted']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>