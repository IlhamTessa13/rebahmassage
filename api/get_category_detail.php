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
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Category ID is required']);
        exit();
    }
    
    $pdo = db();
    
    $query = "SELECT id, name, description, image, branch_id 
              FROM service_categories 
              WHERE id = :id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $id]);
    
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        echo json_encode([
            'success' => true,
            'category' => $category
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Category not found'
        ]);
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