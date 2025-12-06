<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!current_user()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../includes/db.php';

// Ambil koneksi PDO
try {
    $pdo = db();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Ambil branch_id
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if ($branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit();
}

try {
    $query = "SELECT id, name, description, image 
              FROM service_categories 
              WHERE branch_id = ? 
              ORDER BY name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$branch_id]);
    
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'image' => $row['image'] ? 'public/' . $row['image'] : 'public/categories-default.jpg'
        ];
    }

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query error: ' . $e->getMessage()
    ]);
}
?>
