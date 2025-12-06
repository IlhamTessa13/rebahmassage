<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

// Check if user is logged in and is customer
$user = current_user();
if (!$user || !is_customer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../includes/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$user_id = $user['id'];
$branch_id = isset($input['branch_id']) ? intval($input['branch_id']) : 0;
$category_id = isset($input['category_id']) ? intval($input['category_id']) : 0;
$therapist_id = isset($input['therapist_id']) ? intval($input['therapist_id']) : 0;
$room_id = isset($input['room_id']) ? intval($input['room_id']) : 0;
$duration = isset($input['duration']) ? intval($input['duration']) : 0;
$booking_date = isset($input['booking_date']) ? $input['booking_date'] : '';
$start_time = isset($input['start_time']) ? $input['start_time'] : '';

// Validate inputs
if ($branch_id <= 0 || $category_id <= 0 || $therapist_id <= 0 || $room_id <= 0 || 
    $duration <= 0 || empty($booking_date) || empty($start_time)) {
    echo json_encode([
        'success' => false, 
        'message' => 'All fields are required'
    ]);
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

// Validate operational hours
$time_validation = validateOperationalHours($start_time, $duration);
if (!$time_validation['valid']) {
    echo json_encode([
        'success' => false,
        'message' => $time_validation['message']
    ]);
    exit();
}

try {
    $pdo = db();
    
    // START TRANSACTION untuk mencegah race condition
    $pdo->beginTransaction();
    
    // Calculate end time
    $start_datetime = new DateTime($booking_date . ' ' . $start_time);
    $end_datetime = clone $start_datetime;
    $end_datetime->add(new DateInterval('PT' . $duration . 'M'));
    $end_time = $end_datetime->format('H:i:s');
    
    // ============================================
    // CHECK ROOM AVAILABILITY (Online + Offline)
    // ============================================
    
    // Check online bookings
    $check_room_online = "SELECT COUNT(*) as count FROM bookings 
                         WHERE room_id = :room_id 
                         AND booking_date = :booking_date 
                         AND status IN ('approved', 'pending')
                         AND (start_time < :end_time AND end_time > :start_time)
                         FOR UPDATE"; // Lock rows
    
    $stmt = $pdo->prepare($check_room_online);
    $stmt->execute([
        ':room_id' => $room_id,
        ':booking_date' => $booking_date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    $room_online = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check offline bookings
    $check_room_offline = "SELECT COUNT(*) as count FROM offline_bookings 
                          WHERE room_id = :room_id 
                          AND booking_date = :booking_date 
                          AND status IN ('approved', 'pending')
                          AND (start_time < :end_time AND end_time > :start_time)
                          FOR UPDATE"; // Lock rows
    
    $stmt = $pdo->prepare($check_room_offline);
    $stmt->execute([
        ':room_id' => $room_id,
        ':booking_date' => $booking_date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    $room_offline = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if room is occupied
    if ($room_online['count'] > 0 || $room_offline['count'] > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Room is not available for this time slot'
        ]);
        exit();
    }
    
    // ============================================
    // CHECK THERAPIST AVAILABILITY (Online + Offline)
    // ============================================
    
    // Check online bookings
    $check_therapist_online = "SELECT COUNT(*) as count FROM bookings 
                              WHERE therapist_id = :therapist_id 
                              AND booking_date = :booking_date 
                              AND status IN ('approved', 'pending')
                              AND (start_time < :end_time AND end_time > :start_time)
                              FOR UPDATE"; // Lock rows
    
    $stmt = $pdo->prepare($check_therapist_online);
    $stmt->execute([
        ':therapist_id' => $therapist_id,
        ':booking_date' => $booking_date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    $therapist_online = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check offline bookings
    $check_therapist_offline = "SELECT COUNT(*) as count FROM offline_bookings 
                               WHERE therapist_id = :therapist_id 
                               AND booking_date = :booking_date 
                               AND status IN ('approved', 'pending')
                               AND (start_time < :end_time AND end_time > :start_time)
                               FOR UPDATE"; // Lock rows
    
    $stmt = $pdo->prepare($check_therapist_offline);
    $stmt->execute([
        ':therapist_id' => $therapist_id,
        ':booking_date' => $booking_date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    $therapist_offline = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if therapist is occupied
    if ($therapist_online['count'] > 0 || $therapist_offline['count'] > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Therapist is not available for this time slot'
        ]);
        exit();
    }
    
    // ============================================
    // INSERT BOOKING
    // ============================================
    
    $insert_query = "INSERT INTO bookings (
        user_id, 
        branch_id, 
        category_id, 
        therapist_id, 
        room_id, 
        duration, 
        booking_date, 
        start_time, 
        end_time, 
        status, 
        booking_type,
        created_at
    ) VALUES (
        :user_id,
        :branch_id,
        :category_id,
        :therapist_id,
        :room_id,
        :duration,
        :booking_date,
        :start_time,
        :end_time,
        'pending',
        'online',
        NOW()
    )";
    
    $stmt = $pdo->prepare($insert_query);
    $result = $stmt->execute([
        ':user_id' => $user_id,
        ':branch_id' => $branch_id,
        ':category_id' => $category_id,
        ':therapist_id' => $therapist_id,
        ':room_id' => $room_id,
        ':duration' => $duration,
        ':booking_date' => $booking_date,
        ':start_time' => $start_time,
        ':end_time' => $end_time
    ]);
    
    if ($result) {
        $booking_id = $pdo->lastInsertId();
        
        // COMMIT transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $booking_id
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create booking'
        ]);
    }
    
} catch (Exception $e) {
    // Rollback jika ada error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>