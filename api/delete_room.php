<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!current_user()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user = current_user();
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$room_id = intval($input['room_id'] ?? 0);

// Validation
if ($room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit();
}

try {
    $pdo = db();
    
    // Check if room has active bookings
    $check_bookings = "SELECT COUNT(*) as count FROM bookings 
                       WHERE room_id = :room_id AND status IN ('pending', 'approved')";
    $check_stmt = $pdo->prepare($check_bookings);
    $check_stmt->execute([':room_id' => $room_id]);
    $booking_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($booking_count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete room with active bookings. Please cancel or complete all bookings first.'
        ]);
        exit();
    }
    
    // Check offline bookings
    $check_offline = "SELECT COUNT(*) as count FROM offline_bookings 
                      WHERE room_id = :room_id AND status IN ('pending', 'approved')";
    $check_offline_stmt = $pdo->prepare($check_offline);
    $check_offline_stmt->execute([':room_id' => $room_id]);
    $offline_count = $check_offline_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($offline_count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete room with active offline bookings. Please cancel or complete all bookings first.'
        ]);
        exit();
    }
    
    // Delete room
    $query = "DELETE FROM rooms WHERE id = :room_id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([':room_id' => $room_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete room']);
    }
    
} catch (Exception $e) {
    error_log("Error in delete_room.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting room: ' . $e->getMessage()
    ]);
}
?>