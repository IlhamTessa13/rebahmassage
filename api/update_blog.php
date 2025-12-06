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

// Validate required fields
if (!isset($_POST['blog_id']) || !isset($_POST['title']) || !isset($_POST['clickbait']) || !isset($_POST['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$blog_id = intval($_POST['blog_id']);
$title = trim($_POST['title']);
$clickbait = trim($_POST['clickbait']);
$description = trim($_POST['description']);

// Validate blog ID (must be 1, 2, or 3)
if ($blog_id < 1 || $blog_id > 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid blog ID']);
    exit();
}

// Validate that fields are not empty
if (empty($title) || empty($clickbait) || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'All fields must be filled']);
    exit();
}

// Validate field lengths
if (strlen($title) > 255) {
    echo json_encode(['success' => false, 'message' => 'Title is too long (max 255 characters)']);
    exit();
}

if (strlen($clickbait) > 500) {
    echo json_encode(['success' => false, 'message' => 'Clickbait is too long (max 500 characters)']);
    exit();
}

try {
    $pdo = db();
    
    // Check if blog exists
    $check_stmt = $pdo->prepare("SELECT id, image FROM blog WHERE id = :id");
    $check_stmt->execute([':id' => $blog_id]);
    $existing_blog = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing_blog) {
        echo json_encode(['success' => false, 'message' => 'Blog not found']);
        exit();
    }
    
    // Handle image upload if provided
    $image_name = $existing_blog['image']; // Keep current image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, and GIF allowed']);
            exit();
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB']);
            exit();
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'blog' . $blog_id . '.' . $extension;
        $upload_path = '../public/' . $new_filename;
        
        // Delete old image if it exists and is different
        $old_image_path = '../public/' . $existing_blog['image'];
        if (file_exists($old_image_path) && $existing_blog['image'] !== $new_filename) {
            unlink($old_image_path);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $image_name = $new_filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit();
        }
    }
    
    // Update blog
    $stmt = $pdo->prepare("
        UPDATE blog 
        SET title = :title, 
            clickbait = :clickbait, 
            description = :description, 
            image = :image,
            created_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':title' => $title,
        ':clickbait' => $clickbait,
        ':description' => $description,
        ':image' => $image_name,
        ':id' => $blog_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog updated successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>