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
    <title>Blog Management - Rebah Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/blog-management.css">
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
        <h2>Blog Management</h2>
    </header>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div id="alertContainer"></div>
            
            <h1>Blog Management</h1>
            <p class="subtitle">Manage your blog posts. Edit content, images, and descriptions for each article.</p>
            
            <!-- Blog Cards Container -->
            <div class="blog-cards-container" id="blogCardsContainer">
                <div class="loading-message">Loading blogs...</div>
            </div>
        </div>
    </div>

    <!-- Edit Blog Modal -->
    <div id="editBlogModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="modalTitle">Edit Blog Post</h2>
                <button class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editBlogForm" enctype="multipart/form-data">
                    <input type="hidden" id="blogId" name="blog_id">
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="blogTitle">Blog Title *</label>
                            <input type="text" id="blogTitle" name="title" required maxlength="255">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="blogClickbait">Clickbait/Excerpt *</label>
                            <textarea id="blogClickbait" name="clickbait" rows="3" required maxlength="500"></textarea>
                            <span class="char-count" id="clickbaitCount">0/500</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="blogDescription">Description *</label>
                            <textarea id="blogDescription" name="description" rows="10" required></textarea>
                            <span class="char-count" id="descriptionCount">0 characters</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="blogImage">Blog Image</label>
                            <input type="file" id="blogImage" name="image" accept="image/*">
                            <small class="form-text">Leave empty to keep current image. Accepted: JPG, PNG (max 5MB)</small>
                        </div>
                        <div class="form-group">
                            <label>Current Image</label>
                            <div class="current-image-preview">
                                <img id="currentImagePreview" src="" alt="Current blog image">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>New Image Preview</label>
                            <div class="image-preview" id="newImagePreview" style="display: none;">
                                <img id="previewImage" src="" alt="Preview">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn-save" onclick="saveBlog()">Save Changes</button>
            </div>
        </div>
    </div>

    <script src="js/blog-management.js"></script>
</body>
</html>