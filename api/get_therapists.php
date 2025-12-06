<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

// Cek apakah user sudah login
$user = current_user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../includes/db.php';

// Ambil input JSON
$input = json_decode(file_get_contents('php://input'), true);

$branch_id = isset($input['branch_id']) ? intval($input['branch_id']) : 0;
$date = isset($input['date']) ? $input['date'] : '';
$start_time = isset($input['start_time']) ? $input['start_time'] : '';
$duration = isset($input['duration']) ? intval($input['duration']) : 0;

// Ambil data user dari session
$user_gender = $user['gender'];

// Validasi input
if ($branch_id <= 0 || empty($date) || empty($start_time) || $duration <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    $pdo = db();
    
    // Hitung end time
    $start_datetime = new DateTime($date . ' ' . $start_time);
    $end_datetime = clone $start_datetime;
    $end_datetime->add(new DateInterval('PT' . $duration . 'M'));
    $end_time = $end_datetime->format('H:i:s');
    
    // Ambil therapist dengan gender sama & aktif
    // FIX: Tambahkan field gender ke SELECT
    $query = "SELECT id, name, gender FROM therapists 
              WHERE branch_id = :branch_id 
              AND gender = :gender 
              AND is_active = 1";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':gender' => $user_gender
    ]);
    
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $available_therapists = [];
    
    // ============================================
    // CHECK THERAPIST AVAILABILITY (Online + Offline)
    // ============================================
    
    // Check online bookings
    $check_online = "SELECT COUNT(*) as count FROM bookings 
                    WHERE therapist_id = :therapist_id 
                    AND booking_date = :booking_date 
                    AND status IN ('approved', 'pending')
                    AND (start_time < :end_time AND end_time > :start_time)";
    
    // Check offline bookings
    $check_offline = "SELECT COUNT(*) as count FROM offline_bookings 
                     WHERE therapist_id = :therapist_id 
                     AND booking_date = :booking_date 
                     AND status IN ('approved', 'pending')
                     AND (start_time < :end_time AND end_time > :start_time)";
    
    $stmt_online = $pdo->prepare($check_online);
    $stmt_offline = $pdo->prepare($check_offline);
    
    foreach ($therapists as $row) {
        // Check online
        $stmt_online->execute([
            ':therapist_id' => $row['id'],
            ':booking_date' => $date,
            ':end_time' => $end_time,
            ':start_time' => $start_time
        ]);
        $online_count = $stmt_online->fetch(PDO::FETCH_ASSOC);
        
        // Check offline
        $stmt_offline->execute([
            ':therapist_id' => $row['id'],
            ':booking_date' => $date,
            ':end_time' => $end_time,
            ':start_time' => $start_time
        ]);
        $offline_count = $stmt_offline->fetch(PDO::FETCH_ASSOC);
        
        // Therapist available if not booked in both online & offline
        if ($online_count['count'] == 0 && $offline_count['count'] == 0) {
            $available_therapists[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'gender' => $row['gender'] // FIX: Kirim gender field
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'therapists' => $available_therapists
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>