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

// Get JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$serviceId = isset($data['service_id']) ? intval($data['service_id']) : 0;

// Validate service ID
if ($serviceId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit;
}

try {
    $pdo = db();
    
    // Get current service data to delete image
    $stmt = $pdo->prepare("SELECT image FROM menu_services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        exit;
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM menu_services WHERE id = ?");
    $stmt->execute([$serviceId]);
    
    // Delete image file
    $imagePath = '../public/' . $service['image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Service deleted successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Error deleting service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>