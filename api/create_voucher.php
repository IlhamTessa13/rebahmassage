<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!current_user() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$base_code = trim($input['code'] ?? '');
$name = trim($input['name'] ?? '');
$discount_type = $input['discount_type'] ?? null;
$discount = $input['discount'] ?? null;
$expired_at = $input['expired_at'] ?? '';
$branch_id = intval($input['branch_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 1); // NEW: Bulk quantity

// Validation
if (empty($base_code) || empty($name) || empty($expired_at) || $branch_id <= 0 || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit();
}

// Limit bulk quantity
if ($quantity > 100) {
    echo json_encode(['success' => false, 'message' => 'Maximum 100 vouchers per batch']);
    exit();
}

// Check if expired_at is in the future
if (strtotime($expired_at) <= strtotime('today')) {
    echo json_encode(['success' => false, 'message' => 'Expired date must be in the future']);
    exit();
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    
    $created_vouchers = [];
    $created_count = 0;
    $failed_codes = [];
    
    for ($i = 1; $i <= $quantity; $i++) {
        // Generate code
        if ($quantity > 1) {
            $code = $base_code . '_' . $i;
        } else {
            $code = $base_code;
        }
        
        // Check if code already exists
        $check = $pdo->prepare("SELECT id FROM vouchers WHERE code = :code");
        $check->execute([':code' => $code]);
        
        if ($check->fetch()) {
            $failed_codes[] = $code;
            continue; // Skip this code
        }
        
        // Insert voucher
        $query = "INSERT INTO vouchers (code, name, discount, discount_type, branch_id, status, expired_at) 
                  VALUES (:code, :name, :discount, :discount_type, :branch_id, 'available', :expired_at)";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            ':code' => $code,
            ':name' => $name,
            ':discount' => $discount,
            ':discount_type' => $discount_type,
            ':branch_id' => $branch_id,
            ':expired_at' => $expired_at
        ]);
        
        if ($result) {
            $voucher_id = $pdo->lastInsertId();
            
            // For single voucher, return full data
            if ($quantity === 1) {
                $get = $pdo->prepare("SELECT * FROM vouchers WHERE id = :id");
                $get->execute([':id' => $voucher_id]);
                $voucher = $get->fetch(PDO::FETCH_ASSOC);
                
                $created_vouchers[] = [
                    'id' => (int)$voucher['id'],
                    'code' => $voucher['code'],
                    'name' => $voucher['name'],
                    'discount' => $voucher['discount'] ? (float)$voucher['discount'] : null,
                    'discount_type' => $voucher['discount_type'],
                    'status' => $voucher['status'],
                    'expired_at' => $voucher['expired_at']
                ];
            }
            
            $created_count++;
        }
    }
    
    if ($created_count > 0) {
        $pdo->commit();
        
        $response = [
            'success' => true,
            'created_count' => $created_count,
            'message' => $created_count . ' voucher(s) created successfully'
        ];
        
        // For single voucher, include full voucher data
        if ($quantity === 1 && count($created_vouchers) > 0) {
            $response['voucher'] = $created_vouchers[0];
        }
        
        // Include failed codes if any
        if (count($failed_codes) > 0) {
            $response['failed_codes'] = $failed_codes;
            $response['message'] .= ' (' . count($failed_codes) . ' duplicate code(s) skipped)';
        }
        
        echo json_encode($response);
    } else {
        $pdo->rollBack();
        
        $error_msg = 'Failed to create vouchers';
        if (count($failed_codes) > 0) {
            $error_msg = 'All voucher codes already exist: ' . implode(', ', $failed_codes);
        }
        
        echo json_encode([
            'success' => false,
            'message' => $error_msg
        ]);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in create_voucher.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>