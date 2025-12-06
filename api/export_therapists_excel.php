<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check admin
if (!is_admin()) {
    die('Unauthorized');
}

$admin = current_user();
$branch_id = $admin['branch_id'];

// Validate branch_id
if (!$branch_id) {
    die('Error: Branch ID not found');
}

try {
    $pdo = db();
    
    // Get all therapists for the branch
    $query = "SELECT 
                name,
                no as whatsapp_number,
                gender,
                CASE 
                    WHEN is_active = 1 THEN 'Active'
                    ELSE 'Inactive'
                END as status,
                created_at
              FROM therapists 
              WHERE branch_id = :branch_id
              ORDER BY name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':branch_id' => $branch_id]);
    
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate Excel (CSV format)
    $filename = 'therapists_export_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output UTF-8 BOM for Excel to recognize UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'Therapist Name',
        'WhatsApp Number',
        'Gender',
        'Status',
        'Created Date'
    ]);
    
    // Data rows
    foreach ($therapists as $therapist) {
        fputcsv($output, [
            $therapist['name'],
            $therapist['whatsapp_number'],
            ucfirst($therapist['gender']),
            $therapist['status'],
            date('d M Y', strtotime($therapist['created_at']))
        ]);
    }
    
    fclose($output);
    exit();
    
} catch (PDOException $e) {
    error_log("Error in export_therapists_excel: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>