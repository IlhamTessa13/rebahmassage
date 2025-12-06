<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../includes/db.php';

ob_clean();

try {
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Update bookings - cancel expired pending bookings
    $update_bookings = "
        UPDATE bookings 
        SET status = 'cancelled'
        WHERE status = 'pending'
        AND (
            (booking_date < :today)
            OR (booking_date = :today2 AND end_time < :current_time)
        )
    ";
    
    $stmt = $pdo->prepare($update_bookings);
    $cancelled_bookings = $stmt->execute([
        ':today' => $today,
        ':today2' => $today,
        ':current_time' => $current_time
    ]);
    $bookings_affected = $stmt->rowCount();
    
    // Update offline_bookings - cancel expired pending bookings
    $update_offline = "
        UPDATE offline_bookings 
        SET status = 'cancelled'
        WHERE status = 'pending'
        AND (
            (booking_date < :today)
            OR (booking_date = :today2 AND end_time < :current_time)
        )
    ";
    
    $stmt = $pdo->prepare($update_offline);
    $cancelled_offline = $stmt->execute([
        ':today' => $today,
        ':today2' => $today,
        ':current_time' => $current_time
    ]);
    $offline_affected = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Expired bookings cancelled successfully',
        'cancelled' => [
            'bookings' => $bookings_affected,
            'offline_bookings' => $offline_affected,
            'total' => $bookings_affected + $offline_affected
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}