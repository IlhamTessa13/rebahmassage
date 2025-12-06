<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/auth.php';
require_once '../includes/db.php';

ob_clean();

// Check if admin is logged in
if (!current_user() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $pdo = db();
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get branch_id from request
    $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
    
    // Build query based on branch filter
    if ($branch_id) {
        // Count total therapists for specific branch
        $count_query = "SELECT COUNT(*) as total FROM therapists WHERE branch_id = :branch_id";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute([':branch_id' => $branch_id]);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get therapists with pagination for specific branch
        // FIXED: Added is_active field
        $query = "SELECT id, name, no, gender, branch_id, is_active 
                  FROM therapists 
                  WHERE branch_id = :branch_id
                  ORDER BY name ASC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':branch_id', $branch_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    } else {
        // Count total therapists (all branches)
        $count_query = "SELECT COUNT(*) as total FROM therapists";
        $count_stmt = $pdo->query($count_query);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get all therapists with pagination
        // FIXED: Added is_active field
        $query = "SELECT id, name, no, gender, branch_id, is_active 
                  FROM therapists 
                  ORDER BY name ASC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = ceil($total / $limit);
    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total);
    
    echo json_encode([
        'success' => true,
        'therapists' => $therapists,
        'pagination' => [
            'total' => $total,
            'pages' => $total_pages,
            'current' => $page,
            'from' => $from,
            'to' => $to,
            'limit' => $limit
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>