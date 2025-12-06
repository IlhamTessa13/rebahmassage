<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/email-config.php';

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

$booking_id = isset($input['id']) ? intval($input['id']) : 0;
$booking_type = isset($input['type']) ? $input['type'] : '';
$room_id = isset($input['room_id']) ? intval($input['room_id']) : 0;
$therapist_id = isset($input['therapist_id']) ? intval($input['therapist_id']) : 0;

// Validate
if ($booking_id <= 0 || !in_array($booking_type, ['online', 'offline'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID or type']);
    exit();
}

if ($room_id <= 0 || $therapist_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Room and therapist are required']);
    exit();
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    
    // ============================================
    // GET OLD BOOKING DATA (untuk email notification)
    // ============================================
    if ($booking_type === 'online') {
        $get_old_data = "SELECT 
            b.id,
            b.user_id,
            u.email as user_email,
            u.full_name as customer_name,
            b.category_id,
            sc.name as category_name,
            b.therapist_id,
            t.name as old_therapist_name,
            b.room_id,
            r.name as old_room_name,
            b.duration,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.status,
            br.name as branch_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN service_categories sc ON b.category_id = sc.id
        LEFT JOIN therapists t ON b.therapist_id = t.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN branches br ON b.branch_id = br.id
        WHERE b.id = :booking_id AND b.branch_id = :branch_id";
    } else {
        $get_old_data = "SELECT 
            b.id,
            NULL as user_id,
            NULL as user_email,
            b.name as customer_name,
            b.category_id,
            sc.name as category_name,
            b.therapist_id,
            t.name as old_therapist_name,
            b.room_id,
            r.name as old_room_name,
            b.duration,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.status,
            br.name as branch_name
        FROM offline_bookings b
        LEFT JOIN service_categories sc ON b.category_id = sc.id
        LEFT JOIN therapists t ON b.therapist_id = t.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN branches br ON b.branch_id = br.id
        WHERE b.id = :booking_id AND b.branch_id = :branch_id";
    }
    
    $stmt_old = $pdo->prepare($get_old_data);
    $stmt_old->execute([
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    $old_booking = $stmt_old->fetch(PDO::FETCH_ASSOC);
    
    if (!$old_booking) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    // Only approved bookings can be edited
    if ($old_booking['status'] !== 'approved') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Only approved bookings can be edited']);
        exit();
    }
    
    // Detect changes
    $room_changed = ($old_booking['room_id'] != $room_id);
    $therapist_changed = ($old_booking['therapist_id'] != $therapist_id);
    
    if (!$room_changed && !$therapist_changed) {
        $pdo->rollBack();
        echo json_encode(['success' => true, 'message' => 'No changes detected']);
        exit();
    }
    
    $booking_date = $old_booking['booking_date'];
    $start_time = $old_booking['start_time'];
    $end_time = $old_booking['end_time'];
    
    // ============================================
    // VALIDATION: Check room availability
    // ============================================
    $check_room = "SELECT COUNT(*) as count FROM (
        SELECT id FROM bookings 
        WHERE id != :booking_id
        AND room_id = :room_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
        UNION ALL
        SELECT id FROM offline_bookings 
        WHERE id != :booking_id
        AND room_id = :room_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
    ) as combined";
    
    $stmt_check = $pdo->prepare($check_room);
    $stmt_check->execute([
        ':booking_id' => $booking_id,
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
    
    // ============================================
    // VALIDATION: Check therapist availability
    // ============================================
    $check_therapist = "SELECT COUNT(*) as count FROM (
        SELECT id FROM bookings 
        WHERE id != :booking_id
        AND therapist_id = :therapist_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
        UNION ALL
        SELECT id FROM offline_bookings 
        WHERE id != :booking_id
        AND therapist_id = :therapist_id 
        AND booking_date = :booking_date 
        AND status IN ('approved', 'pending')
        AND (start_time < :end_time AND end_time > :start_time)
    ) as combined";
    
    $stmt_check2 = $pdo->prepare($check_therapist);
    $stmt_check2->execute([
        ':booking_id' => $booking_id,
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
    
    // ============================================
    // UPDATE BOOKING
    // ============================================
    if ($booking_type === 'online') {
        $update = "UPDATE bookings SET
            room_id = :room_id,
            therapist_id = :therapist_id,
            updated_at = NOW()
        WHERE id = :booking_id AND branch_id = :branch_id";
    } else {
        $update = "UPDATE offline_bookings SET
            room_id = :room_id,
            therapist_id = :therapist_id,
            updated_at = NOW()
        WHERE id = :booking_id AND branch_id = :branch_id";
    }
    
    $stmt = $pdo->prepare($update);
    $result = $stmt->execute([
        ':room_id' => $room_id,
        ':therapist_id' => $therapist_id,
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    if (!$result) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update booking']);
        exit();
    }
    
    // ============================================
    // GET NEW DATA (after update)
    // ============================================
    $new_room_stmt = $pdo->prepare("SELECT name FROM rooms WHERE id = :id");
    $new_room_stmt->execute([':id' => $room_id]);
    $new_room = $new_room_stmt->fetch(PDO::FETCH_ASSOC);
    
    $new_therapist_stmt = $pdo->prepare("SELECT name FROM therapists WHERE id = :id");
    $new_therapist_stmt->execute([':id' => $therapist_id]);
    $new_therapist = $new_therapist_stmt->fetch(PDO::FETCH_ASSOC);
    
    $pdo->commit();
    
    // ============================================
    // SEND EMAIL NOTIFICATION
    // ============================================
    $email_sent = false;
    
    // Only send email for online bookings with valid email
    if ($booking_type === 'online' && !empty($old_booking['user_email'])) {
        $changes_html = "";
        
        if ($room_changed) {
            $changes_html .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'><strong>Room:</strong></td>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef; color: #dc3545;'><s>{$old_booking['old_room_name']}</s></td>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef; color: #28a745;'><strong>{$new_room['name']}</strong></td>
            </tr>";
        }
        
        if ($therapist_changed) {
            $changes_html .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'><strong>Therapist:</strong></td>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef; color: #dc3545;'><s>{$old_booking['old_therapist_name']}</s></td>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef; color: #28a745;'><strong>{$new_therapist['name']}</strong></td>
            </tr>";
        }
        
        $email_sent = sendBookingUpdateEmail(
            $old_booking['user_email'],
            $old_booking['customer_name'],
            $old_booking,
            $changes_html
        );
    }
    
    // ============================================
    // RESPONSE
    // ============================================
    $message = 'Booking updated successfully';
    
    if ($email_sent) {
        $message .= ' and notification email sent to customer';
    } elseif ($booking_type === 'online' && empty($old_booking['user_email'])) {
        $message .= ' (no email sent - customer email not found)';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'email_sent' => $email_sent,
        'changes' => [
            'room_changed' => $room_changed,
            'therapist_changed' => $therapist_changed
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in update_schedule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>