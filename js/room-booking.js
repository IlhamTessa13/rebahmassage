// Room Booking JavaScript with Modal Notifications
let currentPage = 1;
let editingRoomId = null;

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

// Kompatibilitas dengan kode lama
function showAlert(type, message) {
  showNotification(type, message);
}

// ============================================
// DOCUMENT READY
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Room Booking page loaded");

  // Setup sidebar toggle dengan mobile support
  setupSidebarToggle();

  // Create notification modal
  createNotificationModal();

  // Load rooms on page load
  loadRooms();
});

// Setup sidebar toggle dengan support mobile overlay
function setupSidebarToggle() {
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar =
    document.getElementById("sidebar") || document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");
  const roomModal = document.getElementById("roomModal");

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
        if (roomModal) roomModal.classList.remove("blurred");
      } else {
        // Open sidebar
        sidebar.classList.add("mobile-open");
        overlay.classList.add("active");
        mainContent.classList.add("blurred");
        if (roomModal && roomModal.style.display === "block") {
          roomModal.classList.add("blurred");
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
      if (roomModal) roomModal.classList.remove("blurred");
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
        if (roomModal) roomModal.classList.remove("blurred");
        mainContent.classList.remove("expanded");
      } else {
        // Switch to desktop mode
        overlay.classList.remove("active");
        mainContent.classList.remove("blurred");
        if (roomModal) roomModal.classList.remove("blurred");

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

// ============================================
// LOAD ROOMS
// ============================================

function loadRooms() {
  const entriesPerPage = document.getElementById("entriesPerPage").value;
  console.log("=== LOADING ROOMS ===");
  console.log("Current page:", currentPage);
  console.log("Entries per page:", entriesPerPage);
  console.log("Branch ID:", ADMIN_BRANCH_ID);

  const params = new URLSearchParams({
    page: currentPage,
    limit: entriesPerPage,
    branch_id: ADMIN_BRANCH_ID,
  });

  fetch(`api/get_rooms_list.php?${params}`)
    .then((r) => {
      console.log("Response status:", r.status);
      if (!r.ok) {
        throw new Error(`HTTP error! status: ${r.status}`);
      }
      return r.json();
    })
    .then((data) => {
      console.log("Rooms data:", data);

      if (data.success) {
        console.log("Number of rooms:", data.rooms.length);
        displayRooms(data.rooms);
        updatePagination(data.pagination);
      } else {
        console.error("API returned error:", data.message);
        showNotification("error", data.message || "Failed to load rooms");
        document.getElementById("roomTableBody").innerHTML =
          '<tr><td colspan="3" style="text-align:center;">Error: ' +
          (data.message || "Failed to load") +
          "</td></tr>";
      }
    })
    .catch((err) => {
      console.error("Fetch Error:", err);
      showNotification("error", "Failed to load rooms: " + err.message);
      document.getElementById("roomTableBody").innerHTML =
        '<tr><td colspan="3" style="text-align:center; color: red;">Error loading data</td></tr>';
    });
}

// Display rooms in table
function displayRooms(rooms) {
  console.log("=== DISPLAYING ROOMS ===");
  console.log("Rooms to display:", rooms);

  const tbody = document.getElementById("roomTableBody");

  if (!tbody) {
    console.error("ERROR: roomTableBody element not found!");
    return;
  }

  if (rooms.length === 0) {
    console.log("No rooms found");
    tbody.innerHTML =
      '<tr><td colspan="3" style="text-align:center;">No rooms found</td></tr>';
    return;
  }

  const html = rooms
    .map((room) => {
      const isActive = parseInt(room.is_active) === 1;
      const statusClass = isActive ? "status-active" : "status-inactive";
      const statusText = isActive ? "Active" : "Inactive";
      const buttonClass = isActive ? "btn-deactivate" : "btn-activate";
      const buttonText = isActive ? "Deactivate" : "Activate";

      return `
        <tr>
            <td>${room.name}</td>
            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn ${buttonClass}" 
                            onclick="toggleRoomStatus(${room.id}, ${room.is_active})" 
                            title="${buttonText}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                        ${buttonText}
                    </button>
                    <button class="action-btn btn-delete" 
                            onclick="deleteRoom(${room.id})" 
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

  console.log("Generated HTML length:", html.length);
  tbody.innerHTML = html;
  console.log("✓ Table updated successfully with", rooms.length, "rooms");
}

// ============================================
// ACTION FUNCTIONS WITH MODAL
// ============================================

// Open add modal
function openAddModal() {
  console.log("Opening add room modal");
  editingRoomId = null;
  document.getElementById("modalTitle").textContent = "Add New Room";
  document.getElementById("roomForm").reset();
  document.getElementById("roomId").value = "";
  document.getElementById("roomModal").style.display = "flex";
}

// Close modal
function closeModal() {
  document.getElementById("roomModal").style.display = "none";
  document.getElementById("roomForm").reset();
  editingRoomId = null;
}

// Save room
function saveRoom() {
  const form = document.getElementById("roomForm");

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const formData = new FormData(form);
  const data = {
    room_id: formData.get("room_id"),
    room_name: formData.get("room_name"),
    branch_id: ADMIN_BRANCH_ID,
  };

  console.log("Saving room:", data);

  const url = editingRoomId ? "api/update_room.php" : "api/create_room.php";

  fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((r) => r.json())
    .then((data) => {
      console.log("Save response:", data);
      if (data.success) {
        showNotification(
          "success",
          editingRoomId
            ? "Room updated successfully"
            : "Room created successfully"
        );
        closeModal();
        loadRooms();
      } else {
        showNotification("error", data.message || "Failed to save room");
      }
    })
    .catch((err) => {
      console.error("Error saving room:", err);
      showNotification("error", "Error: " + err.message);
    });
}

// Toggle room status (activate/deactivate)
function toggleRoomStatus(roomId, currentStatus) {
  const newStatus = currentStatus === 1 ? 0 : 1;
  const action = newStatus === 1 ? "activate" : "deactivate";

  showConfirmation(
    `${action.charAt(0).toUpperCase() + action.slice(1)} Room`,
    `Are you sure you want to ${action} this room?`
  ).then((confirmed) => {
    if (!confirmed) return;

    console.log(
      `Toggling room ${roomId} from ${currentStatus} to ${newStatus}`
    );

    fetch("api/toggle_room_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        room_id: roomId,
        is_active: newStatus,
      }),
    })
      .then((r) => r.json())
      .then((data) => {
        console.log("Toggle response:", data);
        if (data.success) {
          showNotification("success", `Room ${action}d successfully`);
          loadRooms();
        } else {
          showNotification("error", data.message || `Failed to ${action} room`);
        }
      })
      .catch((err) => {
        console.error("Error toggling room status:", err);
        showNotification("error", "Error: " + err.message);
      });
  });
}

// Delete room
function deleteRoom(roomId) {
  showConfirmation(
    "Delete Room",
    "Are you sure you want to delete this room? This action cannot be undone."
  ).then((confirmed) => {
    if (!confirmed) return;

    console.log("Deleting room:", roomId);

    fetch("api/delete_room.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ room_id: roomId }),
    })
      .then((r) => r.json())
      .then((data) => {
        console.log("Delete response:", data);
        if (data.success) {
          showNotification("success", "Room deleted successfully");
          loadRooms();
        } else {
          showNotification("error", data.message || "Failed to delete room");
        }
      })
      .catch((err) => {
        console.error("Error deleting room:", err);
        showNotification("error", "Error: " + err.message);
      });
  });
}

// ============================================
// PAGINATION
// ============================================

// Update pagination
function updatePagination(pagination) {
  console.log("=== UPDATING PAGINATION ===");
  console.log("Pagination data:", pagination);

  const info = document.getElementById("paginationInfo");
  if (info) {
    info.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total} entries`;
  }

  const controls = document.getElementById("paginationControls");
  if (!controls) {
    console.error("paginationControls element not found!");
    return;
  }

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
  console.log("✓ Pagination updated with", pagination.pages, "pages");
}

// Change page
function changePage(page) {
  console.log("Changing to page:", page);
  currentPage = page;
  loadRooms();
}

// Close modal when clicking outside (hanya untuk form modal)
window.onclick = function (event) {
  const modal = document.getElementById("roomModal");
  if (event.target === modal) {
    closeModal();
  }
};
