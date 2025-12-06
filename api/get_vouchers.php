<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!current_user()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user = current_user();
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

// Get parameters
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

if ($branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit();
}

try {
    $pdo = db();
    
    // Update expired vouchers automatically
    $update_expired = "UPDATE vouchers 
                       SET status = 'expired' 
                       WHERE status = 'available' 
                       AND expired_at < CURDATE() 
                       AND branch_id = :branch_id";
    $pdo->prepare($update_expired)->execute([':branch_id' => $branch_id]);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM vouchers WHERE branch_id = :branch_id";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute([':branch_id' => $branch_id]);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get vouchers with pagination
    $query = "SELECT id, code, name, discount, discount_type, status, expired_at, 
                     used_at, created_at 
              FROM vouchers 
              WHERE branch_id = :branch_id 
              ORDER BY created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':branch_id', $branch_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $vouchers = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vouchers[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'discount' => $row['discount'] ? (float)$row['discount'] : null,
            'discount_type' => $row['discount_type'],
            'status' => $row['status'],
            'expired_at' => $row['expired_at'],
            'used_at' => $row['used_at'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Calculate pagination info
    $total_pages = ceil($total / $limit);
    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total);
    
    echo json_encode([
        'success' => true,
        'vouchers' => $vouchers,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => (int)$total_pages,
            'from' => $from,
            'to' => $to
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_vouchers.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading vouchers: ' . $e->getMessage()
    ]);
}
?>