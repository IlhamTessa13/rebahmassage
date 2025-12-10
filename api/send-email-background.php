<?php
// admin/api/send-email-background.php
// This script runs in background to send emails

require_once '../includes/email-config.php';
require_once '../includes/db.php';

// Get parameters from URL
$type = isset($_GET['type']) ? $_GET['type'] : '';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$booking_type = isset($_GET['booking_type']) ? $_GET['booking_type'] : 'online';
$reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : '';

if (!$type || !$booking_id) {
    error_log("Background email error: Missing parameters");
    exit();
}

try {
    $pdo = db();
    
    // Get booking details
    if ($booking_type === 'online') {
        $query = "SELECT 
                    b.id,
                    b.booking_date,
                    b.start_time,
                    b.end_time,
                    b.duration,
                    b.branch_id,
                    u.full_name as customer_name,
                    u.email as customer_email,
                    br.name as branch_name,
                    sc.name as category_name,
                    r.name as room_name,
                    t.name as therapist_name
                  FROM bookings b
                  JOIN users u ON b.user_id = u.id
                  JOIN branches br ON b.branch_id = br.id
                  JOIN service_categories sc ON b.category_id = sc.id
                  JOIN rooms r ON b.room_id = r.id
                  JOIN therapists t ON b.therapist_id = t.id
                  WHERE b.id = :booking_id";
    } else {
        error_log("Background email: Offline booking, no email needed");
        exit();
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        error_log("Background email error: Booking not found - ID: $booking_id");
        exit();
    }
    
    // Send emails based on type
    switch ($type) {
        case 'cancel_customer':
            // Customer cancelled - send to customer + admins
            try {
                sendCancellationConfirmationToCustomer(
                    $booking['customer_email'],
                    $booking['customer_name'],
                    $booking
                );
                error_log("✓ Customer cancellation email sent to: {$booking['customer_email']}");
            } catch (Exception $e) {
                error_log("✗ Failed to send customer email: " . $e->getMessage());
            }
            
            // Get all admins for this branch
            $admin_query = "SELECT email, full_name 
                           FROM users 
                           WHERE branch_id = :branch_id 
                           AND role = 'admin' 
                           AND email_verified = 1";
            
            $admin_stmt = $pdo->prepare($admin_query);
            $admin_stmt->execute([':branch_id' => $booking['branch_id']]);
            $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $success_count = 0;
            foreach ($admins as $admin) {
                try {
                    if (sendCancellationNotificationToAdmin(
                        $admin['email'],
                        $admin['full_name'],
                        $booking,
                        $booking['customer_name']
                    )) {
                        $success_count++;
                        error_log("✓ Admin notification sent to: {$admin['email']}");
                    }
                } catch (Exception $e) {
                    error_log("✗ Failed to send admin email to {$admin['email']}: " . $e->getMessage());
                }
            }
            
            error_log("Email summary: {$success_count}/" . count($admins) . " admins notified for branch {$booking['branch_id']}");
            break;
            
        case 'accept':
            // Admin accepted booking
            try {
                sendBookingApprovalEmail(
                    $booking['customer_email'],
                    $booking['customer_name'],
                    $booking
                );
                error_log("✓ Approval email sent to: {$booking['customer_email']}");
            } catch (Exception $e) {
                error_log("✗ Failed to send approval email: " . $e->getMessage());
            }
            break;
            
        case 'reject':
            // Admin rejected booking
            try {
                sendBookingRejectionEmail(
                    $booking['customer_email'],
                    $booking['customer_name'],
                    $booking,
                    $reason
                );
                error_log("✓ Rejection email sent to: {$booking['customer_email']}");
            } catch (Exception $e) {
                error_log("✗ Failed to send rejection email: " . $e->getMessage());
            }
            break;
            
        default:
            error_log("Background email error: Unknown type - $type");
    }
    
} catch (Exception $e) {
    error_log("Background email fatal error: " . $e->getMessage());
}

exit();
?>