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
    
    // Get therapists with most bookings today
    $query = "
        SELECT 
            t.id,
            t.name,
            COUNT(*) as bookings_count,
            t.is_active as status
        FROM (
            SELECT therapist_id 
            FROM bookings 
            WHERE branch_id = :branch_id 
            AND booking_date = :today 
            AND status IN ('approved', 'completed')
            
            UNION ALL
            
            SELECT therapist_id 
            FROM offline_bookings 
            WHERE branch_id = :branch_id2 
            AND booking_date = :today2 
            AND status IN ('approved', 'completed')
        ) as all_bookings
        JOIN therapists t ON all_bookings.therapist_id = t.id
        GROUP BY t.id, t.name, t.is_active
        ORDER BY bookings_count DESC
        LIMIT 4
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':today' => $today,
        ':branch_id2' => $branch_id,
        ':today2' => $today
    ]);
    
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format status
    foreach ($therapists as &$therapist) {
        $therapist['status'] = $therapist['status'] == 1 ? 'active' : 'inactive';
    }
    
    echo json_encode([
        'success' => true,
        'therapists' => $therapists
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