<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
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
    $pdo = db();
    $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
    $today = date('Y-m-d');
    
    if (!$branch_id) {
        echo json_encode(['success' => false, 'message' => 'Branch ID required']);
        exit();
    }
    
    // 1. Total Reservasi Hari Ini (approved + completed)
    $reservasi_query = "
        SELECT COUNT(*) as total FROM (
            SELECT id FROM bookings 
            WHERE branch_id = :branch_id 
            AND booking_date = :today 
            AND status IN ('approved', 'completed')
            
            UNION ALL
            
            SELECT id FROM offline_bookings 
            WHERE branch_id = :branch_id2 
            AND booking_date = :today2 
            AND status IN ('approved', 'completed')
        ) as combined
    ";
    
    $stmt = $pdo->prepare($reservasi_query);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':today' => $today,
        ':branch_id2' => $branch_id,
        ':today2' => $today
    ]);
    $total_reservasi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. Total Customer Hari Ini (completed only)
    $customer_query = "
        SELECT COUNT(DISTINCT user_id) as total 
        FROM bookings 
        WHERE branch_id = :branch_id 
        AND booking_date = :today 
        AND status = 'completed'
        AND user_id IS NOT NULL
    ";
    
    $stmt = $pdo->prepare($customer_query);
    $stmt->execute([':branch_id' => $branch_id, ':today' => $today]);
    $total_customer = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 3. Total Therapist Aktif
    $therapist_query = "
        SELECT COUNT(*) as total 
        FROM therapists 
        WHERE branch_id = :branch_id 
        AND is_active = 1
    ";
    
    $stmt = $pdo->prepare($therapist_query);
    $stmt->execute([':branch_id' => $branch_id]);
    $total_therapist = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'reservasi' => (int)$total_reservasi,
            'customer' => (int)$total_customer,
            'therapist' => (int)$total_therapist
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}