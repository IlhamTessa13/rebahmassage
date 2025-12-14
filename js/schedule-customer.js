// Schedule Customer JavaScript
let currentPage = 1;
let editingBookingId = null;
let editingBookingType = null;

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
          <svg id="warningIcon" class="icon-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <circle cx="12" cy="17" r="0.5" fill="currentColor"></circle>
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
  
  document.getElementById("notificationOkBtn").addEventListener("click", closeNotificationModal);
}

// Show notification modal (GANTI FUNGSI showAlert YANG LAMA)
function showNotification(type, message, title = null) {
  const modal = document.getElementById("notificationModal");
  const iconContainer = document.getElementById("notificationIcon");
  const titleElement = document.getElementById("notificationTitle");
  const messageElement = document.getElementById("notificationMessage");
  const successIcon = document.getElementById("successIcon");
  const errorIcon = document.getElementById("errorIcon");
  const warningIcon = document.getElementById("warningIcon");

  if (!title) {
    title = type === "success" ? "Success!" : type === "warning" ? "Warning!" : "Error!";
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

// Show confirmation dialog (GANTI SEMUA confirm() BROWSER)
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

// ============================================
// UBAH FUNGSI showAlert YANG LAMA MENJADI:
// ============================================
function showAlert(type, message) {
  showNotification(type, message);
}

// Load initial data
document.addEventListener("DOMContentLoaded", function () {
  console.log("Schedule Customer loaded");
  console.log(
    "Branch ID:",
    typeof ADMIN_BRANCH_ID !== "undefined" ? ADMIN_BRANCH_ID : "NOT DEFINED"
  );

  // Setup sidebar toggle
  setupSidebarToggle();
  
  createNotificationModal();

  // Load initial data
  loadRooms();
  loadCategories();
  loadTherapists();
  loadSchedules();
  setMinDate();

  // Setup duration change listener for time slots
  document.getElementById("duration").addEventListener("change", function () {
    generateTimeSlots();
  });
});

// Setup sidebar toggle dengan support mobile overlay
function setupSidebarToggle() {
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar =
    document.getElementById("sidebar") || document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

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
      } else {
        // Open sidebar
        sidebar.classList.add("mobile-open");
        overlay.classList.add("active");
        mainContent.classList.add("blurred");
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
        mainContent.classList.remove("expanded");
      } else {
        // Switch to desktop mode
        overlay.classList.remove("active");
        mainContent.classList.remove("blurred");

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

// Load schedules
function loadSchedules() {
  const filterDate = document.getElementById("filterDate").value;
  const filterStatus = document.getElementById("filterStatus").value;
  const filterRoom = document.getElementById("filterRoom").value;
  const entriesPerPage = document.getElementById("entriesPerPage").value;

  const params = new URLSearchParams({
    page: currentPage,
    limit: entriesPerPage,
    branch_id: ADMIN_BRANCH_ID,
    date: filterDate,
    status: filterStatus,
    room_id: filterRoom,
  });

  console.log("Loading schedules with params:", params.toString());

  fetch(`api/get_schedules.php?${params}`)
    .then((r) => {
      console.log("Response status:", r.status);
      if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
      return r.json();
    })
    .then((data) => {
      console.log("Schedules data:", data);
      if (data.success) {
        displaySchedules(data.schedules);
        updatePagination(data.pagination);
      } else {
        showAlert("error", data.message || "Failed to load schedules");
        document.getElementById("scheduleTableBody").innerHTML =
          '<tr><td colspan="10" style="text-align:center;">Error: ' +
          (data.message || "Failed to load") +
          "</td></tr>";
      }
    })
    .catch((err) => {
      console.error("Error loading schedules:", err);
      showAlert("error", "Failed to load schedules: " + err.message);
      document.getElementById("scheduleTableBody").innerHTML =
        '<tr><td colspan="10" style="text-align:center;">Error loading data</td></tr>';
    });
}

// Display schedules in table
function displaySchedules(schedules) {
  const tbody = document.getElementById("scheduleTableBody");

  if (schedules.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="10" style="text-align:center;">No data found</td></tr>';
    return;
  }

  tbody.innerHTML = schedules
    .map((schedule) => {
      let actionButtons = "";

      // Convert status to lowercase for comparison
      const status = schedule.status.toLowerCase();

      console.log(
        `Schedule ID: ${schedule.id}, Status: "${schedule.status}" (lowercase: "${status}")`
      );

      // Buttons based on status
      if (status === "pending") {
        // Pending: Accept & Reject
        actionButtons = `
                <button class="btn btn-accept" onclick="acceptSchedule(${schedule.id}, '${schedule.booking_type}')" title="Accept">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Accept
                </button>
                <button class="btn btn-reject" onclick="rejectSchedule(${schedule.id}, '${schedule.booking_type}')" title="Reject">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Reject
                </button>
            `;
      } else if (status === "approved") {
        // Approved: Edit, Complete, No Show
        actionButtons = `
                <button class="btn btn-edit" onclick="editSchedule(${schedule.id}, '${schedule.booking_type}')" title="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit
                </button>
                <button class="btn btn-complete" onclick="markComplete(${schedule.id}, '${schedule.booking_type}')" title="Mark Complete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Complete
                </button>
                <button class="btn btn-noshow" onclick="markNoShow(${schedule.id}, '${schedule.booking_type}')" title="No Show">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    No Show
                </button>
            `;
      } else if (status === "cancelled" || status === "completed") {
        // Cancelled/Completed: View & Delete
        actionButtons = `
                <button class="btn btn-view" onclick="viewSchedule(${schedule.id}, '${schedule.booking_type}')" title="View Details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    View
                </button>
                <button class="btn btn-delete" onclick="deleteSchedule(${schedule.id}, '${schedule.booking_type}')" title="Delete History">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            `;
      } else {
        // Fallback for unknown status
        actionButtons = `
                <button class="btn btn-view" onclick="viewSchedule(${schedule.id}, '${schedule.booking_type}')" title="View Details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    View
                </button>
            `;
      }

      return `
            <tr>
                <td>${schedule.customer_name}</td>
                <td>${schedule.category_name}</td>
                <td>${schedule.duration} mins</td>
                <td>${schedule.room_name}</td>
                <td>${formatDate(schedule.booking_date)}</td>
                <td>${schedule.start_time.substring(
                  0,
                  5
                )} - ${schedule.end_time.substring(0, 5)}</td>
                <td>${schedule.therapist_name}</td>
                <td><span class="status-${schedule.status}">${
        schedule.status
      }</span></td>
                <td><span class="badge-${schedule.booking_type}">${
        schedule.booking_type
      }</span></td>
                <td class="action-buttons">
                    ${actionButtons}
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
  loadSchedules();
}

function acceptSchedule(id, type) {
  showConfirmation(
    "Accept Booking",
    "Are you sure you want to accept this booking? The customer will receive an email notification."
  ).then((confirmed) => {
    if (!confirmed) return;

    fetch("api/accept_schedule.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: id, type: type }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          let message = "Booking accepted successfully";

          if (data.email_sent) {
            message +=
              "\n\n📧 Confirmation email has been sent to the customer.";
          } else {
            message += "\n\n⚠️ Note: Email notification could not be sent.";
          }

          showNotification("success", message);
          loadSchedules();
        } else {
          showNotification("error", data.message);
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Failed to accept booking");
      });
  });
}

function rejectSchedule(id, type) {
  showConfirmation(
    "Reject Booking",
    "Are you sure you want to reject this booking?"
  ).then((confirmed) => {
    if (!confirmed) return;

    const reason = prompt("Reason for rejection (optional):");
    if (reason === null) return;

    fetch("api/reject_schedule.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: id, type: type, reason: reason }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          let message = "Booking rejected successfully";

          if (data.email_sent) {
            message += "\n\n📧 Rejection email has been sent to the customer.";
          }

          showNotification("success", message);
          loadSchedules();
        } else {
          showNotification("error", data.message);
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Failed to reject booking");
      });
  });
}

// Mark complete
function markComplete(id, type) {
  showConfirmation("Mark as Complete", "Mark this booking as completed?").then(
    (confirmed) => {
      if (!confirmed) return;

      fetch("api/mark_complete.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id, type: type }),
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            showNotification("success", "Booking marked as completed");
            loadSchedules();
          } else {
            showNotification("error", data.message);
          }
        })
        .catch((err) => {
          console.error("Error:", err);
          showNotification("error", "Failed to mark complete");
        });
    }
  );
}

// Mark no show
function markNoShow(id, type) {
  showConfirmation(
    "Mark as No Show",
    "Mark this booking as no show (cancelled)?"
  ).then((confirmed) => {
    if (!confirmed) return;

    fetch("api/mark_noshow.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: id, type: type }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          showNotification("success", "Booking marked as no show");
          loadSchedules();
        } else {
          showNotification("error", data.message);
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Failed to mark no show");
      });
  });
}

// View schedule details dengan custom modal
function viewSchedule(id, type) {
  fetch(`api/get_booking_detail.php?id=${id}&type=${type}`)
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        const booking = data.booking;
        
        // Format detail booking dengan styling yang lebih compact
        const details = `
<div style="text-align: left; line-height: 1.6; font-size: 14px;">
  <div style="display: grid; grid-template-columns: 100px 1fr; gap: 8px 12px; margin-bottom: 0;">
    <strong style="color: #46486f;">Customer:</strong>
    <span>${booking.customer_name}</span>
    
    <strong style="color: #46486f;">Category:</strong>
    <span>${booking.category_name || "N/A"}</span>
    
    <strong style="color: #46486f;">Duration:</strong>
    <span>${booking.duration} mins</span>
    
    <strong style="color: #46486f;">Room:</strong>
    <span>${booking.room_name || "N/A"}</span>
    
    <strong style="color: #46486f;">Date:</strong>
    <span>${formatDate(booking.booking_date)}</span>
    
    <strong style="color: #46486f;">Time:</strong>
    <span>${booking.start_time.substring(0, 5)} - ${booking.end_time.substring(0, 5)}</span>
    
    <strong style="color: #46486f;">Therapist:</strong>
    <span>${booking.therapist_name || "N/A"}</span>
    
    <strong style="color: #46486f;">Status:</strong>
    <span style="text-transform: uppercase; font-weight: 600; color: #007bff;">${booking.status}</span>
    
    <strong style="color: #46486f;">Type:</strong>
    <span style="text-transform: uppercase; font-weight: 600; color: #6c757d;">${type}</span>
  </div>
</div>
        `.trim();
        
        // Gunakan modal yang sudah ada dengan modifikasi
        showBookingDetailModal("Booking Details", details);
      } else {
        showNotification("error", "Failed to load booking details");
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Error loading booking details");
    });
}

// Fungsi untuk menampilkan detail booking dalam modal
function showBookingDetailModal(title, htmlContent) {
  const modal = document.getElementById("notificationModal");
  const iconContainer = document.getElementById("notificationIcon");
  const titleElement = document.getElementById("notificationTitle");
  const messageElement = document.getElementById("notificationMessage");
  const successIcon = document.getElementById("successIcon");
  const errorIcon = document.getElementById("errorIcon");
  const warningIcon = document.getElementById("warningIcon");

  titleElement.textContent = title;
  messageElement.innerHTML = htmlContent;
  
  // Reset styles untuk notifikasi normal
  messageElement.style.textAlign = "left";
  messageElement.style.maxHeight = "none";
  messageElement.style.overflowY = "visible";

  // Sembunyikan semua icon
  successIcon.style.display = "none";
  errorIcon.style.display = "none";
  warningIcon.style.display = "none";
  
  // Gunakan icon info
  iconContainer.className = "notification-icon info";
  iconContainer.style.background = "linear-gradient(135deg, #cce5ff 0%, #b8daff 100%)";
  iconContainer.style.borderColor = "#007bff";
  
  // Buat icon info custom
  iconContainer.innerHTML = `
    <svg style="width: 48px; height: 48px; stroke: #007bff;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
      <circle cx="12" cy="12" r="10"></circle>
      <line x1="12" y1="16" x2="12" y2="12"></line>
      <circle cx="12" cy="8" r="0.5" fill="currentColor"></circle>
    </svg>
  `;

  modal.style.display = "flex";
  setTimeout(() => modal.classList.add("show"), 10);
}

// Edit schedule (only room & therapist)
function editSchedule(id, type) {
  editingBookingId = id;
  editingBookingType = type;
  document.getElementById("modalTitle").textContent =
    "Edit Booking (Room & Therapist Only)";

  // Fetch booking data
  fetch(`api/get_booking_detail.php?id=${id}&type=${type}`)
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        const booking = data.booking;

        // Hide groups by ID
        document.getElementById("customerNameGroup").style.display = "none";
        document.getElementById("categoryGroup").style.display = "none";
        document.getElementById("durationRow").style.display = "none";
        document.getElementById("dateRow").style.display = "none";

        // Show therapist group
        document.getElementById("therapistGroup").style.display = "block";

        // Remove required attribute for hidden fields
        document.getElementById("customerName").removeAttribute("required");
        document.getElementById("category").removeAttribute("required");
        document.getElementById("duration").removeAttribute("required");
        document.getElementById("bookingDate").removeAttribute("required");
        document.getElementById("startTime").removeAttribute("required");

        // Add visible room field before therapist (if not exists)
        if (!document.getElementById("editRoomGroup")) {
          const roomHTML = `
                        <div class="form-group" id="editRoomGroup">
                            <label for="editRoom">Room *</label>
                            <select id="editRoom" name="edit_room_id" required>
                                ${document.getElementById("room").innerHTML}
                            </select>
                        </div>
                    `;
          document
            .getElementById("therapistGroup")
            .insertAdjacentHTML("beforebegin", roomHTML);
        }

        document.getElementById("bookingId").value = booking.id;
        document.getElementById("bookingType").value = type;
        document.getElementById("editRoom").value = booking.room_id;
        document.getElementById("therapist").value = booking.therapist_id;

        document.getElementById("bookingModal").style.display = "block";
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showAlert("error", "Failed to load booking details");
    });
}

// Delete schedule
function deleteSchedule(id, type) {
  showConfirmation(
    "Delete Booking",
    "Are you sure you want to delete this booking history? This action cannot be undone."
  ).then((confirmed) => {
    if (!confirmed) return;

    fetch("api/delete_schedule.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: id, type: type }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          showNotification("success", "Booking deleted successfully");
          loadSchedules();
        } else {
          showNotification("error", data.message);
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Failed to delete booking");
      });
  });
}

// Open add modal
function openAddModal() {
  editingBookingId = null;
  editingBookingType = "offline";
  document.getElementById("modalTitle").textContent = "Add Offline Booking";
  document.getElementById("bookingForm").reset();
  document.getElementById("bookingId").value = "";
  document.getElementById("bookingType").value = "offline";

  // Remove edit room group if exists
  const editRoomGroup = document.getElementById("editRoomGroup");
  if (editRoomGroup) {
    editRoomGroup.remove();
  }

  // Show all fields and restore required attributes
  document.getElementById("customerNameGroup").style.display = "block";
  document.getElementById("categoryGroup").style.display = "block";
  document.getElementById("durationRow").style.display = "grid";
  document.getElementById("dateRow").style.display = "grid";
  document.getElementById("therapistGroup").style.display = "block";

  // Restore required attributes
  document.getElementById("customerName").setAttribute("required", "required");
  document.getElementById("category").setAttribute("required", "required");
  document.getElementById("duration").setAttribute("required", "required");
  document.getElementById("bookingDate").setAttribute("required", "required");
  document.getElementById("startTime").setAttribute("required", "required");

  document.getElementById("bookingModal").style.display = "block";
  generateTimeSlots(); // Reset time slots
}

// Close modal
function closeModal() {
  document.getElementById("bookingModal").style.display = "none";
  document.getElementById("bookingForm").reset();
}

/// GANTI FUNGSI saveBooking() YANG LAMA DENGAN INI:

function saveBooking() {
  const form = document.getElementById("bookingForm");

  // For edit mode, we don't need to validate hidden fields
  if (editingBookingId) {
    // Only validate room and therapist for edit
    const editRoomEl = document.getElementById("editRoom");
    const roomId = editRoomEl
      ? editRoomEl.value
      : document.getElementById("room").value;
    const therapistId = document.getElementById("therapist").value;

    if (!roomId || !therapistId) {
      showNotification("error", "Please select both room and therapist");
      return;
    }

    // Update only room and therapist
    fetch("api/update_schedule.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: editingBookingId,
        type: editingBookingType,
        room_id: roomId,
        therapist_id: therapistId,
      }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          // Enhanced success message
          let successMessage = data.message;
          
          // Add email notification info if available
          if (data.email_sent) {
            successMessage += "\n\n📧 Email notification has been sent to the customer.";
          }
          
          // Show changes info
          if (data.changes) {
            const changes = [];
            if (data.changes.room_changed) changes.push("Room");
            if (data.changes.therapist_changed) changes.push("Therapist");
            
            if (changes.length > 0) {
              successMessage += "\n\nChanged: " + changes.join(" & ");
            }
          }
          
          showNotification("success", successMessage);
          closeModal();
          loadSchedules();
        } else {
          showNotification("error", data.message);
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Failed to update booking");
      });
  } else {
    // Create new offline booking - validate all fields
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    data.branch_id = ADMIN_BRANCH_ID;

    fetch("api/create_offline_booking.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          showNotification("success", "Offline booking created successfully");
          closeModal();
          loadSchedules();
        } else {
          showNotification("error", data.message);
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Failed to create booking");
      });
  }
}

// Load rooms
function loadRooms() {
  console.log("Loading rooms for branch:", ADMIN_BRANCH_ID);

  fetch(`api/admin_get_rooms.php?branch_id=${ADMIN_BRANCH_ID}`)
    .then((r) => {
      console.log("Rooms response status:", r.status);
      return r.json();
    })
    .then((data) => {
      console.log("Rooms data:", data);
      if (data.success && data.rooms) {
        const select = document.getElementById("room");
        const filterSelect = document.getElementById("filterRoom");

        if (data.rooms.length === 0) {
          console.warn("No rooms found for this branch");
          select.innerHTML = '<option value="">No rooms available</option>';
          filterSelect.innerHTML = '<option value="">No rooms</option>';
        } else {
          select.innerHTML =
            '<option value="">Select Room</option>' +
            data.rooms
              .map((r) => `<option value="${r.id}">${r.name}</option>`)
              .join("");

          filterSelect.innerHTML =
            '<option value="">All Rooms</option>' +
            data.rooms
              .map((r) => `<option value="${r.id}">${r.name}</option>`)
              .join("");

          console.log("Loaded", data.rooms.length, "rooms");
        }
      } else {
        console.error("Failed to load rooms:", data.message);
        showAlert("error", "Failed to load rooms");
      }
    })
    .catch((err) => {
      console.error("Error loading rooms:", err);
      showAlert("error", "Error loading rooms");
    });
}

// Load categories
function loadCategories() {
  console.log("Loading categories for branch:", ADMIN_BRANCH_ID);

  fetch(`api/admin_get_categories.php?branch_id=${ADMIN_BRANCH_ID}`)
    .then((r) => {
      console.log("Categories response status:", r.status);
      return r.json();
    })
    .then((data) => {
      console.log("Categories data:", data);
      if (data.success && data.categories) {
        const select = document.getElementById("category");

        if (data.categories.length === 0) {
          console.warn("No categories found for this branch");
          select.innerHTML =
            '<option value="">No categories available</option>';
        } else {
          select.innerHTML =
            '<option value="">Select Category</option>' +
            data.categories
              .map((c) => `<option value="${c.id}">${c.name}</option>`)
              .join("");

          console.log("Loaded", data.categories.length, "categories");
        }
      } else {
        console.error("Failed to load categories:", data.message);
        showAlert("error", "Failed to load categories");
      }
    })
    .catch((err) => {
      console.error("Error loading categories:", err);
      showAlert("error", "Error loading categories");
    });
}

// Load therapists
function loadTherapists() {
  console.log("Loading therapists for branch:", ADMIN_BRANCH_ID);

  fetch(`api/get_all_therapists.php?branch_id=${ADMIN_BRANCH_ID}`)
    .then((r) => {
      console.log("Therapists response status:", r.status);
      return r.json();
    })
    .then((data) => {
      console.log("Therapists data:", data);
      if (data.success) {
        const select = document.getElementById("therapist");
        select.innerHTML =
          '<option value="">Select Therapist</option>' +
          data.therapists
            .map(
              (t) => `<option value="${t.id}">${t.name} (${t.gender})</option>`
            )
            .join("");
      } else {
        console.error("Failed to load therapists:", data.message);
        showAlert("error", "Failed to load therapists");
      }
    })
    .catch((err) => {
      console.error("Error loading therapists:", err);
      showAlert("error", "Error loading therapists");
    });
}

// Generate time slots based on duration
function generateTimeSlots() {
  const select = document.getElementById("startTime");
  const duration = parseInt(document.getElementById("duration").value) || 0;

  select.innerHTML = '<option value="">Select Time</option>';

  if (duration === 0) return;

  // Operating hours: 9:00 - 21:00 (9 AM - 9 PM)
  const openHour = 9;
  const closeHour = 21;

  // Calculate last possible start time based on duration
  // If 60 mins: last slot is 20:00 (ends at 21:00)
  // If 90 mins: last slot is 19:30 (ends at 21:00)
  // If 120 mins: last slot is 19:00 (ends at 21:00)
  const durationHours = duration / 60;
  const lastStartHour = closeHour - durationHours;

  // Generate time slots every 30 minutes
  for (let h = openHour; h < closeHour; h++) {
    for (let m = 0; m < 60; m += 30) {
      const currentTime = h + m / 60;

      // Only show times that allow the session to end by closing time
      if (currentTime <= lastStartHour) {
        const timeStr = `${h.toString().padStart(2, "0")}:${m
          .toString()
          .padStart(2, "0")}`;
        select.innerHTML += `<option value="${timeStr}">${timeStr}</option>`;
      }
    }
  }
}

// Set minimum date to today
function setMinDate() {
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("bookingDate").min = today;
}

// Format date
function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

// Show alert
function showAlert(type, message) {
  const container = document.getElementById("alertContainer");
  const alert = document.createElement("div");
  alert.className = `alert alert-${type}`;
  alert.textContent = message;
  alert.style.display = "block";
  container.appendChild(alert);

  setTimeout(() => {
    alert.remove();
  }, 5000);
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("bookingModal");
  if (event.target === modal) {
    closeModal();
  }
};

// Download Excel
function downloadExcel() {
  const filterDate = document.getElementById("filterDate").value;
  const filterStatus = document.getElementById("filterStatus").value;
  const filterRoom = document.getElementById("filterRoom").value;

  const params = new URLSearchParams({
    branch_id: ADMIN_BRANCH_ID,
    date: filterDate,
    status: filterStatus,
    room_id: filterRoom,
    export: "excel",
  });

  console.log("Downloading Excel with params:", params.toString());

  // Open download in new window
  window.open(`api/export_schedules_excel.php?${params}`, "_blank");
}
