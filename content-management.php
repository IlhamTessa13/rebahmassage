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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Services Management - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/content-management.css">
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
        <h2>Content Management</h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div id="alertContainer"></div>
            
            <div class="page-header">
                <div>
                    <h1>Menu Services Management</h1>
                    <p class="subtitle">Manage your service menu. Add, edit, or delete services displayed on the landing page.</p>
                </div>
                <button class="btn-add-new" onclick="openAddModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add New Service
                </button>
            </div>
            
            <!-- Services Cards Container -->
            <div class="services-cards-container" id="servicesCardsContainer">
                <div class="loading-message">Loading services...</div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content modal-medium">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Service</h2>
                <button class="close" onclick="closeServiceModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="serviceForm" enctype="multipart/form-data">
                    <input type="hidden" id="serviceId" name="service_id">
                    <input type="hidden" id="formMode" value="add">
                    
                    <div class="form-group full-width">
                        <label for="serviceName">Service Name *</label>
                        <input type="text" id="serviceName" name="name" required maxlength="100" placeholder="e.g., Sport Massage, Deep Tissue">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="serviceImage">Service Image *</label>
                        <input type="file" id="serviceImage" name="image" accept="image/*" required>
                        <small class="form-text">Recommended: Square image (1:1 ratio), JPG/PNG (max 5MB)</small>
                    </div>
                    
                    <div class="form-group full-width" id="currentImageGroup" style="display: none;">
                        <label>Current Image</label>
                        <div class="current-image-preview">
                            <img id="currentImagePreview" src="" alt="Current service image">
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Image Preview</label>
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img id="previewImage" src="" alt="Preview">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeServiceModal()">Cancel</button>
                <button type="button" class="btn-save" onclick="saveService()">Save Service</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header modal-header-danger">
                <h2>Confirm Delete</h2>
                <button class="close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <p>Are you sure you want to delete this service?</p>
                    <p class="service-name-delete" id="deleteServiceName"></p>
                    <p class="warning-text">This action cannot be undone!</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-delete" onclick="confirmDelete()">Delete Service</button>
            </div>
        </div>
    </div>

    <script src="js/content-management.js"></script>
</body>
</html>