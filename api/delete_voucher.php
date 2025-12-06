<?php
// ==========================================
// FILE 2: api/delete_voucher.php
// ==========================================
?>
<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!current_user() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$voucher_id = intval($input['voucher_id'] ?? 0);

if ($voucher_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid voucher ID']);
    exit();
}

try {
    $pdo = db();
    
    // Check if voucher is used
    $check = $pdo->prepare("SELECT status FROM vouchers WHERE id = :id");
    $check->execute([':id' => $voucher_id]);
    $voucher = $check->fetch();
    
    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Voucher not found']);
        exit();
    }
    
    
    // Delete voucher
    $query = "DELETE FROM vouchers WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([':id' => $voucher_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Voucher deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete voucher']);
    }
    
} catch (Exception $e) {
    error_log("Error in delete_voucher.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>