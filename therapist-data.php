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
    <title>Therapist Data - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/therapist-data.css">
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
        <h2>Therapist Data - <?php echo htmlspecialchars($branch_name); ?></h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div id="alertContainer"></div>
            
            <h1>Therapist Data</h1>
            
            <!-- Add New Therapist Button -->
            <button class="btn btn-add" onclick="openAddModal()">
                <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14m-7-7h14"/>
                </svg>
                Add Therapist
            </button>
            
            <!-- Therapist Table -->
            <div class="therapist-table-container">
                <div class="table-header">
                    <h3>All Therapists</h3>
                    <div class="entries-per-page">
                        <span>Entries per page:</span>
                        <select id="entriesPerPage" onchange="loadTherapists()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="therapist-table">
                        <thead>
                            <tr>
                                <th>Therapist Name</th>
                                <th>WhatsApp Number</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="therapistTableBody">
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

    <!-- Add Therapist Modal -->
    <div id="therapistModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Therapist</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="therapistForm">
                    <input type="hidden" id="therapistId" name="therapist_id">
                    
                    <div class="form-group">
                        <label for="therapistName">Therapist Name *</label>
                        <input type="text" id="therapistName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="branch">Branch *</label>
                        <select id="branch" name="branch_id" required>
                            <option value="">Select Branch</option>
                            <option value="2">Fatmawati</option>
                            <option value="1">Menteng</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="whatsappNumber">WhatsApp Number *</label>
                        <input type="tel" id="whatsappNumber" name="no" placeholder="628xxxxxxxxxx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-save" onclick="saveTherapist()">Save</button>
            </div>
        </div>
    </div>


    <script src="js/therapist-data.js"></script>
    <script>
        const ADMIN_BRANCH_ID = <?php echo $admin_branch_id; ?>;
    </script>
</body>
</html>