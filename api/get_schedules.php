<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

// Enable error logging
error_log("=== Get Schedules API Called ===");

// Check admin
if (!is_admin()) {
    error_log("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin = current_user();
$branch_id = $admin['branch_id'];
error_log("Admin branch_id: " . $branch_id);

// Get parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$filter_date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : '';
$filter_status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : '';
$filter_room = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

$offset = ($page - 1) * $limit;

error_log("Filters - Date: $filter_date, Status: $filter_status, Room: $filter_room");

try {
    $pdo = db();
    
    // Build params
    $params = [':branch_id' => $branch_id];
    
    if (!empty($filter_date)) {
        $params[':filter_date'] = $filter_date;
    }
    
    if (!empty($filter_status)) {
        $params[':filter_status'] = $filter_status;
    }
    
    if ($filter_room > 0) {
        $params[':filter_room'] = $filter_room;
    }
    
    error_log("Query params: " . print_r($params, true));
    
    // Query online bookings
    $query_online = "
        SELECT 
            b.id,
            u.full_name as customer_name,
            sc.name as category_name,
            b.category_id,
            b.duration,
            r.name as room_name,
            b.room_id,
            b.booking_date,
            b.start_time,
            b.end_time,
            t.name as therapist_name,
            b.therapist_id,
            b.status,
            'online' as booking_type,
            b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN service_categories sc ON b.category_id = sc.id
        JOIN rooms r ON b.room_id = r.id
        JOIN therapists t ON b.therapist_id = t.id
        WHERE b.branch_id = :branch_id
    ";
    
    // Add additional filters for online bookings
    if (!empty($filter_date)) {
        $query_online .= " AND b.booking_date = :filter_date";
    }
    if (!empty($filter_status)) {
        $query_online .= " AND b.status = :filter_status";
    }
    if ($filter_room > 0) {
        $query_online .= " AND b.room_id = :filter_room";
    }
    
    // Query offline bookings
    $query_offline = "
        SELECT 
            ob.id,
            ob.name as customer_name,
            sc.name as category_name,
            ob.category_id,
            ob.duration,
            r.name as room_name,
            ob.room_id,
            ob.booking_date,
            ob.start_time,
            ob.end_time,
            t.name as therapist_name,
            ob.therapist_id,
            ob.status,
            'offline' as booking_type,
            ob.created_at
        FROM offline_bookings ob
        JOIN service_categories sc ON ob.category_id = sc.id
        JOIN rooms r ON ob.room_id = r.id
        JOIN therapists t ON ob.therapist_id = t.id
        WHERE ob.branch_id = :branch_id
    ";
    
    // Add additional filters for offline bookings
    if (!empty($filter_date)) {
        $query_offline .= " AND ob.booking_date = :filter_date";
    }
    if (!empty($filter_status)) {
        $query_offline .= " AND ob.status = :filter_status";
    }
    if ($filter_room > 0) {
        $query_offline .= " AND ob.room_id = :filter_room";
    };
    
    // Union and order
    $query = "($query_online) UNION ALL ($query_offline) 
              ORDER BY booking_date DESC, start_time DESC 
              LIMIT :limit OFFSET :offset";
    
    error_log("Executing query with limit: $limit, offset: $offset");
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($schedules) . " schedules");
    
    // Count total
    $count_query = "
        SELECT COUNT(*) as total FROM (
            ($query_online) UNION ALL ($query_offline)
        ) as combined
    ";
    
    $count_stmt = $pdo->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    error_log("Total records: " . $total);
    
    $total_pages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $total_pages,
            'from' => $offset + 1,
            'to' => min($offset + $limit, $total)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_schedules: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>