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
$serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Validate required fields
if ($serviceId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Service name is required']);
    exit;
}

try {
    $pdo = db();
    
    // Get current service data
    $stmt = $pdo->prepare("SELECT image FROM menu_services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $currentService = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentService) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        exit;
    }
    
    $oldImage = $currentService['image'];
    $newFilename = $oldImage; // Keep old image by default
    
    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
        $newFilename = $safeFilename . 'menu.' . $extension;
        
        // Upload directory
        $uploadDir = '../public/';
        $uploadPath = $uploadDir . $newFilename;
        
        // Check if file already exists, add number suffix if needed
        $counter = 1;
        while (file_exists($uploadPath) && $newFilename !== $oldImage) {
            $newFilename = $safeFilename . '_' . $counter . 'menu.' . $extension;
            $uploadPath = $uploadDir . $newFilename;
            $counter++;
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
        
        // Delete old image if different from new one
        if ($oldImage !== $newFilename && file_exists($uploadDir . $oldImage)) {
            unlink($uploadDir . $oldImage);
        }
    }
    
    // Update database
    $stmt = $pdo->prepare("UPDATE menu_services SET name = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $newFilename, $serviceId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Service updated successfully'
    ]);
    
} catch (PDOException $e) {
    // Delete newly uploaded file if database update fails
    if (isset($uploadPath) && file_exists($uploadPath) && $newFilename !== $oldImage) {
        unlink($uploadPath);
    }
    
    error_log("Error updating service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>