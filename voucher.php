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
    <title>Voucher Management - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/voucher.css">
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- HTML5 QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
        <h2>Voucher - <?php echo htmlspecialchars($branch_name); ?></h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div id="alertContainer"></div>
            
            <h1>Voucher Management</h1>
            
            <!-- Action Buttons -->
            <div class="action-buttons-row">
                <button class="btn btn-add" onclick="openAddModal()">
                    <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14m-7-7h14"/>
                    </svg>
                    Add New Voucher
                </button>
                <button class="btn btn-claim" onclick="openClaimModal()">
                    <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                    Claim Voucher
                </button>
            </div>
            
            <!-- Vouchers Table -->
            <div class="voucher-table-container">
                <div class="table-header">
                    <h3>All Vouchers</h3>
                    <div class="entries-per-page">
                        <span>Entries per page:</span>
                        <select id="entriesPerPage" onchange="loadVouchers()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="voucher-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th>Expired Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="voucherTableBody">
                            <tr>
                                <td colspan="6" style="text-align:center;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                    <div class="pagination-controls" id="paginationControls"></div>
                    <button class="btn-download-excel" onclick="downloadVoucherExcel()" title="Download Excel">
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

    <!-- Add Voucher Modal - WITH BULK GENERATION -->
    <div id="voucherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Voucher</h2>
                <button class="close" onclick="closeModal('voucherModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="voucherForm">
                    <!-- NEW: Quantity Field -->
                    <div class="form-group">
                        <label for="voucherQuantity">
                            Quantity *
                            <span class="label-help">(1 for single voucher, 2+ for bulk generation)</span>
                        </label>
                        <input type="number" id="voucherQuantity" name="quantity" min="1" max="100" value="1" required onchange="toggleBulkFields()">
                        <small>Maximum 100 vouchers per batch</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="voucherCode">Voucher Code *</label>
                        <input type="text" id="voucherCode" name="code" required placeholder="e.g., REBAH2024-001" maxlength="50">
                        <small>Must be unique</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="voucherName">Voucher Name *</label>
                        <input type="text" id="voucherName" name="name" required placeholder="e.g., Diskon 20% Massage" maxlength="100">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="discountType">Discount Type</label>
                            <select id="discountType" name="discount_type" onchange="toggleDiscountField()">
                                <option value="">No Discount</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (Rp)</option>
                                <option value="cashback">Cashback (Rp)</option>
                                <option value="free_service">Free Service</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="discountValueGroup" style="display:none;">
                            <label for="discountValue">Discount Value</label>
                            <input type="number" id="discountValue" name="discount" min="0" step="0.01" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiredDate">Expired Date *</label>
                        <input type="date" id="expiredDate" name="expired_at" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Branch</label>
                        <input type="text" value="<?php echo htmlspecialchars($branch_name); ?>" disabled class="disabled-input">
                        <input type="hidden" name="branch_id" value="<?php echo $admin_branch_id; ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('voucherModal')">Cancel</button>
                <button type="button" class="btn-save" onclick="saveVoucher()">
                    <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Save & Generate
                </button>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Voucher QR Code</h2>
                <button class="close" onclick="closeModal('qrModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <div id="qrCodeContainer" style="display: inline-block; padding: 20px;"></div>
                <p id="qrCodeText" style="font-weight: bold; margin-top: 10px;"></p>
                <p id="qrVoucherName" style="color: #666;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-save" onclick="downloadQRCode()">
                    <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    Download QR Code
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal('qrModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Claim Voucher Modal -->
    <div id="claimModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Claim Voucher</h2>
                <button class="close" onclick="closeClaimModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Method Selection -->
                <div id="claimMethodSelection">
                    <p style="text-align: center; margin-bottom: 20px; color: #666;">Choose claim method:</p>
                    <div class="claim-method-buttons">
                        <button class="btn-method" onclick="selectClaimMethod('manual')">
                            <svg style="width: 24px; height: 24px; margin-bottom: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Manual Input</span>
                        </button>
                        <button class="btn-method" onclick="selectClaimMethod('scan')">
                            <svg style="width: 24px; height: 24px; margin-bottom: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                            <span>Scan QR Code</span>
                        </button>
                    </div>
                </div>

                <!-- Manual Input -->
                <div id="claimManualInput" style="display:none;">
                    <form id="claimForm">
                        <div class="form-group">
                            <label for="claimCode">Voucher Code *</label>
                            <input type="text" id="claimCode" name="code" required placeholder="Enter voucher code">
                        </div>
                        <button type="button" class="btn-save" onclick="claimVoucher()" style="width: 100%;">Claim Voucher</button>
                    </form>
                    <button type="button" class="btn-cancel" onclick="backToMethodSelection()" style="width: 100%; margin-top: 10px;">Back</button>
                </div>

                <!-- QR Scanner -->
                <div id="claimScanInput" style="display:none;">
                    <div id="qrReader" style="width: 100%;"></div>
                    <button type="button" class="btn-cancel" onclick="stopScanner()" style="width: 100%; margin-top: 10px;">Stop Scanner</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass PHP variables to JavaScript -->
    <script>
        const ADMIN_BRANCH_ID = <?php echo $admin_branch_id; ?>;
        const BRANCH_NAME = "<?php echo htmlspecialchars($branch_name); ?>";
    </script>
    
    <!-- External JavaScript -->
    <script src="js/voucher.js"></script>
</body>
</html>