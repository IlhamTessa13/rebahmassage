<?php
// Strict error handling untuk production
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Pastikan no output sebelum header
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Initialize response
$response = ['success' => false, 'message' => 'Unknown error'];

try {
    // Include required files
    if (!file_exists('../includes/db.php')) {
        throw new Exception('Database configuration file not found');
    }
    require_once '../includes/db.php';
    
    if (!file_exists('../includes/auth.php')) {
        throw new Exception('Authentication file not found');
    }
    require_once '../includes/auth.php';

    // Check authentication
    if (!function_exists('require_login')) {
        throw new Exception('Authentication functions not available');
    }
    
    require_login();
    
    if (!function_exists('is_admin') || !is_admin()) {
        throw new Exception('Unauthorized access');
    }

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate service name
    if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
        throw new Exception('Service name is required');
    }
    
    $name = trim($_POST['name']);
    
    if (strlen($name) > 100) {
        throw new Exception('Service name is too long (max 100 characters)');
    }

    // Validate image upload
    if (!isset($_FILES['image'])) {
        throw new Exception('No image file uploaded');
    }
    
    $file = $_FILES['image'];
    
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Image size exceeds maximum limit');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('Image was only partially uploaded');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No image file selected');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('Server configuration error (no temp directory)');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('Failed to write file to disk');
            default:
                throw new Exception('Image upload failed');
        }
    }

    // Validate file size (5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Image size must be less than 5MB');
    }
    
    if ($file['size'] == 0) {
        throw new Exception('Image file is empty');
    }

    // Validate file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Invalid image format. Only JPG, PNG, and GIF are allowed');
    }

    // Validate MIME type
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Invalid image type detected');
        }
    }

    // Prepare upload directory
    $uploadDir = dirname(__DIR__) . '/public/';
    
    // Check if directory exists and is writable
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            throw new Exception('Upload directory does not exist and cannot be created');
        }
    }
    
    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable');
    }

    // Generate safe filename
    $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    $safeFilename = substr($safeFilename, 0, 50); // Limit length
    $filename = $safeFilename . '_menu_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    // Ensure unique filename
    $counter = 1;
    while (file_exists($uploadPath)) {
        $filename = $safeFilename . '_menu_' . time() . '_' . $counter . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        $counter++;
        
        if ($counter > 100) {
            throw new Exception('Failed to generate unique filename');
        }
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save image file');
    }

    // Set proper permissions
    @chmod($uploadPath, 0644);

    // Database insertion
    if (!function_exists('db')) {
        throw new Exception('Database connection function not available');
    }
    
    $pdo = db();
    
    if (!$pdo) {
        // Delete uploaded file
        @unlink($uploadPath);
        throw new Exception('Database connection failed');
    }
    
    $stmt = $pdo->prepare("INSERT INTO menu_services (name, image, created_at) VALUES (?, ?, NOW())");
    
    if (!$stmt) {
        @unlink($uploadPath);
        throw new Exception('Failed to prepare database statement');
    }
    
    $result = $stmt->execute([$name, $filename]);
    
    if (!$result) {
        @unlink($uploadPath);
        $errorInfo = $stmt->errorInfo();
        throw new Exception('Failed to save service: ' . ($errorInfo[2] ?? 'Unknown database error'));
    }
    
    $serviceId = $pdo->lastInsertId();
    
    if (!$serviceId) {
        @unlink($uploadPath);
        throw new Exception('Failed to retrieve service ID');
    }
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Service added successfully',
        'service_id' => $serviceId
    ];
    
} catch (Exception $e) {
    // Clean up uploaded file on error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        @unlink($uploadPath);
    }
    
    // Error response
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // Log error (jika error_log tersedia)
    @error_log('Add service error: ' . $e->getMessage());
}

// Clean output buffer
ob_clean();

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);

// End output buffering
ob_end_flush();
exit;