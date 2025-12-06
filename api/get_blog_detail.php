<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if admin is logged in
require_login();
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get blog_id from query parameter
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing blog ID']);
    exit();
}

$blog_id = intval($_GET['id']);

// Validate blog ID (must be 1, 2, or 3)
if ($blog_id < 1 || $blog_id > 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid blog ID']);
    exit();
}

try {
    $pdo = db();
    
    // Get blog details
    $stmt = $pdo->prepare("SELECT * FROM blog WHERE id = :id");
    $stmt->execute([':id' => $blog_id]);
    
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blog) {
        echo json_encode(['success' => false, 'message' => 'Blog not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'blog' => $blog
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>