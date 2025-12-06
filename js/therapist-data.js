// Therapist Data JavaScript with Modal Notifications
let currentPage = 1;
let editingTherapistId = null;
let deletingTherapistId = null;

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

    // Ensure icon is visible
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

// Biar kompatibel dengan kode lama
function showAlert(type, message) {
  showNotification(type, message);
}

// ============================================
// LOAD INITIAL DATA
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Therapist Data loaded");
  console.log(
    "Branch ID:",
    typeof ADMIN_BRANCH_ID !== "undefined" ? ADMIN_BRANCH_ID : "NOT DEFINED"
  );

  // Setup sidebar toggle
  setupSidebarToggle();

  // Create notification modal
  createNotificationModal();

  // Load therapists data
  loadTherapists();
});

// Setup sidebar toggle
function setupSidebarToggle() {
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (!toggleBtn || !sidebar || !mainContent) {
    console.warn("Sidebar elements not found");
    return;
  }

  const sidebarState = localStorage.getItem("sidebarState");
  if (sidebarState === "closed") {
    sidebar.classList.add("collapsed");
    mainContent.classList.add("expanded");
  }

  toggleBtn.addEventListener("click", function () {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");

    if (sidebar.classList.contains("collapsed")) {
      localStorage.setItem("sidebarState", "closed");
    } else {
      localStorage.setItem("sidebarState", "open");
    }
  });
}

// Load therapists
function loadTherapists() {
  const entriesPerPage = document.getElementById("entriesPerPage").value;

  const params = new URLSearchParams({
    page: currentPage,
    limit: entriesPerPage,
    branch_id: ADMIN_BRANCH_ID,
  });

  console.log("Loading therapists with params:", params.toString());

  fetch(`api/get_therapists(2).php?${params}`)
    .then((r) => {
      console.log("Response status:", r.status);
      if (!r.ok) {
        throw new Error(`HTTP error! status: ${r.status}`);
      }
      return r.json();
    })
    .then((data) => {
      console.log("Therapists data:", data);
      if (data.success) {
        displayTherapists(data.therapists);
        updatePagination(data.pagination);
      } else {
        showNotification("error", data.message || "Failed to load therapists");
        document.getElementById("therapistTableBody").innerHTML =
          '<tr><td colspan="5" style="text-align:center;">Error: ' +
          (data.message || "Failed to load") +
          "</td></tr>";
      }
    })
    .catch((err) => {
      console.error("Error loading therapists:", err);
      showNotification("error", "Failed to load therapists: " + err.message);
      document.getElementById("therapistTableBody").innerHTML =
        '<tr><td colspan="5" style="text-align:center;">Error loading data</td></tr>';
    });
}

// Display therapists in table
function displayTherapists(therapists) {
  const tbody = document.getElementById("therapistTableBody");

  if (therapists.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="5" style="text-align:center;">No therapists found</td></tr>';
    return;
  }

  tbody.innerHTML = therapists
    .map((therapist) => {
      const isActive = parseInt(therapist.is_active) === 1;

      console.log(
        `Therapist ${therapist.name}: is_active = ${therapist.is_active}, isActive = ${isActive}`
      );

      return `
        <tr>
            <td>${therapist.name}</td>
            <td>${therapist.no || "-"}</td>
            <td>${capitalizeFirst(therapist.gender)}</td>
            <td>
                <span class="status-badge ${
                  isActive ? "status-active" : "status-inactive"
                }">
                    ${isActive ? "Active" : "Inactive"}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn ${
                      isActive ? "btn-deactivate" : "btn-activate"
                    }" 
                            onclick="toggleStatus(${therapist.id}, ${
        isActive ? 1 : 0
      })"
                            title="${isActive ? "Deactivate" : "Activate"}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                        ${isActive ? "Deactivate" : "Activate"}
                    </button>
                    <button class="action-btn btn-delete" 
                            onclick="openDeleteModal(${therapist.id})"
                            title="Delete">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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
// ACTION FUNCTIONS WITH MODAL
// ============================================

// Toggle therapist status
function toggleStatus(therapistId, currentStatus) {
  const currentStatusInt = parseInt(currentStatus);
  const newStatus = currentStatusInt === 1 ? 0 : 1;
  const action = newStatus === 1 ? "activate" : "deactivate";

  console.log(
    `Toggle status: therapistId=${therapistId}, currentStatus=${currentStatusInt}, newStatus=${newStatus}, action=${action}`
  );

  showConfirmation(
    `${capitalizeFirst(action)} Therapist`,
    `Are you sure you want to ${action} this therapist?`
  ).then((confirmed) => {
    if (!confirmed) return;

    fetch("api/toggle_therapist_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        therapist_id: therapistId,
        is_active: newStatus,
      }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          showNotification("success", `Therapist ${action}d successfully`);
          loadTherapists();
        } else {
          showNotification(
            "error",
            data.message || `Failed to ${action} therapist`
          );
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", `Failed to ${action} therapist`);
      });
  });
}

// Open delete modal (gunakan confirmation modal baru)
function openDeleteModal(therapistId) {
  deletingTherapistId = therapistId;

  showConfirmation(
    "Delete Therapist",
    "Are you sure you want to delete this therapist? This action cannot be undone."
  ).then((confirmed) => {
    if (confirmed) {
      confirmDelete();
    } else {
      deletingTherapistId = null;
    }
  });
}

// Close delete modal (tidak perlu lagi, sudah pakai confirmation modal)
function closeDeleteModal() {
  deletingTherapistId = null;
  // Modal lama sudah tidak dipakai
}

// Confirm delete
function confirmDelete() {
  if (!deletingTherapistId) return;

  fetch("api/delete_therapist.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      therapist_id: deletingTherapistId,
    }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        showNotification("success", "Therapist deleted successfully");
        deletingTherapistId = null;
        loadTherapists();
      } else {
        showNotification("error", data.message || "Failed to delete therapist");
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Failed to delete therapist");
    });
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
  loadTherapists();
}

// Open add modal
function openAddModal() {
  editingTherapistId = null;
  document.getElementById("modalTitle").textContent = "Add Therapist";
  document.getElementById("therapistForm").reset();
  document.getElementById("therapistId").value = "";
  document.getElementById("therapistModal").style.display = "block";
}

// Close modal
function closeModal() {
  document.getElementById("therapistModal").style.display = "none";
  document.getElementById("therapistForm").reset();
}

// Save therapist
function saveTherapist() {
  const form = document.getElementById("therapistForm");
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  // Validate WhatsApp number format
  if (!data.no.startsWith("62")) {
    showNotification(
      "error",
      "WhatsApp number must start with 62 (Indonesia country code)"
    );
    return;
  }

  const url = editingTherapistId
    ? "api/update_therapist.php"
    : "api/create_therapist.php";

  fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        showNotification(
          "success",
          editingTherapistId
            ? "Therapist updated successfully"
            : "Therapist created successfully"
        );
        closeModal();
        loadTherapists();
      } else {
        showNotification("error", data.message);
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Failed to save therapist");
    });
}

// Capitalize first letter
function capitalizeFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
}

// Close modal when clicking outside (hanya untuk modal form, bukan notification)
window.onclick = function (event) {
  const therapistModal = document.getElementById("therapistModal");

  if (event.target === therapistModal) {
    closeModal();
  }
};

// Download Excel
function downloadTherapistExcel() {
  const params = new URLSearchParams({
    branch_id: ADMIN_BRANCH_ID,
    export: "excel",
  });

  console.log("Downloading therapist Excel with params:", params.toString());
  window.open(`api/export_therapists_excel.php?${params}`, "_blank");
}
