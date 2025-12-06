<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/auth.php';
require_once '../includes/db.php';

ob_clean();

// Check if admin is logged in
if (!current_user() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['name']) || empty($input['branch_id']) || 
        empty($input['no']) || empty($input['gender'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    $name = trim($input['name']);
    $branch_id = intval($input['branch_id']);
    $no = trim($input['no']);
    $gender = strtolower(trim($input['gender']));
    
    // Validate gender
    if (!in_array($gender, ['male', 'female'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid gender']);
        exit();
    }
    
    // Validate WhatsApp number format
    if (!preg_match('/^62\d{9,13}$/', $no)) {
        echo json_encode(['success' => false, 'message' => 'Invalid WhatsApp number format. Must start with 62 and be 11-15 digits']);
        exit();
    }
    
    $pdo = db();
    
    // Check if therapist with same phone number already exists
    $check_query = "SELECT id FROM therapists WHERE no = :no";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([':no' => $no]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Therapist with this WhatsApp number already exists']);
        exit();
    }
    
    // Insert new therapist
    $insert_query = "INSERT INTO therapists (name, branch_id, no, gender) 
                     VALUES (:name, :branch_id, :no, :gender)";
    
    $insert_stmt = $pdo->prepare($insert_query);
    $result = $insert_stmt->execute([
        ':name' => $name,
        ':branch_id' => $branch_id,
        ':no' => $no,
        ':gender' => $gender
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Therapist created successfully',
            'therapist_id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create therapist']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}