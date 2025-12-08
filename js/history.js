// History Page JavaScript with Modal Notifications

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

// ============================================
// INITIALIZATION
// ============================================

// Initialize on page load
// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Create notification modal
  createNotificationModal();

  // Mobile Navigation
  const hamburger = document.getElementById("hamburgerBtn");
  const navContainer = document.getElementById("navContainer");
  const navClose = document.getElementById("navClose");
  const navOverlay = document.getElementById("navOverlay");
  const navLinks = document.querySelectorAll(".nav-link");

  function openNav() {
    navContainer.classList.add("active");
    navOverlay.classList.add("active");
    hamburger.classList.add("hide");
    document.body.style.overflow = "hidden";
  }

  function closeNav() {
    navContainer.classList.remove("active");
    navOverlay.classList.remove("active");
    hamburger.classList.remove("hide");
    document.body.style.overflow = "";
  }

  if (hamburger) {
    hamburger.addEventListener("click", openNav);
  }

  if (navClose) {
    navClose.addEventListener("click", closeNav);
  }

  if (navOverlay) {
    navOverlay.addEventListener("click", closeNav);
  }

  // Close nav when clicking nav links
  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      if (window.innerWidth <= 768) {
        closeNav();
      }
    });
  });

  // Close nav on ESC key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && navContainer.classList.contains("active")) {
      closeNav();
    }
  });

  // Load bookings
  loadBookings();
});
// ============================================
// LOAD & DISPLAY BOOKINGS
// ============================================

// Load bookings
function loadBookings() {
  const tableBody = document.getElementById("bookingsTableBody");

  // Show loading
  tableBody.innerHTML = `
    <tr>
      <td colspan="9" class="loading">
        <div class="loader"></div>
        <p>Loading your bookings...</p>
      </td>
    </tr>
  `;

  fetch("api/get_bookings.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayBookings(data.bookings);
      } else {
        showNotification(
          "error",
          data.message || "Failed to load booking history"
        );
        showError("Failed to load bookings");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        "error",
        "An error occurred while loading booking history"
      );
      showError("Error loading bookings");
    });
}

// Display bookings
function displayBookings(bookings) {
  const tableBody = document.getElementById("bookingsTableBody");

  if (bookings.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="9" class="empty-state">
          <div class="empty-state-icon">📅</div>
          <div class="empty-state-text">No bookings yet</div>
          <div class="empty-state-subtext">Start by making your first booking!</div>
        </td>
      </tr>
    `;

    // Also update cards container for mobile
    const cardsContainer = document.getElementById("bookingCardsContainer");
    if (cardsContainer) {
      cardsContainer.innerHTML = `
        <div class="empty-state">
          <div class="empty-state-icon">📅</div>
          <div class="empty-state-text">No bookings yet</div>
          <div class="empty-state-subtext">Start by making your first booking!</div>
        </div>
      `;
    }
    return;
  }

  tableBody.innerHTML = "";

  // Get cards container for mobile view
  const cardsContainer = document.getElementById("bookingCardsContainer");
  if (cardsContainer) {
    cardsContainer.innerHTML = "";
  }

  bookings.forEach((booking) => {
    // Format date
    const date = new Date(booking.booking_date);
    const formattedDate = date.toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });

    // Format time
    const time = booking.start_time.substring(0, 5); // HH:MM

    // Status badge
    const statusClass = `status-${booking.status.toLowerCase()}`;
    const statusBadge = `<span class="status-badge ${statusClass}">${booking.status}</span>`;

    // Action buttons based on status
    let actionButtons = "";

    if (booking.status === "pending") {
      actionButtons = `
        <div class="action-buttons">
          <button class="btn-action btn-cancel" onclick="cancelBooking(${booking.id})">
            Cancel
          </button>
        </div>
      `;
    } else if (booking.status === "approved") {
      actionButtons = `
        <div class="action-buttons">
          <button class="btn-action btn-cancel" onclick="cancelBooking(${booking.id})">
            Cancel
          </button>
          <a href="api/download_invoice.php?booking_id=${booking.id}" class="btn-action btn-download">
            Download
          </a>
        </div>
      `;
    } else if (booking.status === "complete") {
      actionButtons = `
        <div class="action-buttons">
          <a href="api/download_invoice.php?booking_id=${booking.id}" class="btn-action btn-download">
            Download
          </a>
        </div>
      `;
    } else if (
      booking.status === "rejected" ||
      booking.status === "cancelled"
    ) {
      actionButtons = `
        <div class="action-buttons">
          <span style="color: #999; font-size: 0.85rem;">No actions available</span>
        </div>
      `;
    }

    // Create table row (for desktop)
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${String(booking.id).padStart(3, "0")}</td>
      <td>${booking.branch_name}</td>
      <td>${booking.category_name}</td>
      <td>${booking.duration}</td>
      <td>${booking.room_name}</td>
      <td>${formattedDate}</td>
      <td>${time}</td>
      <td>${statusBadge}</td>
      <td>${actionButtons}</td>
    `;
    tableBody.appendChild(row);

    // Create card (for mobile/tablet)
    if (cardsContainer) {
      const card = document.createElement("div");
      card.className = "booking-card";
      card.innerHTML = `
        <div class="booking-card-header">
          <div>
            <div class="booking-card-title">${booking.branch_name}</div>
            <div class="booking-card-code">Booking Code: ${String(
              booking.id
            ).padStart(3, "0")}</div>
          </div>
          ${statusBadge}
        </div>
        
        <div class="booking-card-body">
          <div class="booking-info-row">
            <span class="booking-info-label">Service</span>
            <span class="booking-info-value">${booking.category_name}</span>
          </div>
          
          <div class="booking-detail-section">
            <div class="booking-detail-title">Booking Details</div>
            <div class="booking-detail-grid">
              <div class="booking-detail-item">
                <div class="booking-detail-label">Date</div>
                <div class="booking-detail-value">${formattedDate}</div>
              </div>
              <div class="booking-detail-item">
                <div class="booking-detail-label">Time</div>
                <div class="booking-detail-value">${time}</div>
              </div>
              <div class="booking-detail-item">
                <div class="booking-detail-label">Duration</div>
                <div class="booking-detail-value">${booking.duration}</div>
              </div>
            </div>
          </div>
          
          <div class="booking-info-row">
            <span class="booking-info-label">Room</span>
            <span class="booking-info-value">${booking.room_name}</span>
          </div>
        </div>
        
        ${actionButtons}
      `;
      cardsContainer.appendChild(card);
    }
  });
}

// ============================================
// CANCEL BOOKING WITH MODAL CONFIRMATION
// ============================================

// Cancel booking
function cancelBooking(bookingId) {
  // Show confirmation modal
  showConfirmation(
    "Cancel Booking",
    "Are you sure you want to cancel this booking? This action cannot be undone."
  ).then((confirmed) => {
    if (!confirmed) return;

    // Find the specific button for this booking
    const button = event.target;
    const originalText = button.textContent;

    // Disable the button
    button.disabled = true;
    button.textContent = "Cancelling...";
    button.style.opacity = "0.6";

    fetch("api/cancel_booking.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ booking_id: bookingId }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Cancel response:", data);

        if (data.success) {
          showNotification(
            "success",
            "Booking successfully cancelled!",
            "Booking Cancelled"
          );

          // Reload bookings after 1 second
          setTimeout(() => {
            loadBookings();
          }, 1000);
        } else {
          showNotification(
            "error",
            data.message || "Failed to cancel booking. Please try again."
          );

          // Re-enable button
          button.disabled = false;
          button.textContent = originalText;
          button.style.opacity = "1";
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showNotification(
          "error",
          "A network error occurred. Please try again."
        );

        // Re-enable button
        button.disabled = false;
        button.textContent = originalText;
        button.style.opacity = "1";
      });
  });
}

// ============================================
// LEGACY FUNCTIONS (for backward compatibility)
// ============================================

// Show error message (legacy)
function showError(message) {
  // Still show in table if needed
  const tableBody = document.getElementById("bookingsTableBody");
  if (tableBody) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="9" class="empty-state">
          <div class="empty-state-icon">❌</div>
          <div class="empty-state-text">${message}</div>
        </td>
      </tr>
    `;
  }
}

// Show success message (legacy - redirects to showNotification)
function showSuccess(message) {
  showNotification("success", message);
}
