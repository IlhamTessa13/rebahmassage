<?php
ob_start();
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
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
    $branch_id = isset($_GET['branch_id']) && !empty($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
    
    if (!$branch_id) {
        echo json_encode(['success' => false, 'message' => 'Branch ID is required']);
        exit();
    }
    
    // Count total categories for specific branch
    $count_query = "SELECT COUNT(*) as total FROM service_categories WHERE branch_id = :branch_id";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute([':branch_id' => $branch_id]);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get categories with pagination
    $query = "SELECT id, name, description, image, branch_id, created_at
              FROM service_categories 
              WHERE branch_id = :branch_id
              ORDER BY created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':branch_id', $branch_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = $total > 0 ? ceil($total / $limit) : 0;
    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total);
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'pagination' => [
            'total' => (int)$total,
            'pages' => (int)$total_pages,
            'current' => (int)$page,
            'from' => (int)$from,
            'to' => (int)$to,
            'limit' => (int)$limit
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