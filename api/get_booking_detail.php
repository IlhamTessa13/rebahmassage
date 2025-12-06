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
            b.therapist_id,
            b.room_id,
            b.duration,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :booking_id AND b.branch_id = :branch_id";
    } else {
        $query = "SELECT 
            id,
            name as customer_name,
            category_id,
            therapist_id,
            room_id,
            duration,
            booking_date,
            start_time,
            end_time,
            status
        FROM offline_bookings
        WHERE id = :booking_id AND branch_id = :branch_id";
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