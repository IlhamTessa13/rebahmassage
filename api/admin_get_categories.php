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
    
    // Get all service categories for the branch (without image for admin)
    $query = "SELECT id, name, description 
              FROM service_categories 
              WHERE branch_id = :branch_id 
              ORDER BY name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':branch_id' => $branch_id]);
    
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($categories)
    ]);
    
} catch (Exception $e) {
    error_log("Error in admin_get_categories.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading categories: ' . $e->getMessage()
    ]);
}
?>