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
    // PERBAIKAN: Ambil dari tabel users, bukan admins
    $stmt = $pdo->prepare("SELECT branch_id FROM users WHERE id = :id AND role = 'admin'");
    $stmt->execute([':id' => $admin['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['branch_id'])) {
        $admin_branch_id = $result['branch_id'];
        // Update session
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
    <title>Schedule Customer - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/schedule-customer.css">
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
        <h2>Schedule Customer - <?php echo htmlspecialchars($branch_name); ?></h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div id="alertContainer"></div>
            
            <h1>Schedule Customer</h1>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filterDate">Date</label>
                        <input type="date" id="filterDate" name="filterDate">
                    </div>
                    <div class="filter-group">
                        <label for="filterStatus">Status</label>
                        <select id="filterStatus" name="filterStatus">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterRoom">Room</label>
                        <select id="filterRoom" name="filterRoom">
                            <option value="">All Rooms</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button class="btn btn-filter" onclick="loadSchedules()">Apply Filters</button>
                    </div>
                </div>
            </div>
            
            <!-- Add New Schedule Button -->
            <button class="btn btn-add" onclick="openAddModal()">
                <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14m-7-7h14"/>
                </svg>
                Add Offline Booking
            </button>
            
            <!-- Schedule Table -->
            <div class="schedule-table-container">
                <div class="table-header">
                    <h3>Customer Schedules</h3>
                    <div class="entries-per-page">
                        <span>Entries per page:</span>
                        <select id="entriesPerPage" onchange="loadSchedules()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Room</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Therapist</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <tr>
                                <td colspan="10" style="text-align:center;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                    <div class="pagination-controls" id="paginationControls"></div>
                    <button class="btn-download-excel" onclick="downloadExcel()" title="Download Excel">
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

    <!-- Add/Edit Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Offline Booking</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
           <div class="modal-body">
                <form id="bookingForm">
                    <input type="hidden" id="bookingId" name="booking_id">
                    <input type="hidden" id="bookingType" name="booking_type" value="offline">
                    
                    <div class="form-group" id="customerNameGroup">
                        <label for="customerName">Customer Name *</label>
                        <input type="text" id="customerName" name="customer_name" required>
                    </div>
                    
                    <div class="form-group" id="categoryGroup">
                        <label for="category">Category *</label>
                        <select id="category" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="form-row" id="durationRow">
                        <div class="form-group">
                            <label for="duration">Duration *</label>
                            <select id="duration" name="duration" required>
                                <option value="">Select Duration</option>
                                <option value="60">60 minutes</option>
                                <option value="90">90 minutes</option>
                                <option value="120">120 minutes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="room">Room *</label>
                            <select id="room" name="room_id" required>
                                <option value="">Select Room</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row" id="dateRow">
                        <div class="form-group">
                            <label for="bookingDate">Date *</label>
                            <input type="date" id="bookingDate" name="booking_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="startTime">Start Time *</label>
                            <select id="startTime" name="start_time" required>
                                <option value="">Select Time</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group" id="therapistGroup">
                        <label for="therapist">Therapist *</label>
                        <select id="therapist" name="therapist_id" required>
                            <option value="">Select Therapist</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-save" onclick="saveBooking()">Save</button>
            </div>
        </div>
    </div>

    <script src="js/schedule-customer.js"></script>
    <script>
        const ADMIN_BRANCH_ID = <?php echo $admin_branch_id; ?>;
    </script>
</body>
</html>