<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check admin
if (!is_admin()) {
    die('Unauthorized');
}

try {
    $pdo = db();
    
    // Get all customers (users with role 'customer')
    $query = "SELECT 
                full_name,
                email,
                phone,
                gender
              FROM users 
              WHERE role = 'customer'
              ORDER BY full_name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate Excel (CSV format)
    $filename = 'customers_export_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output UTF-8 BOM for Excel to recognize UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'Customer Name',
        'Email',
        'Phone Number',
        'Gender'
    ]);
    
    // Data rows
    foreach ($customers as $customer) {
        fputcsv($output, [
            $customer['full_name'],
            $customer['email'],
            $customer['phone'] ?: '-',
            $customer['gender'] ? ucfirst($customer['gender']) : '-'
        ]);
    }
    
    fclose($output);
    exit();
    
} catch (PDOException $e) {
    error_log("Error in export_customers_excel: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>