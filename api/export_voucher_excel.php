<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check admin
if (!is_admin()) {
    die('Unauthorized');
}

$admin = current_user();
$branch_id = $admin['branch_id'];

if (!$branch_id) {
    die('Error: Branch ID not found');
}

try {
    $pdo = db();
    
    $query = "SELECT 
                code,
                name,
                discount,
                discount_type,
                status,
                expired_at,
                used_at,
                created_at
              FROM vouchers 
              WHERE branch_id = :branch_id
              ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':branch_id' => $branch_id]);
    
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'vouchers_export_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, [
        'Code',
        'Name',
        'Discount',
        'Discount Type',
        'Status',
        'Expired Date',
        'Used Date',
        'Created Date'
    ]);
    
    foreach ($vouchers as $voucher) {
        $discount = '-';
        if ($voucher['discount_type']) {
            if ($voucher['discount_type'] === 'percentage') {
                $discount = $voucher['discount'] . '%';
            } elseif ($voucher['discount_type'] === 'fixed' || $voucher['discount_type'] === 'cashback') {
                $discount = 'Rp ' . number_format($voucher['discount'], 0, ',', '.');
            } elseif ($voucher['discount_type'] === 'free_service') {
                $discount = 'Free Service';
            }
        }
        
        fputcsv($output, [
            $voucher['code'],
            $voucher['name'],
            $discount,
            ucfirst(str_replace('_', ' ', $voucher['discount_type'] ?: '-')),
            ucfirst($voucher['status']),
            $voucher['expired_at'] ? date('d M Y', strtotime($voucher['expired_at'])) : '-',
            $voucher['used_at'] ? date('d M Y H:i', strtotime($voucher['used_at'])) : '-',
            date('d M Y', strtotime($voucher['created_at']))
        ]);
    }
    
    fclose($output);
    exit();
    
} catch (PDOException $e) {
    error_log("Error in export_voucher_excel: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>