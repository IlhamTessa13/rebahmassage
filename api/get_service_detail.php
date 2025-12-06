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

// Get service ID
$serviceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($serviceId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, name, image, created_at FROM menu_services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($service) {
        echo json_encode([
            'success' => true,
            'service' => $service
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Service not found'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error fetching service detail: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>