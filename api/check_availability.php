<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!current_user()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../includes/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$branch_id = isset($input['branch_id']) ? intval($input['branch_id']) : 0;
$room_id = isset($input['room_id']) ? intval($input['room_id']) : 0;
$date = isset($input['date']) ? $input['date'] : '';
$start_time = isset($input['start_time']) ? $input['start_time'] : '';
$duration = isset($input['duration']) ? intval($input['duration']) : 0;

// Validate inputs
if ($branch_id <= 0 || $room_id <= 0 || empty($date) || empty($start_time) || $duration <= 0) {
    echo json_encode(['success' => false, 'available' => false, 'message' => 'Invalid input']);
    exit();
}

// ============================================
// VALIDATE OPERATIONAL HOURS
// ============================================
function validateOperationalHours($start_time, $duration) {
    $opening_time = 9;  // 09:00
    $closing_time = 21; // 21:00
    
    // Parse start time
    list($start_hour, $start_minute) = explode(':', $start_time);
    $start_decimal = intval($start_hour) + (intval($start_minute) / 60);
    
    // Calculate end time
    $duration_hours = $duration / 60;
    $end_decimal = $start_decimal + $duration_hours;
    
    // Check if before opening
    if ($start_decimal < $opening_time) {
        return [
            'valid' => false,
            'message' => 'Rebah Massage buka mulai pukul 09:00'
        ];
    }
    
    // Check if exceeds closing time
    if ($end_decimal > $closing_time) {
        // Calculate max start time
        $max_start_decimal = $closing_time - $duration_hours;
        $max_start_hour = floor($max_start_decimal);
        $max_start_minute = ($max_start_decimal - $max_start_hour) * 60;
        $max_start_formatted = sprintf('%02d:%02d', $max_start_hour, $max_start_minute);
        
        return [
            'valid' => false,
            'message' => "Untuk durasi {$duration} menit, waktu booking terakhir adalah pukul {$max_start_formatted} agar selesai sebelum jam tutup (21:00)"
        ];
    }
    
    return ['valid' => true];
}

// Validate operational hours first
$time_validation = validateOperationalHours($start_time, $duration);
if (!$time_validation['valid']) {
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => $time_validation['message']
    ]);
    exit();
}

try {
    $pdo = db();
    
    // Calculate end time
    $start_datetime = new DateTime($date . ' ' . $start_time);
    $end_datetime = clone $start_datetime;
    $end_datetime->add(new DateInterval('PT' . $duration . 'M'));
    $end_time = $end_datetime->format('H:i:s');
    
    // ============================================
    // CHECK ROOM AVAILABILITY (Online + Offline)
    // ============================================
    
    // Check online bookings
    $query_online = "SELECT COUNT(*) as count FROM bookings 
                    WHERE room_id = :room_id 
                    AND booking_date = :booking_date 
                    AND status IN ('approved', 'pending')
                    AND (start_time < :end_time AND end_time > :start_time)";
    
    $stmt = $pdo->prepare($query_online);
    $stmt->execute([
        ':room_id' => $room_id,
        ':booking_date' => $date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    $online_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check offline bookings
    $query_offline = "SELECT COUNT(*) as count FROM offline_bookings 
                     WHERE room_id = :room_id 
                     AND booking_date = :booking_date 
                     AND status IN ('approved', 'pending')
                     AND (start_time < :end_time AND end_time > :start_time)";
    
    $stmt = $pdo->prepare($query_offline);
    $stmt->execute([
        ':room_id' => $room_id,
        ':booking_date' => $date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    $offline_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Room available if not booked in both online & offline
    $available = ($online_result['count'] == 0 && $offline_result['count'] == 0);
    
    echo json_encode([
        'success' => true,
        'available' => $available,
        'message' => $available ? 'Time slot is available' : 'Time slot is not available',
        'debug' => [
            'online_bookings' => $online_result['count'],
            'offline_bookings' => $offline_result['count'],
            'calculated_end_time' => $end_time
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>