<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!current_user()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user = current_user();
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if ($branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit();
}

try {
    $pdo = db();
    
    // Get all active rooms for the branch
    $query = "SELECT id, name 
              FROM rooms 
              WHERE branch_id = :branch_id AND is_active = 1 
              ORDER BY name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':branch_id' => $branch_id]);
    
    $rooms = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rooms[] = [
            'id' => (int)$row['id'],
            'name' => $row['name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'count' => count($rooms)
    ]);
    
} catch (Exception $e) {
    error_log("Error in admin_get_rooms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading rooms: ' . $e->getMessage()
    ]);
}
?>