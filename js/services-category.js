// Services Category JavaScript with Modal Notifications
let currentPage = 1;
let editingCategoryId = null;

document.addEventListener("DOMContentLoaded", function () {
  console.log("Services Category loaded");
  console.log(
    "Branch ID:",
    typeof ADMIN_BRANCH_ID !== "undefined" ? ADMIN_BRANCH_ID : "NOT DEFINED"
  );

  // Load categories
  loadCategories();

  // Sidebar toggle functionality
  setupSidebarToggle();

  // Image preview
  setupImagePreview();

  // Create notification modal structure
  createNotificationModal();
});

// Create notification modal HTML structure
function createNotificationModal() {
  // Check if modal already exists
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
        </div>
        <h3 id="notificationTitle" class="notification-title">Success</h3>
        <p id="notificationMessage" class="notification-message">Operation completed successfully</p>
        <button id="notificationOkBtn" class="notification-ok-btn">OK</button>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Add event listener for OK button
  document
    .getElementById("notificationOkBtn")
    .addEventListener("click", function () {
      closeNotificationModal();
    });
}

// Show notification modal
function showNotification(type, message, title = null) {
  const modal = document.getElementById("notificationModal");
  const iconContainer = document.getElementById("notificationIcon");
  const titleElement = document.getElementById("notificationTitle");
  const messageElement = document.getElementById("notificationMessage");
  const successIcon = document.getElementById("successIcon");
  const errorIcon = document.getElementById("errorIcon");

  // Set default titles
  if (!title) {
    title = type === "success" ? "Success!" : "Error!";
  }

  // Update content
  titleElement.textContent = title;
  messageElement.textContent = message;

  // Show appropriate icon
  if (type === "success") {
    iconContainer.className = "notification-icon success";
    successIcon.style.display = "block";
    errorIcon.style.display = "none";
  } else {
    iconContainer.className = "notification-icon error";
    successIcon.style.display = "none";
    errorIcon.style.display = "block";
  }

  // Show modal with animation
  modal.style.display = "flex";
  setTimeout(() => {
    modal.classList.add("show");
  }, 10);
}

// Close notification modal
function closeNotificationModal() {
  const modal = document.getElementById("notificationModal");
  modal.classList.remove("show");
  setTimeout(() => {
    modal.style.display = "none";
  }, 300);
}

// Setup sidebar toggle
function setupSidebarToggle() {
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (!toggleBtn || !sidebar || !mainContent) {
    console.warn("Sidebar elements not found");
    return;
  }

  // Check localStorage for sidebar state
  const sidebarState = localStorage.getItem("sidebarState");
  if (sidebarState === "closed") {
    sidebar.classList.add("collapsed");
    mainContent.classList.add("expanded");
  }

  toggleBtn.addEventListener("click", function () {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");

    // Save state to localStorage
    if (sidebar.classList.contains("collapsed")) {
      localStorage.setItem("sidebarState", "closed");
    } else {
      localStorage.setItem("sidebarState", "open");
    }
  });
}

// Setup image preview
function setupImagePreview() {
  const imageInput = document.getElementById("categoryImage");
  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
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
            <td class="action-buttons">
                <button class="btn btn-edit" onclick="editCategory(${
                  category.id
                })" title="Edit">
                    <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Edit
                </button>
                <button class="btn btn-delete" onclick="deleteCategory(${category.id}, '${
                        category.image
                      }')" title="Delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                    Delete
                </button>
            </td>
        </tr>
        `;
    })
    .join("");
}

// Update pagination
function updatePagination(pagination) {
  const info = document.getElementById("paginationInfo");
  info.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total} entries`;

  const controls = document.getElementById("paginationControls");
  let html = "";

  for (let i = 1; i <= pagination.pages; i++) {
    html += `<button class="pagination-btn ${
      i === currentPage ? "active" : ""
    }" onclick="changePage(${i})">${i}</button>`;
  }

  controls.innerHTML = html;
}

function changePage(page) {
  currentPage = page;
  loadCategories();
}

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
        showNotification("error", "Failed to load category details");
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Error loading category details");
    });
}

// Delete category
function deleteCategory(id, imageName) {
  if (!confirm("Are you sure you want to delete this category?")) return;

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
        showNotification("error", data.message);
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Failed to delete category");
    });
}

// Save category
function saveCategory() {
  const form = document.getElementById("categoryForm");
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const formData = new FormData(form);
  formData.append("branch_id", ADMIN_BRANCH_ID);

  const url = editingCategoryId
    ? "api/update_category.php"
    : "api/create_category.php";

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
        showNotification("error", data.message);
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Failed to save category");
    });
}

// Close modal
function closeModal() {
  document.getElementById("categoryModal").style.display = "none";
  document.getElementById("categoryForm").reset();
  document.getElementById("imagePreview").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("categoryModal");
  if (event.target === modal) {
    closeModal();
  }
};
