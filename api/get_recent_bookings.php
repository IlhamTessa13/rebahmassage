<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../includes/auth.php';
require_once '../includes/db.php';

ob_clean();

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
    
    // Get today's bookings
    $query = "
        SELECT 
            b.id,
            u.full_name as customer_name,
            sc.name as service_name,
            t.name as therapist_name,
            b.booking_date,
            b.start_time,
            b.status,
            'online' as booking_type
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN service_categories sc ON b.category_id = sc.id
        LEFT JOIN therapists t ON b.therapist_id = t.id
        WHERE b.branch_id = :branch_id 
        AND b.booking_date = :today
        
        UNION ALL
        
        SELECT 
            ob.id,
            ob.name as customer_name,
            sc.name as service_name,
            t.name as therapist_name,
            ob.booking_date,
            ob.start_time,
            ob.status,
            'offline' as booking_type
        FROM offline_bookings ob
        LEFT JOIN service_categories sc ON ob.category_id = sc.id
        LEFT JOIN therapists t ON ob.therapist_id = t.id
        WHERE ob.branch_id = :branch_id2 
        AND ob.booking_date = :today2
        
        ORDER BY start_time ASC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':today' => $today,
        ':branch_id2' => $branch_id,
        ':today2' => $today
    ]);
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
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