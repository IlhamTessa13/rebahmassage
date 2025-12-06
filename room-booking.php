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
    <title>Room Management - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/room-booking.css">
</head>
<body>
    <header class="top-header">
        <button class="toggle-btn" id="toggleBtn" title="Toggle Sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <a href="dashboard.php" class="logo-link">
            <img src="/php/public/logorebah2.png" class="logo-header" alt="Rebah Logo">
        </a>
        <h2>Room Booking - <?php echo htmlspecialchars($branch_name); ?></h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div id="alertContainer"></div>
            
            <h1>Room Management</h1>
            
            <!-- Add Room Button -->
            <button class="btn btn-add" onclick="openAddModal()">
                <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14m-7-7h14"/>
                </svg>
                Add New Room
            </button>
            
            <!-- Rooms Table -->
            <div class="room-table-container">
                <div class="table-header">
                    <h3>All Rooms</h3>
                    <div class="entries-per-page">
                        <span>Entries per page:</span>
                        <select id="entriesPerPage" onchange="loadRooms()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="room-table">
                        <thead>
                            <tr>
                                <th>Room Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="roomTableBody">
                            <tr>
                                <td colspan="3" style="text-align:center;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                    <div class="pagination-controls" id="paginationControls"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Room Modal -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Room</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="roomForm">
                    <input type="hidden" id="roomId" name="room_id">
                    
                    <div class="form-group">
                        <label for="roomName">Room Name *</label>
                        <input type="text" id="roomName" name="room_name" required placeholder="e.g., Room A">
                    </div>
                    
                    <div class="form-group">
                        <label>Branch</label>
                        <input type="text" value="<?php echo htmlspecialchars($branch_name); ?>" disabled class="disabled-input">
                        <input type="hidden" name="branch_id" value="<?php echo $admin_branch_id; ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-save" onclick="saveRoom()">Save</button>
            </div>
        </div>
    </div>

    <!-- Pass PHP variables to JavaScript -->
    <script>
        const ADMIN_BRANCH_ID = <?php echo $admin_branch_id; ?>;
        const BRANCH_NAME = "<?php echo htmlspecialchars($branch_name); ?>";
    </script>
    
    <!-- External JavaScript -->
    <script src="js/room-booking.js"></script>
</body>
</html>