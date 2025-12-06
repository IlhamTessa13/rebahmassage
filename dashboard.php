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
    <title>Dashboard - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <h2>Dashboard - <?php echo htmlspecialchars($branch_name); ?></h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div class="dashboard-header">
                <div class="date-info">
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card reservasi">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3>Reservasi</h3>
                        <p class="stat-number" id="totalReservasi">0</p>
                        <span class="stat-label">Hari Ini</span>
                    </div>
                </div>

                <div class="stat-card customer">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3>Customer</h3>
                        <p class="stat-number" id="totalCustomer">0</p>
                        <span class="stat-label">Hari Ini</span>
                    </div>
                </div>

                <div class="stat-card therapist">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3>Therapist</h3>
                        <p class="stat-number" id="totalTherapist">0</p>
                        <span class="stat-label">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="dashboard-section chart-section">
                <div class="chart-header">
                    <div class="chart-title-area">
                        <h2>Statistik Reservasi</h2>
                        <div class="chart-current-value">
                            <span class="value-number" id="currentValue">0</span>
                            <span class="value-label">Total Reservasi</span>
                        </div>
                    </div>
                    <div class="chart-period-buttons">
                        <button class="period-btn active" data-period="7days">7 Hari</button>
                        <button class="period-btn" data-period="1month">1 Bulan</button>
                        <button class="period-btn" data-period="3months">3 Bulan</button>
                        <button class="period-btn" data-period="1year">1 Tahun</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="reservationChart"></canvas>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="schedule-customer.php" class="view-all">View All →</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Therapist</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentBookingsBody">
                            <tr>
                                <td colspan="7" style="text-align:center;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Therapist Status -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Therapist Today</h2>
                    <a href="therapist-data.php" class="view-all">View All →</a>
                </div>
                <div class="therapist-grid" id="therapistGrid">
                    <p style="text-align:center;">Loading...</p>
                </div>
            </div>

        </div>   
    </div>
    
    <script>
        const ADMIN_BRANCH_ID = <?php echo $admin_branch_id; ?>;
    </script>
    <script src="js/dashboard.js"></script>
</body>
</html>