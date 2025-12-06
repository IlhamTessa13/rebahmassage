<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check admin
if (!is_admin()) {
    die('Unauthorized');
}

$admin = current_user();
$branch_id = $admin['branch_id'];

// Get filters
$filter_date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : '';
$filter_status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : '';
$filter_room = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

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
    
    // Query online bookings
    $query_online = "
        SELECT 
            u.full_name as customer_name,
            sc.name as category_name,
            b.duration,
            r.name as room_name,
            b.booking_date,
            b.start_time,
            b.end_time,
            t.name as therapist_name,
            b.status,
            'online' as booking_type
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN service_categories sc ON b.category_id = sc.id
        JOIN rooms r ON b.room_id = r.id
        JOIN therapists t ON b.therapist_id = t.id
        WHERE b.branch_id = :branch_id
    ";
    
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
            ob.name as customer_name,
            sc.name as category_name,
            ob.duration,
            r.name as room_name,
            ob.booking_date,
            ob.start_time,
            ob.end_time,
            t.name as therapist_name,
            ob.status,
            'offline' as booking_type
        FROM offline_bookings ob
        JOIN service_categories sc ON ob.category_id = sc.id
        JOIN rooms r ON ob.room_id = r.id
        JOIN therapists t ON ob.therapist_id = t.id
        WHERE ob.branch_id = :branch_id
    ";
    
    if (!empty($filter_date)) {
        $query_offline .= " AND ob.booking_date = :filter_date";
    }
    if (!empty($filter_status)) {
        $query_offline .= " AND ob.status = :filter_status";
    }
    if ($filter_room > 0) {
        $query_offline .= " AND ob.room_id = :filter_room";
    }
    
    // Union and order
    $query = "($query_online) UNION ALL ($query_offline) 
              ORDER BY booking_date DESC, start_time DESC";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate Excel (CSV format)
    $filename = 'schedules_export_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output UTF-8 BOM for Excel to recognize UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'Customer Name',
        'Category',
        'Duration (mins)',
        'Room',
        'Date',
        'Time',
        'Therapist',
        'Status',
        'Type'
    ]);
    
    // Data rows
    foreach ($schedules as $schedule) {
        fputcsv($output, [
            $schedule['customer_name'],
            $schedule['category_name'],
            $schedule['duration'],
            $schedule['room_name'],
            date('d M Y', strtotime($schedule['booking_date'])),
            substr($schedule['start_time'], 0, 5) . ' - ' . substr($schedule['end_time'], 0, 5),
            $schedule['therapist_name'],
            strtoupper($schedule['status']),
            strtoupper($schedule['booking_type'])
        ]);
    }
    
    fclose($output);
    exit();
    
} catch (Exception $e) {
    error_log("Error in export_schedules_excel: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>