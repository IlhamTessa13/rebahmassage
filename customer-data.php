<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Check if admin is logged in
require_login();
if (!is_admin()) {
    header('Location: ../login.php');
    exit();
}

// Get admin data
$admin = current_user();

// Get branch_id from database if not in session
if (!isset($admin['branch_id'])) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT branch_id FROM users WHERE id = :id AND role = 'admin'");
    $stmt->execute([':id' => $admin['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['branch_id'])) {
        $admin_branch_id = $result['branch_id'];
        $_SESSION['user']['branch_id'] = $admin_branch_id;
    } else {
        die('Error: Admin branch not found. Please contact administrator.');
    }
} else {
    $admin_branch_id = $admin['branch_id'];
}

// Get branch name
$pdo = db();
$branch_query = "SELECT name FROM branches WHERE id = :branch_id";
$branch_stmt = $pdo->prepare($branch_query);
$branch_stmt->execute([':branch_id' => $admin_branch_id]);
$branch_data = $branch_stmt->fetch(PDO::FETCH_ASSOC);

if (!$branch_data) {
    die('Error: Branch not found.');
}

$branch_name = $branch_data['name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Data - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/customer-data.css">
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <button class="toggle-btn" id="toggleBtn" title="Toggle Sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <a href="dashboard.php" class="logo-link">
            <img src="/php/public/logorebah.png" class="logo-header" alt="Rebah Logo">
        </a>
        <h2>Customer Data - <?php echo htmlspecialchars($branch_name); ?></h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div id="alertContainer"></div>
            
            <h1>Customer Data</h1>
            
            <!-- Customer Table -->
            <div class="customer-table-container">
                <div class="table-header">
                    <h3>All Customers</h3>
                    <div class="entries-per-page">
                        <span>Entries per page:</span>
                        <select id="entriesPerPage" onchange="loadCustomers()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="customer-table">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Gender</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            <tr>
                                <td colspan="5" style="text-align:center;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                    <div class="pagination-controls" id="paginationControls"></div>
                    <button class="btn-download-excel" onclick="downloadTherapistExcel()" title="Download Excel">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Download Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass PHP variable to JavaScript -->
    <script>
        const ADMIN_BRANCH_ID = <?php echo $admin_branch_id; ?>;
    </script>
    
    <!-- External JavaScript -->
    <script src="js/customer-data.js"></script>
</body>
</html>