// Blog Management JavaScript with Modal Notifications & Image Cache Fix
let editingBlogId = null;

// ============================================
// NOTIFICATION MODAL FUNCTIONS
// ============================================

// Create notification modal HTML structure
function createNotificationModal() {
  if (document.getElementById("notificationModal")) return;

  const modalHTML = `
    <div id="notificationModal" class="notification-modal">
      <div class="notification-modal-content">
        <div class="notification-icon" id="notificationIcon">
          <svg id="successIcon" class="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <svg id="errorIcon" class="icon-error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          <svg id="warningIcon" class="icon-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
        <h3 id="notificationTitle" class="notification-title">Success</h3>
        <p id="notificationMessage" class="notification-message">Operation completed successfully</p>
        <button id="notificationOkBtn" class="notification-ok-btn">OK</button>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
  document
    .getElementById("notificationOkBtn")
    .addEventListener("click", closeNotificationModal);
}

// Show notification modal
function showNotification(type, message, title = null) {
  const modal = document.getElementById("notificationModal");
  const iconContainer = document.getElementById("notificationIcon");
  const titleElement = document.getElementById("notificationTitle");
  const messageElement = document.getElementById("notificationMessage");
  const successIcon = document.getElementById("successIcon");
  const errorIcon = document.getElementById("errorIcon");
  const warningIcon = document.getElementById("warningIcon");

  if (!title) {
    title =
      type === "success"
        ? "Success!"
        : type === "warning"
        ? "Warning!"
        : "Error!";
  }

  titleElement.textContent = title;
  messageElement.textContent = message;

  successIcon.style.display = "none";
  errorIcon.style.display = "none";
  warningIcon.style.display = "none";

  if (type === "success") {
    iconContainer.className = "notification-icon success";
    successIcon.style.display = "block";
  } else if (type === "warning") {
    iconContainer.className = "notification-icon warning";
    warningIcon.style.display = "block";
  } else {
    iconContainer.className = "notification-icon error";
    errorIcon.style.display = "block";
  }

  modal.style.display = "flex";
  setTimeout(() => modal.classList.add("show"), 10);
}

// Close notification modal
function closeNotificationModal() {
  const modal = document.getElementById("notificationModal");
  modal.classList.remove("show");
  setTimeout(() => {
    modal.style.display = "none";
  }, 300);
}

// ============================================
// DOCUMENT READY
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Blog Management loaded");

  // Create notification modal
  createNotificationModal();

  // Load blogs
  loadBlogs();

  // Setup sidebar toggle with localStorage
  setupSidebarToggle();

  // Setup image preview
  setupImagePreview();

  // Setup character counters
  setupCharCounters();
});

// ============================================
// SETUP FUNCTIONS
// ============================================

// Setup sidebar toggle (kompatibel dengan dashboard.js)
function setupSidebarToggle() {
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar =
    document.getElementById("sidebar") || document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (!toggleBtn || !sidebar || !mainContent) {
    console.warn("Sidebar elements not found");
    return;
  }

  // Check localStorage for sidebar state
  const sidebarState = localStorage.getItem("sidebarState");
  if (sidebarState === "closed") {
    sidebar.classList.add("collapsed");
    sidebar.classList.add("closed");
    mainContent.classList.add("expanded");
  }

  toggleBtn.addEventListener("click", function () {
    sidebar.classList.toggle("collapsed");
    sidebar.classList.toggle("closed");
    mainContent.classList.toggle("expanded");

    // Save state to localStorage
    if (
      sidebar.classList.contains("collapsed") ||
      sidebar.classList.contains("closed")
    ) {
      localStorage.setItem("sidebarState", "closed");
    } else {
      localStorage.setItem("sidebarState", "open");
    }
  });
}

// Setup image preview
function setupImagePreview() {
  const imageInput = document.getElementById("blogImage");
  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          showNotification(
            "error",
            "Image size must be less than 5MB",
            "File Too Large"
          );
          e.target.value = "";
          return;
        }

        // Validate file type
        if (!file.type.match("image.*")) {
          showNotification(
            "error",
            "Please select an image file (JPG, PNG, GIF)",
            "Invalid File Format"
          );
          e.target.value = "";
          return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
          document.getElementById("previewImage").src = e.target.result;
          document.getElementById("newImagePreview").style.display = "block";
        };
        reader.readAsDataURL(file);
      } else {
        document.getElementById("newImagePreview").style.display = "none";
      }
    });
  }
}

// Setup character counters
function setupCharCounters() {
  const clickbaitInput = document.getElementById("blogClickbait");
  const descriptionInput = document.getElementById("blogDescription");

  if (clickbaitInput) {
    clickbaitInput.addEventListener("input", function () {
      const count = this.value.length;
      document.getElementById("clickbaitCount").textContent = `${count}/500`;
    });
  }

  if (descriptionInput) {
    descriptionInput.addEventListener("input", function () {
      const count = this.value.length;
      document.getElementById(
        "descriptionCount"
      ).textContent = `${count} characters`;
    });
  }
}

// ============================================
// LOAD & DISPLAY BLOGS
// ============================================

// Load all blogs
function loadBlogs() {
  fetch("api/get_blogs.php")
    .then((r) => {
      if (!r.ok) {
        throw new Error(`HTTP error! status: ${r.status}`);
      }
      return r.json();
    })
    .then((data) => {
      console.log("Blogs data:", data);
      if (data.success) {
        displayBlogs(data.blogs);
      } else {
        showNotification("error", data.message || "Failed to load blogs");
        document.getElementById("blogCardsContainer").innerHTML =
          '<div class="error-message">Error loading blogs</div>';
      }
    })
    .catch((err) => {
      console.error("Error loading blogs:", err);
      showNotification("error", "Failed to load blogs: " + err.message);
      document.getElementById("blogCardsContainer").innerHTML =
        '<div class="error-message">Error loading blogs</div>';
    });
}

// Display blogs as cards with cache busting
function displayBlogs(blogs) {
  const container = document.getElementById("blogCardsContainer");

  if (blogs.length === 0) {
    container.innerHTML = '<div class="no-blogs">No blogs found</div>';
    return;
  }

  // Add timestamp for cache busting to force reload new images
  const timestamp = new Date().getTime();

  container.innerHTML = blogs
    .map(
      (blog) => `
        <div class="blog-card" data-blog-id="${blog.id}">
            <div class="blog-card-image">
                <img src="/php/public/${blog.image}?v=${timestamp}" 
                     alt="${escapeHtml(blog.title)}"
                     onerror="this.src='/php/public/default-blog.jpg'">
            </div>
            <div class="blog-card-content">
                <h3 class="blog-card-title">${escapeHtml(blog.title)}</h3>
                <p class="blog-card-clickbait">${escapeHtml(blog.clickbait)}</p>
                <div class="blog-card-meta">
                    <span class="blog-id">Blog #${blog.id}</span>
                    <span class="blog-updated">Updated: ${formatDate(
                      blog.created_at
                    )}</span>
                </div>
                <button class="btn-edit" onclick="openEditModal(${blog.id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Edit Blog
                </button>
            </div>
        </div>
    `
    )
    .join("");
}

// ============================================
// EDIT MODAL FUNCTIONS
// ============================================

// Open edit modal
function openEditModal(blogId) {
  editingBlogId = blogId;

  // Fetch blog details
  fetch(`api/get_blog_detail.php?id=${blogId}`)
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        const blog = data.blog;

        // Fill form
        document.getElementById("blogId").value = blog.id;
        document.getElementById("blogTitle").value = blog.title;
        document.getElementById("blogClickbait").value = blog.clickbait;
        document.getElementById("blogDescription").value = blog.description;

        // Update character counts
        document.getElementById(
          "clickbaitCount"
        ).textContent = `${blog.clickbait.length}/500`;
        document.getElementById(
          "descriptionCount"
        ).textContent = `${blog.description.length} characters`;

        // Show current image with cache busting
        const currentImagePreview = document.getElementById(
          "currentImagePreview"
        );
        const currentImageContainer = currentImagePreview.parentElement;
        const timestamp = new Date().getTime();

        if (blog.image && blog.image !== "") {
          currentImagePreview.src = `/php/public/${blog.image}?v=${timestamp}`;
          currentImagePreview.style.display = "block";
          currentImagePreview.onerror = function () {
            this.style.display = "none";
            currentImageContainer.innerHTML =
              '<div class="no-image-placeholder">No image available</div>';
          };
        } else {
          currentImagePreview.style.display = "none";
          currentImageContainer.innerHTML =
            '<div class="no-image-placeholder">No image available</div>';
        }

        // Clear file input and preview
        document.getElementById("blogImage").value = "";
        document.getElementById("newImagePreview").style.display = "none";

        // Open modal
        document.getElementById("editBlogModal").style.display = "block";
        document.body.style.overflow = "hidden"; // Prevent background scroll
      } else {
        showNotification(
          "error",
          data.message || "Failed to load blog details"
        );
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Failed to load blog details: " + err.message);
    });
}

// Close edit modal
function closeEditModal() {
  document.getElementById("editBlogModal").style.display = "none";
  document.getElementById("editBlogForm").reset();
  document.getElementById("newImagePreview").style.display = "none";
  document.body.style.overflow = "auto"; // Restore scroll
  editingBlogId = null;
}

// ============================================
// SAVE BLOG WITH NOTIFICATIONS
// ============================================

// Save blog
function saveBlog() {
  const form = document.getElementById("editBlogForm");

  // Validate form
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // Check if fields are not empty
  const title = document.getElementById("blogTitle").value.trim();
  const clickbait = document.getElementById("blogClickbait").value.trim();
  const description = document.getElementById("blogDescription").value.trim();

  if (!title || !clickbait || !description) {
    showNotification("warning", "All fields must be filled", "Incomplete Data");
    return;
  }

  // Validate clickbait length
  if (clickbait.length > 500) {
    showNotification(
      "warning",
      "Clickbait maximum 500 characters",
      "Text Too Long"
    );
    return;
  }

  // Create FormData
  const formData = new FormData(form);

  // Show loading
  const saveBtn = document.querySelector(".btn-save");
  const originalText = saveBtn.textContent;
  saveBtn.textContent = "Saving...";
  saveBtn.disabled = true;

  fetch("api/update_blog.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        showNotification("success", "Blog successfully updated!");
        closeEditModal();

        // Reload blogs to show updated content with new cache-busted images
        setTimeout(() => {
          loadBlogs();
        }, 300);
      } else {
        // Handle specific error messages
        let errorMessage = data.message || "Failed to update blog";

        if (errorMessage.includes("Invalid image type")) {
          showNotification(
            "error",
            "Invalid image format. Only JPG, PNG, and GIF are allowed.",
            "Invalid File Format"
          );
        } else if (errorMessage.includes("size must be less than")) {
          showNotification(
            "error",
            "Image size must be less than 5MB",
            "File Too Large"
          );
        } else if (errorMessage.includes("Missing required fields")) {
          showNotification(
            "warning",
            "All fields are required",
            "Incomplete Data"
          );
        } else {
          showNotification("error", errorMessage);
        }
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification(
        "error",
        "An error occurred while saving: " + err.message
      );
    })
    .finally(() => {
      saveBtn.textContent = originalText;
      saveBtn.disabled = false;
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  const options = { year: "numeric", month: "short", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

// Close modal when clicking outside (only for edit modal, not notification)
window.onclick = function (event) {
  const editModal = document.getElementById("editBlogModal");
  if (event.target === editModal) {
    closeEditModal();
  }
};
