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
if (empty($customer_name) || $category_id <= 0 || $therapist_id <= 0 || 
    $room_id <= 0 || $duration <= 0 || empty($booking_date) || empty($start_time)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
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
    
    // Check room availability
    $check_room = "SELECT COUNT(*) as count FROM (
        SELECT id FROM bookings 
        WHERE room_id = :room_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
        UNION ALL
        SELECT id FROM offline_bookings 
        WHERE room_id = :room_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
    ) as combined";
    
    $stmt_check = $pdo->prepare($check_room);
    $stmt_check->execute([
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
        WHERE therapist_id = :therapist_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
        UNION ALL
        SELECT id FROM offline_bookings 
        WHERE therapist_id = :therapist_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
    ) as combined";
    
    $stmt_check2 = $pdo->prepare($check_therapist);
    $stmt_check2->execute([
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
    
    // Insert offline booking
    $insert = "INSERT INTO offline_bookings (
        name, branch_id, category_id, therapist_id, room_id,
        duration, booking_date, start_time, end_time, status, created_at
    ) VALUES (
        :name, :branch_id, :category_id, :therapist_id, :room_id,
        :duration, :booking_date, :start_time, :end_time, 'approved', NOW()
    )";
    
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
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>