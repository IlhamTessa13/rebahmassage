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

// Get parameters
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$booking_type = isset($_GET['type']) ? $_GET['type'] : '';

// Validate
if ($booking_id <= 0 || !in_array($booking_type, ['online', 'offline'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID or type']);
    exit();
}

try {
    $pdo = db();
    
    if ($booking_type === 'online') {
        $query = "SELECT 
            b.id,
            u.full_name as customer_name,
            b.category_id,
            c.name as category_name,
            b.therapist_id,
            t.name as therapist_name,
            b.room_id,
            r.name as room_name,
            b.duration,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN service_categories c ON b.category_id = c.id
        LEFT JOIN therapists t ON b.therapist_id = t.id
        LEFT JOIN rooms r ON b.room_id = r.id
        WHERE b.id = :booking_id AND b.branch_id = :branch_id";
    } else {
        $query = "SELECT 
            ob.id,
            ob.name as customer_name,
            ob.category_id,
            c.name as category_name,
            ob.therapist_id,
            t.name as therapist_name,
            ob.room_id,
            r.name as room_name,
            ob.duration,
            ob.booking_date,
            ob.start_time,
            ob.end_time,
            ob.status
        FROM offline_bookings ob
        LEFT JOIN service_categories c ON ob.category_id = c.id
        LEFT JOIN therapists t ON ob.therapist_id = t.id
        LEFT JOIN rooms r ON ob.room_id = r.id
        WHERE ob.id = :booking_id AND ob.branch_id = :branch_id";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':branch_id' => $branch_id
    ]);
    
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        echo json_encode(['success' => true, 'booking' => $booking]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>