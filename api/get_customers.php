<?php
header('Content-Type: application/json');
session_start();

require_once '../includes/auth.php';
require_once '../includes/db.php';

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
    
    // Count total customers
    $count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
    $count_stmt = $pdo->query($count_query);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get customers with pagination
    $query = "SELECT id, full_name, email, phone, gender, created_at 
              FROM users 
              WHERE role = 'customer' 
              ORDER BY created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = ceil($total / $limit);
    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total);
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
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