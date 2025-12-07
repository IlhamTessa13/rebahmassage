// Services Category JavaScript with Modal Notifications
let currentPage = 1;
let editingCategoryId = null;

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
    
    <div id="confirmationModal" class="notification-modal">
      <div class="notification-modal-content">
        <div class="notification-icon warning" id="confirmationIcon">
          <svg id="warningIcon" class="icon-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <circle cx="12" cy="17" r="0.5" fill="currentColor"></circle>
          </svg>
        </div>
        <h3 id="confirmationTitle" class="notification-title">Confirm Action</h3>
        <p id="confirmationMessage" class="notification-message">Are you sure?</p>
        <div class="confirmation-buttons">
          <button id="confirmationCancelBtn" class="confirmation-cancel-btn">Cancel</button>
          <button id="confirmationOkBtn" class="confirmation-ok-btn">Confirm</button>
        </div>
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

// Show confirmation dialog
function showConfirmation(title, message) {
  return new Promise((resolve) => {
    const modal = document.getElementById("confirmationModal");
    const titleEl = document.getElementById("confirmationTitle");
    const messageEl = document.getElementById("confirmationMessage");
    const okBtn = document.getElementById("confirmationOkBtn");
    const cancelBtn = document.getElementById("confirmationCancelBtn");

    titleEl.textContent = title;
    messageEl.textContent = message;

    const iconContainer = document.getElementById("confirmationIcon");
    const warningIcon = iconContainer.querySelector(".icon-warning");
    if (warningIcon) warningIcon.style.display = "block";

    const handleConfirm = () => {
      cleanup();
      resolve(true);
    };

    const handleCancel = () => {
      cleanup();
      resolve(false);
    };

    const cleanup = () => {
      modal.classList.remove("show");
      setTimeout(() => {
        modal.style.display = "none";
        okBtn.removeEventListener("click", handleConfirm);
        cancelBtn.removeEventListener("click", handleCancel);
      }, 300);
    };

    okBtn.addEventListener("click", handleConfirm);
    cancelBtn.addEventListener("click", handleCancel);

    modal.style.display = "flex";
    setTimeout(() => modal.classList.add("show"), 10);
  });
}

// ============================================
// DOCUMENT READY
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Services Category loaded");
  console.log(
    "Branch ID:",
    typeof ADMIN_BRANCH_ID !== "undefined" ? ADMIN_BRANCH_ID : "NOT DEFINED"
  );

  // Create notification modal
  createNotificationModal();

  // Load categories
  loadCategories();

  // Sidebar toggle functionality with mobile support
  setupSidebarToggle();

  // Image preview
  setupImagePreview();
});

// ============================================
// SETUP FUNCTIONS
// ============================================

// Setup sidebar toggle dengan support mobile overlay
function setupSidebarToggle() {
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar =
    document.getElementById("sidebar") || document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");
  const categoryModal = document.getElementById("categoryModal");

  if (!toggleBtn || !sidebar || !mainContent) {
    console.warn("Sidebar elements not found");
    return;
  }

  // Create overlay untuk mobile/tablet
  let overlay = document.querySelector(".sidebar-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    document.body.appendChild(overlay);
  }

  // Check jika mobile/tablet view
  function isMobileView() {
    return window.innerWidth <= 1024;
  }

  // Toggle sidebar function
  function toggleSidebar() {
    if (isMobileView()) {
      // Mobile/Tablet behavior: overlay sidebar
      const isOpen = sidebar.classList.contains("mobile-open");

      if (isOpen) {
        // Close sidebar
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
        mainContent.classList.remove("blurred");
        if (categoryModal) categoryModal.classList.remove("blurred");
      } else {
        // Open sidebar
        sidebar.classList.add("mobile-open");
        overlay.classList.add("active");
        mainContent.classList.add("blurred");
        if (categoryModal && categoryModal.style.display === "flex") {
          categoryModal.classList.add("blurred");
        }
      }
    } else {
      // Desktop behavior: collapse sidebar
      const isCollapsed = sidebar.classList.contains("collapsed");

      if (isCollapsed) {
        sidebar.classList.remove("collapsed");
        mainContent.classList.remove("expanded");
        localStorage.setItem("sidebarState", "open");
      } else {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("expanded");
        localStorage.setItem("sidebarState", "closed");
      }
    }
  }

  // Toggle button click
  toggleBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    toggleSidebar();
  });

  // Close sidebar ketika overlay diklik (mobile only)
  overlay.addEventListener("click", function () {
    if (isMobileView()) {
      sidebar.classList.remove("mobile-open");
      overlay.classList.remove("active");
      mainContent.classList.remove("blurred");
      if (categoryModal) categoryModal.classList.remove("blurred");
    }
  });

  // Handle window resize
  let resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      if (isMobileView()) {
        // Switch to mobile mode
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
        mainContent.classList.remove("blurred");
        if (categoryModal) categoryModal.classList.remove("blurred");
        mainContent.classList.remove("expanded");
      } else {
        // Switch to desktop mode
        overlay.classList.remove("active");
        mainContent.classList.remove("blurred");
        if (categoryModal) categoryModal.classList.remove("blurred");

        // Restore desktop sidebar state
        const sidebarState = localStorage.getItem("sidebarState");
        if (sidebarState === "closed") {
          sidebar.classList.add("collapsed");
          mainContent.classList.add("expanded");
        } else {
          sidebar.classList.remove("collapsed");
          mainContent.classList.remove("expanded");
        }
      }
    }, 250);
  });

  // Initialize state for desktop
  if (!isMobileView()) {
    const sidebarState = localStorage.getItem("sidebarState");
    if (sidebarState === "closed") {
      sidebar.classList.add("collapsed");
      mainContent.classList.add("expanded");
    }
  }
}

// Setup image preview
function setupImagePreview() {
  const imageInput = document.getElementById("categoryImage");
  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
          showNotification(
            "error",
            "Image size must be less than 2MB",
            "File Too Large"
          );
          e.target.value = "";
          return;
        }

        // Validate file type
        if (!file.type.match("image.*")) {
          showNotification(
            "error",
            "Please select an image file (PNG, JPG, JPEG)",
            "Invalid File Format"
          );
          e.target.value = "";
          return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
          document.getElementById("previewImg").src = e.target.result;
          document.getElementById("imagePreview").style.display = "block";
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

// ============================================
// LOAD & DISPLAY CATEGORIES
// ============================================

// Load categories
function loadCategories() {
  const entriesPerPage = document.getElementById("entriesPerPage").value;

  const params = new URLSearchParams({
    page: currentPage,
    limit: entriesPerPage,
    branch_id: ADMIN_BRANCH_ID,
  });

  console.log("Loading categories with params:", params.toString());

  fetch(`api/get_category.php?${params}`)
    .then((r) => {
      console.log("Response status:", r.status);
      if (!r.ok) {
        throw new Error(`HTTP error! status: ${r.status}`);
      }
      return r.json();
    })
    .then((data) => {
      console.log("Categories data:", data);
      if (data.success) {
        displayCategories(data.categories);
        updatePagination(data.pagination);
      } else {
        showNotification("error", data.message || "Failed to load categories");
        document.getElementById("categoryTableBody").innerHTML =
          '<tr><td colspan="4" style="text-align:center;">Error: ' +
          (data.message || "Failed to load") +
          "</td></tr>";
      }
    })
    .catch((err) => {
      console.error("Error loading categories:", err);
      showNotification("error", "Failed to load categories: " + err.message);
      document.getElementById("categoryTableBody").innerHTML =
        '<tr><td colspan="4" style="text-align:center;">Error loading data</td></tr>';
    });
}

// Display categories in table
function displayCategories(categories) {
  const tbody = document.getElementById("categoryTableBody");

  if (categories.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="4" style="text-align:center;">No categories found</td></tr>';
    return;
  }

  tbody.innerHTML = categories
    .map((category) => {
      const imagePath = category.image ? `public/${category.image}` : "";
      return `
        <tr>
            <td>
                ${
                  imagePath
                    ? `<img src="${imagePath}" alt="${category.name}" class="category-image" onerror="this.style.display='none'">`
                    : "<span>No Image</span>"
                }
            </td>
            <td>${category.name}</td>
            <td>${category.description}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-edit" onclick="editCategory(${
                      category.id
                    })" title="Edit">
                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit
                    </button>
                    <button class="btn btn-delete" onclick="deleteCategory(${
                      category.id
                    }, '${category.image}')" title="Delete">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        Delete
                    </button>
                </div>
            </td>
        </tr>
        `;
    })
    .join("");
}

// ============================================
// PAGINATION
// ============================================

// Update pagination
function updatePagination(pagination) {
  const info = document.getElementById("paginationInfo");
  info.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total} entries`;

  const controls = document.getElementById("paginationControls");
  let html = "";

  if (currentPage > 1) {
    html += `<button class="pagination-btn" onclick="changePage(${
      currentPage - 1
    })">←</button>`;
  }

  for (let i = 1; i <= pagination.pages; i++) {
    html += `<button class="pagination-btn ${
      i === currentPage ? "active" : ""
    }" onclick="changePage(${i})">${i}</button>`;
  }

  if (currentPage < pagination.pages) {
    html += `<button class="pagination-btn" onclick="changePage(${
      currentPage + 1
    })">→</button>`;
  }

  controls.innerHTML = html;
}

function changePage(page) {
  currentPage = page;
  loadCategories();
}

// ============================================
// MODAL FUNCTIONS
// ============================================

// Open add modal
function openAddModal() {
  editingCategoryId = null;
  document.getElementById("modalTitle").textContent = "Add Category";
  document.getElementById("categoryForm").reset();
  document.getElementById("categoryId").value = "";
  document.getElementById("oldImage").value = "";
  document.getElementById("imagePreview").style.display = "none";
  document.getElementById("categoryImage").required = true;
  document.getElementById("categoryModal").style.display = "flex";
}

// Edit category
function editCategory(id) {
  editingCategoryId = id;
  document.getElementById("modalTitle").textContent = "Edit Category";

  fetch(`api/get_category_detail.php?id=${id}`)
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        const category = data.category;
        document.getElementById("categoryId").value = category.id;
        document.getElementById("categoryName").value = category.name;
        document.getElementById("categoryDescription").value =
          category.description;
        document.getElementById("oldImage").value = category.image;

        // Show current image
        if (category.image) {
          document.getElementById(
            "previewImg"
          ).src = `public/${category.image}`;
          document.getElementById("imagePreview").style.display = "block";
        }

        // Image not required when editing
        document.getElementById("categoryImage").required = false;

        document.getElementById("categoryModal").style.display = "flex";
      } else {
        showNotification(
          "error",
          data.message || "Failed to load category details"
        );
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Failed to load category details");
    });
}

// Close modal
function closeModal() {
  document.getElementById("categoryModal").style.display = "none";
  document.getElementById("categoryForm").reset();
  document.getElementById("imagePreview").style.display = "none";
}

// ============================================
// SAVE & DELETE FUNCTIONS WITH MODAL
// ============================================

// Save category
function saveCategory() {
  const form = document.getElementById("categoryForm");
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // Validate inputs
  const name = document.getElementById("categoryName").value.trim();
  const description = document
    .getElementById("categoryDescription")
    .value.trim();

  if (!name || !description) {
    showNotification("warning", "All fields must be filled", "Incomplete Data");
    return;
  }

  const formData = new FormData(form);
  formData.append("branch_id", ADMIN_BRANCH_ID);

  const url = editingCategoryId
    ? "api/update_category.php"
    : "api/create_category.php";

  // Show loading
  const saveBtn = document.querySelector(".btn-save");
  const originalText = saveBtn.textContent;
  saveBtn.textContent = "Saving...";
  saveBtn.disabled = true;

  fetch(url, {
    method: "POST",
    body: formData,
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        showNotification(
          "success",
          editingCategoryId
            ? "Category updated successfully"
            : "Category created successfully"
        );
        closeModal();
        loadCategories();
      } else {
        // Handle specific error messages
        let errorMessage = data.message || "Failed to save category";

        if (
          errorMessage.toLowerCase().includes("image") &&
          errorMessage.toLowerCase().includes("size")
        ) {
          showNotification(
            "error",
            "Image size is too large. Maximum 2MB",
            "File Too Large"
          );
        } else if (
          errorMessage.toLowerCase().includes("image") &&
          errorMessage.toLowerCase().includes("type")
        ) {
          showNotification(
            "error",
            "Invalid image format. Use PNG, JPG, or JPEG",
            "Invalid File Format"
          );
        } else {
          showNotification("error", errorMessage);
        }
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Failed to save category: " + err.message);
    })
    .finally(() => {
      saveBtn.textContent = originalText;
      saveBtn.disabled = false;
    });
}

// Delete category with confirmation
function deleteCategory(id, imageName) {
  showConfirmation(
    "Delete Category",
    "Are you sure you want to delete this category? This action cannot be undone."
  ).then((confirmed) => {
    if (!confirmed) return;

    fetch("api/delete_category.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: id, image: imageName }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          showNotification("success", "Category deleted successfully");
          loadCategories();
        } else {
          showNotification(
            "error",
            data.message || "Failed to delete category"
          );
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Failed to delete category: " + err.message);
      });
  });
}

// Close modal when clicking outside (only for category modal, not notifications)
window.onclick = function (event) {
  const modal = document.getElementById("categoryModal");
  if (event.target === modal) {
    closeModal();
  }
};
