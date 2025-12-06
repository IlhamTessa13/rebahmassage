<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../includes/auth.php';
require_once '../includes/db.php';

ob_clean();

if (!current_user() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $pdo = db();
    $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
    $period = isset($_GET['period']) ? $_GET['period'] : '7days';
    
    if (!$branch_id) {
        echo json_encode(['success' => false, 'message' => 'Branch ID required']);
        exit();
    }
    
    $labels = [];
    $data = [];
    
    switch ($period) {
        case '7days':
            // Last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d M', strtotime($date));
                
                $query = "
                    SELECT COUNT(*) as total FROM (
                        SELECT id FROM bookings 
                        WHERE branch_id = :branch_id 
                        AND booking_date = :date 
                        AND status IN ('approved', 'completed')
                        
                        UNION ALL
                        
                        SELECT id FROM offline_bookings 
                        WHERE branch_id = :branch_id2 
                        AND booking_date = :date2 
                        AND status IN ('approved', 'completed')
                    ) as combined
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':branch_id' => $branch_id,
                    ':date' => $date,
                    ':branch_id2' => $branch_id,
                    ':date2' => $date
                ]);
                $data[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
            break;
            
        case '1month':
            // Last 4 weeks
            for ($i = 3; $i >= 0; $i--) {
                $start = date('Y-m-d', strtotime("-" . (($i + 1) * 7) . " days"));
                $end = date('Y-m-d', strtotime("-" . ($i * 7) . " days"));
                $labels[] = 'Week ' . (4 - $i);
                
                $query = "
                    SELECT COUNT(*) as total FROM (
                        SELECT id FROM bookings 
                        WHERE branch_id = :branch_id 
                        AND booking_date BETWEEN :start AND :end
                        AND status IN ('approved', 'completed')
                        
                        UNION ALL
                        
                        SELECT id FROM offline_bookings 
                        WHERE branch_id = :branch_id2 
                        AND booking_date BETWEEN :start2 AND :end2
                        AND status IN ('approved', 'completed')
                    ) as combined
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':branch_id' => $branch_id,
                    ':start' => $start,
                    ':end' => $end,
                    ':branch_id2' => $branch_id,
                    ':start2' => $start,
                    ':end2' => $end
                ]);
                $data[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
            break;
            
        case '3months':
            // Last 3 months
            for ($i = 2; $i >= 0; $i--) {
                $date = date('Y-m-01', strtotime("-$i months"));
                $labels[] = date('F', strtotime($date));
                $start = date('Y-m-01', strtotime($date));
                $end = date('Y-m-t', strtotime($date));
                
                $query = "
                    SELECT COUNT(*) as total FROM (
                        SELECT id FROM bookings 
                        WHERE branch_id = :branch_id 
                        AND booking_date BETWEEN :start AND :end
                        AND status IN ('approved', 'completed')
                        
                        UNION ALL
                        
                        SELECT id FROM offline_bookings 
                        WHERE branch_id = :branch_id2 
                        AND booking_date BETWEEN :start2 AND :end2
                        AND status IN ('approved', 'completed')
                    ) as combined
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':branch_id' => $branch_id,
                    ':start' => $start,
                    ':end' => $end,
                    ':branch_id2' => $branch_id,
                    ':start2' => $start,
                    ':end2' => $end
                ]);
                $data[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
            break;
            
        case '1year':
            // Last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m-01', strtotime("-$i months"));
                $labels[] = date('M', strtotime($date));
                $start = date('Y-m-01', strtotime($date));
                $end = date('Y-m-t', strtotime($date));
                
                $query = "
                    SELECT COUNT(*) as total FROM (
                        SELECT id FROM bookings 
                        WHERE branch_id = :branch_id 
                        AND booking_date BETWEEN :start AND :end
                        AND status IN ('approved', 'completed')
                        
                        UNION ALL
                        
                        SELECT id FROM offline_bookings 
                        WHERE branch_id = :branch_id2 
                        AND booking_date BETWEEN :start2 AND :end2
                        AND status IN ('approved', 'completed')
                    ) as combined
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':branch_id' => $branch_id,
                    ':start' => $start,
                    ':end' => $end,
                    ':branch_id2' => $branch_id,
                    ':start2' => $start,
                    ':end2' => $end
                ]);
                $data[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
            break;
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'data' => $data,
        'total' => array_sum($data)
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