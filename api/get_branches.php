<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Include auth
$authPath = '../includes/auth.php';
if (file_exists($authPath)) {
    require_once $authPath;
    $user = current_user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
        exit();
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Auth file not found at: ' . $authPath]);
    exit();
}

// Include database
$dbPath = '../includes/db.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database file not found at: ' . $dbPath]);
    exit();
}

// Get database connection
try {
    $pdo = db();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

try {
    // Get all branches
    $stmt = $pdo->prepare("SELECT id, name, address, image FROM branches ORDER BY name ASC");
    $stmt->execute();
    
    $branches = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $branches[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'address' => $row['address'] ?? '',
            'image' => $row['image'] ? 'public/' . $row['image'] : 'public/branch-default.jpg'
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'branches' => $branches,
        'count' => count($branches)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Query error: ' . $e->getMessage()
    ]);
}
?>