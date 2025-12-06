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
    if (empty($_POST['category_id']) || empty($_POST['name']) || empty($_POST['description']) || empty($_POST['branch_id'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    $category_id = intval($_POST['category_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $branch_id = intval($_POST['branch_id']);
    $old_image = $_POST['old_image'] ?? '';
    
    $pdo = db();
    
    // Check if category exists
    $check_query = "SELECT image FROM service_categories WHERE id = :id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([':id' => $category_id]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        exit();
    }
    
    $filename = $existing['image']; // Keep old image by default
    
    // Handle new image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
        $upload_dir = '../public/';
        $upload_path = $upload_dir . $filename;
        
        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload new image']);
            exit();
        }
        
        // Delete old image if it's different from new one
        if ($existing['image'] && $existing['image'] !== $filename) {
            $old_path = $upload_dir . $existing['image'];
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
    }
    
    // Update database
    $update_query = "UPDATE service_categories 
                     SET name = :name, description = :description, image = :image 
                     WHERE id = :id";
    
    $update_stmt = $pdo->prepare($update_query);
    $result = $update_stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':image' => $filename,
        ':id' => $category_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update category']);
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