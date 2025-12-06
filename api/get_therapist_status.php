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

// Get therapist_id from query parameter
if (!isset($_GET['therapist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing therapist ID']);
    exit();
}

$therapist_id = intval($_GET['therapist_id']);

try {
    $pdo = db();
    
    // Get admin's branch
    $admin = current_user();
    $admin_branch_id = $admin['branch_id'];
    
    // Get therapist status
    $stmt = $pdo->prepare("
        SELECT id, name, is_active 
        FROM therapists 
        WHERE id = :id AND branch_id = :branch_id
    ");
    $stmt->execute([
        ':id' => $therapist_id,
        ':branch_id' => $admin_branch_id
    ]);
    
    $therapist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$therapist) {
        echo json_encode(['success' => false, 'message' => 'Therapist not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'therapist' => [
            'id' => $therapist['id'],
            'name' => $therapist['name'],
            'is_active' => (bool)$therapist['is_active']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>