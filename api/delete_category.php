<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../includes/auth.php';
require_once '../includes/db.php';

ob_clean();

// Check if admin is logged in
if (!current_user() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Category ID is required']);
        exit();
    }
    
    $category_id = intval($input['id']);
    $image_name = $input['image'] ?? '';
    
    $pdo = db();
    
    // Check if category exists
    $check_query = "SELECT image FROM service_categories WHERE id = :id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([':id' => $category_id]);
    $category = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        exit();
    }
    
    // Delete from database
    $delete_query = "DELETE FROM service_categories WHERE id = :id";
    $delete_stmt = $pdo->prepare($delete_query);
    $result = $delete_stmt->execute([':id' => $category_id]);
    
    if ($result) {
        // Delete image file
        $image_path = __DIR__ . '/../public/' . $category['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}