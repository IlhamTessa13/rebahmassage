<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
require_login();
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Validate required fields
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Service name is required']);
    exit;
}

// Validate image
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Image is required']);
    exit;
}

$file = $_FILES['image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, and GIF are allowed']);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB']);
    exit;
}

// Generate safe filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', str_replace(' ', '_', $name));
$filename = $safeFilename . 'menu.' . $extension;

// Upload directory
$uploadDir = '../public/';
$uploadPath = $uploadDir . $filename;

// Check if file already exists, add number suffix if needed
$counter = 1;
while (file_exists($uploadPath)) {
    $filename = $safeFilename . '_' . $counter . 'menu.' . $extension;
    $uploadPath = $uploadDir . $filename;
    $counter++;
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
    exit;
}

// Insert into database
try {
    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO menu_services (name, image) VALUES (?, ?)");
    $stmt->execute([$name, $filename]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Service added successfully',
        'service_id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    // Delete uploaded file if database insert fails
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    error_log("Error adding service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>