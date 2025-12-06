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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['therapist_id']) || !isset($input['is_active'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$therapist_id = intval($input['therapist_id']);
$is_active = intval($input['is_active']);

try {
    $pdo = db();
    
    // Get admin's branch
    $admin = current_user();
    $admin_branch_id = $admin['branch_id'];
    
    // Check if therapist exists and belongs to admin's branch
    $check_stmt = $pdo->prepare("SELECT id FROM therapists WHERE id = :id AND branch_id = :branch_id");
    $check_stmt->execute([
        ':id' => $therapist_id,
        ':branch_id' => $admin_branch_id
    ]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Therapist not found or unauthorized']);
        exit();
    }
    
    // Update therapist status
    $stmt = $pdo->prepare("UPDATE therapists SET is_active = :is_active WHERE id = :id");
    $stmt->execute([
        ':is_active' => $is_active,
        ':id' => $therapist_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Therapist status updated successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>