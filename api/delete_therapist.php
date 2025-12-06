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
if (!isset($input['therapist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing therapist ID']);
    exit();
}

$therapist_id = intval($input['therapist_id']);

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
    
    // Check if therapist has any bookings
    $booking_check = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE therapist_id = :id");
    $booking_check->execute([':id' => $therapist_id]);
    $booking_result = $booking_check->fetch(PDO::FETCH_ASSOC);
    
    if ($booking_result['count'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete therapist with existing bookings. Please deactivate instead.'
        ]);
        exit();
    }
    
    // Check offline bookings
    $offline_check = $pdo->prepare("SELECT COUNT(*) as count FROM offline_bookings WHERE therapist_id = :id");
    $offline_check->execute([':id' => $therapist_id]);
    $offline_result = $offline_check->fetch(PDO::FETCH_ASSOC);
    
    if ($offline_result['count'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete therapist with existing offline bookings. Please deactivate instead.'
        ]);
        exit();
    }
    
    // Delete therapist
    $stmt = $pdo->prepare("DELETE FROM therapists WHERE id = :id");
    $stmt->execute([':id' => $therapist_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Therapist deleted successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>