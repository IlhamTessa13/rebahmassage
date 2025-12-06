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

$room_name = trim($input['room_name'] ?? '');
$branch_id = intval($input['branch_id'] ?? 0);

// Validation
if (empty($room_name)) {
    echo json_encode(['success' => false, 'message' => 'Room name is required']);
    exit();
}

if ($branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit();
}

try {
    $pdo = db();
    
    // Check if room name already exists in this branch
    $check_query = "SELECT id FROM rooms WHERE name = :name AND branch_id = :branch_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([
        ':name' => $room_name,
        ':branch_id' => $branch_id
    ]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Room name already exists in this branch']);
        exit();
    }
    
    // Insert new room
    $query = "INSERT INTO rooms (name, branch_id, is_active, created_at) 
              VALUES (:name, :branch_id, 1, NOW())";
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':name' => $room_name,
        ':branch_id' => $branch_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Room created successfully',
            'room_id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create room']);
    }
    
} catch (Exception $e) {
    error_log("Error in create_room.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error creating room: ' . $e->getMessage()
    ]);
}
?>