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
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get branch_id and status from request
    $branch_id = isset($_GET['branch_id']) && !empty($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
    $status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : 'approved';
    
    if (!$branch_id) {
        echo json_encode(['success' => false, 'message' => 'Branch ID is required']);
        exit();
    }
    
    // Query to get approved bookings from both online and offline bookings
    // First, count total entries
    $count_query = "
        SELECT COUNT(*) as total FROM (
            SELECT b.id 
            FROM bookings b
            WHERE b.branch_id = :branch_id 
            AND b.status = :status
            
            UNION ALL
            
            SELECT ob.id
            FROM offline_bookings ob
            WHERE ob.branch_id = :branch_id2 
            AND ob.status = :status2
        ) as combined
    ";
    
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute([
        ':branch_id' => $branch_id,
        ':status' => $status,
        ':branch_id2' => $branch_id,
        ':status2' => $status
    ]);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Query to get the actual data with pagination
    $query = "
        SELECT 
            t.name as therapist_name,
            r.name as room_name,
            sc.name as category_name,
            b.booking_date,
            b.start_time,
            b.end_time,
            'online' as booking_type
        FROM bookings b
        LEFT JOIN therapists t ON b.therapist_id = t.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN service_categories sc ON b.category_id = sc.id
        WHERE b.branch_id = :branch_id 
        AND b.status = :status
        
        UNION ALL
        
        SELECT 
            t.name as therapist_name,
            r.name as room_name,
            sc.name as category_name,
            ob.booking_date,
            ob.start_time,
            ob.end_time,
            'offline' as booking_type
        FROM offline_bookings ob
        LEFT JOIN therapists t ON ob.therapist_id = t.id
        LEFT JOIN rooms r ON ob.room_id = r.id
        LEFT JOIN service_categories sc ON ob.category_id = sc.id
        WHERE ob.branch_id = :branch_id2 
        AND ob.status = :status2
        
        ORDER BY booking_date DESC, start_time ASC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':branch_id', $branch_id, PDO::PARAM_INT);
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':branch_id2', $branch_id, PDO::PARAM_INT);
    $stmt->bindValue(':status2', $status, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = $total > 0 ? ceil($total / $limit) : 0;
    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total);
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'pagination' => [
            'total' => (int)$total,
            'pages' => (int)$total_pages,
            'current' => (int)$page,
            'from' => (int)$from,
            'to' => (int)$to,
            'limit' => (int)$limit
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