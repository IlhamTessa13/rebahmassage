<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user || !is_customer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../includes/db.php';

try {
    $pdo = db();
    $user_id = $user['id'];
    
    $query = "SELECT 
                b.id,
                b.booking_date,
                b.start_time,
                b.end_time,
                b.duration,
                b.status,
                br.name as branch_name,
                sc.name as category_name,
                r.name as room_name,
                t.name as therapist_name
              FROM bookings b
              JOIN branches br ON b.branch_id = br.id
              JOIN service_categories sc ON b.category_id = sc.id
              JOIN rooms r ON b.room_id = r.id
              JOIN therapists t ON b.therapist_id = t.id
              WHERE b.user_id = :user_id
              ORDER BY b.booking_date DESC, b.start_time DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>