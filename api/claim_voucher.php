<?php
// ==========================================
// FILE 3: api/claim_voucher.php
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
$code = strtoupper(trim($input['code'] ?? ''));
$branch_id = intval($input['branch_id'] ?? 0);

if (empty($code) || $branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit();
}

try {
    $pdo = db();
    
    // Get voucher
    $query = "SELECT * FROM vouchers WHERE code = :code AND branch_id = :branch_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':code' => $code,
        ':branch_id' => $branch_id
    ]);
    
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Voucher not found or not available for this branch']);
        exit();
    }
    
    // Check status
    if ($voucher['status'] === 'used') {
        $used_date = date('d M Y H:i', strtotime($voucher['used_at']));
        echo json_encode(['success' => false, 'message' => "Voucher has already been redeemed on {$used_date}"]);
        exit();
    }
    
    if ($voucher['status'] === 'expired') {
        echo json_encode(['success' => false, 'message' => 'Voucher has expired']);
        exit();
    }
    
    // Check if expired
    if (strtotime($voucher['expired_at']) < strtotime('today')) {
        // Update to expired
        $update_expired = "UPDATE vouchers SET status = 'expired' WHERE id = :id";
        $pdo->prepare($update_expired)->execute([':id' => $voucher['id']]);
        
        echo json_encode(['success' => false, 'message' => 'Voucher has expired']);
        exit();
    }
    
    // Redeem voucher - admin claims it for customer
    $update = "UPDATE vouchers 
               SET status = 'used', used_at = NOW() 
               WHERE id = :id AND status = 'available'";
    
    $update_stmt = $pdo->prepare($update);
    $result = $update_stmt->execute([':id' => $voucher['id']]);
    
    if ($result && $update_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Voucher redeemed successfully',
            'voucher' => [
                'code' => $voucher['code'],
                'name' => $voucher['name'],
                'discount' => $voucher['discount'],
                'discount_type' => $voucher['discount_type']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to redeem voucher. It may have been used already.']);
    }
    
} catch (Exception $e) {
    error_log("Error in claim_voucher.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>