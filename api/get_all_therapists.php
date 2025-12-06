<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

// Check admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if ($branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit();
}

try {
    $pdo = db();
    
    // Get all active therapists (no gender filter for admin)
    $query = "SELECT id, name, gender FROM therapists 
              WHERE branch_id = :branch_id AND is_active = 1 
              ORDER BY name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':branch_id' => $branch_id]);
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'therapists' => $therapists
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>