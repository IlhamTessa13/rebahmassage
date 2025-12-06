<?php
header('Content-Type: application/json');

require_once '../includes/db.php';

try {
    $pdo = db();
    
    $stmt = $pdo->prepare("SELECT id, name, image FROM menu_services ORDER BY id");
    $stmt->execute();
    
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'services' => $services,
        'count' => count($services)
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching menu services: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load services',
        'services' => []
    ]);
}
?>