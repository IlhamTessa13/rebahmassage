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
    // Validate required fields
    if (empty($_POST['name']) || empty($_POST['description']) || empty($_POST['branch_id'])) {
        echo json_encode(['success' => false, 'message' => 'Name, description, and branch are required']);
        exit();
    }
    
    // Validate image upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Image is required']);
        exit();
    }
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $branch_id = intval($_POST['branch_id']);
    
    // Handle image upload
    $file = $_FILES['image'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, and PNG images are allowed']);
        exit();
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Image size must be less than 2MB']);
        exit();
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = strtolower(str_replace(' ', '_', $name)) . '.' . $extension;
    $upload_dir = __DIR__ . '/../public/';
    $upload_path = $upload_dir . $filename;
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit();
    }
    
    // Insert into database
    $pdo = db();
    
    // Check if category with same name already exists in this branch
    $check_query = "SELECT id FROM service_categories WHERE name = :name AND branch_id = :branch_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([':name' => $name, ':branch_id' => $branch_id]);
    
    if ($check_stmt->fetch()) {
        // Delete uploaded file
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Category with this name already exists in this branch']);
        exit();
    }
    
    $insert_query = "INSERT INTO service_categories (branch_id, name, description, image) 
                     VALUES (:branch_id, :name, :description, :image)";
    
    $insert_stmt = $pdo->prepare($insert_query);
    $result = $insert_stmt->execute([
        ':branch_id' => $branch_id,
        ':name' => $name,
        ':description' => $description,
        ':image' => $filename
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully',
            'category_id' => $pdo->lastInsertId()
        ]);
    } else {
        // Delete uploaded file if database insert fails
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Failed to create category']);
    }
    
} catch (PDOException $e) {
    // Delete uploaded file if exists
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Delete uploaded file if exists
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}