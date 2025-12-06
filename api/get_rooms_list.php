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
    
    // Get total count of rooms for this branch
    $count_query = "SELECT COUNT(*) as total FROM rooms WHERE branch_id = :branch_id";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute([':branch_id' => $branch_id]);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get rooms with pagination
    $query = "SELECT id, name, branch_id, is_active, created_at 
              FROM rooms 
              WHERE branch_id = :branch_id 
              ORDER BY name ASC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':branch_id', $branch_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $rooms = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rooms[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'branch_id' => (int)$row['branch_id'],
            'is_active' => (int)$row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Calculate pagination info
    $total_pages = ceil($total / $limit);
    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total);
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
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
    error_log("Error in get_rooms_list.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading rooms: ' . $e->getMessage()
    ]);
}
?>