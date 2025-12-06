<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

// Check admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin = current_user();
$branch_id = $admin['branch_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$customer_name = isset($input['customer_name']) ? trim($input['customer_name']) : '';
$category_id = isset($input['category_id']) ? intval($input['category_id']) : 0;
$therapist_id = isset($input['therapist_id']) ? intval($input['therapist_id']) : 0;
$room_id = isset($input['room_id']) ? intval($input['room_id']) : 0;
$duration = isset($input['duration']) ? intval($input['duration']) : 0;
$booking_date = isset($input['booking_date']) ? $input['booking_date'] : '';
$start_time = isset($input['start_time']) ? $input['start_time'] : '';

// Validate
if (empty($customer_name)) {
    echo json_encode(['success' => false, 'message' => 'Customer name is required']);
    exit();
}

if ($category_id <= 0 || $therapist_id <= 0 || $room_id <= 0 || 
    $duration <= 0 || empty($booking_date) || empty($start_time)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate duration
if (!in_array($duration, [60, 90, 120])) {
    echo json_encode(['success' => false, 'message' => 'Invalid duration. Must be 60, 90, or 120 minutes']);
    exit();
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    
    // Calculate end time
    $start_datetime = new DateTime($booking_date . ' ' . $start_time);
    $end_datetime = clone $start_datetime;
    $end_datetime->add(new DateInterval('PT' . $duration . 'M'));
    $end_time = $end_datetime->format('H:i:s');
    
    // Validate operating hours (9:00 - 21:00)
    $start_hour = (int)$start_datetime->format('H');
    $end_hour = (int)$end_datetime->format('H');
    $end_minute = (int)$end_datetime->format('i');
    
    if ($start_hour < 9) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Booking cannot start before 9:00 AM']);
        exit();
    }
    
    if ($end_hour > 21 || ($end_hour == 21 && $end_minute > 0)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Booking must end by 9:00 PM (21:00)']);
        exit();
    }
    
    // Check for exact duplicate booking
    $check_duplicate = "SELECT COUNT(*) as count FROM (
        SELECT id FROM bookings 
        WHERE branch_id = :branch_id
        AND therapist_id = :therapist_id
        AND room_id = :room_id
        AND booking_date = :booking_date
        AND start_time = :start_time
        AND end_time = :end_time
        AND duration = :duration
        AND status NOT IN ('cancelled')
        UNION ALL
        SELECT id FROM offline_bookings 
        WHERE branch_id = :branch_id
        AND therapist_id = :therapist_id
        AND room_id = :room_id
        AND booking_date = :booking_date
        AND start_time = :start_time
        AND end_time = :end_time
        AND duration = :duration
        AND status NOT IN ('cancelled')
    ) as combined";
    
    $stmt_dup = $pdo->prepare($check_duplicate);
    $stmt_dup->execute([
        ':branch_id' => $branch_id,
        ':therapist_id' => $therapist_id,
        ':room_id' => $room_id,
        ':booking_date' => $booking_date,
        ':start_time' => $start_time,
        ':end_time' => $end_time,
        ':duration' => $duration
    ]);
    
    if ($stmt_dup->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Duplicate booking detected! A booking with the same details already exists.'
        ]);
        exit();
    }
    
    // Check room availability
    $check_room = "SELECT COUNT(*) as count FROM (
        SELECT id FROM bookings 
        WHERE branch_id = :branch_id
        AND room_id = :room_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
        UNION ALL
        SELECT id FROM offline_bookings 
        WHERE branch_id = :branch_id
        AND room_id = :room_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
    ) as combined";
    
    $stmt_check = $pdo->prepare($check_room);
    $stmt_check->execute([
        ':branch_id' => $branch_id,
        ':room_id' => $room_id,
        ':booking_date' => $booking_date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    if ($stmt_check->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Room is not available at this time']);
        exit();
    }
    
    // Check therapist availability
    $check_therapist = "SELECT COUNT(*) as count FROM (
        SELECT id FROM bookings 
        WHERE branch_id = :branch_id
        AND therapist_id = :therapist_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
        UNION ALL
        SELECT id FROM offline_bookings 
        WHERE branch_id = :branch_id
        AND therapist_id = :therapist_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
    ) as combined";
    
    $stmt_check2 = $pdo->prepare($check_therapist);
    $stmt_check2->execute([
        ':branch_id' => $branch_id,
        ':therapist_id' => $therapist_id,
        ':booking_date' => $booking_date,
        ':end_time' => $end_time,
        ':start_time' => $start_time
    ]);
    
    if ($stmt_check2->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Therapist is not available at this time']);
        exit();
    }
    
    // Insert offline booking (auto-approved)
    $insert = "INSERT INTO offline_bookings 
        (name, branch_id, category_id, therapist_id, room_id, duration, 
         booking_date, start_time, end_time, status, created_at, updated_at)
        VALUES 
        (:name, :branch_id, :category_id, :therapist_id, :room_id, :duration, 
         :booking_date, :start_time, :end_time, 'approved', NOW(), NOW())";
    
    $stmt = $pdo->prepare($insert);
    $result = $stmt->execute([
        ':name' => $customer_name,
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
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Offline booking created successfully',
            'booking_id' => $pdo->lastInsertId()
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to create booking']);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in create_offline_booking: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>