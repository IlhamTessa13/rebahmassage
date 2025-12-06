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
$is_active = intval($input['is_active'] ?? 0);

// Validation
if ($room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit();
}

if ($is_active !== 0 && $is_active !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    $pdo = db();
    
    // Update room status
    $query = "UPDATE rooms SET is_active = :is_active WHERE id = :room_id";
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':is_active' => $is_active,
        ':room_id' => $room_id
    ]);
    
    if ($result) {
        $action = $is_active === 1 ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true,
            'message' => "Room {$action} successfully"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update room status']);
    }
    
} catch (Exception $e) {
    error_log("Error in toggle_room_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating room status: ' . $e->getMessage()
    ]);
}
?>