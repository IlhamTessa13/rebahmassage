// Booking Page JavaScript with Modal Notifications & Time Validation
// Global variables
let selectedBranch = null;
let selectedCategory = null;

// ============================================
// OPERATIONAL HOURS CONSTANTS
// ============================================
const CLOSING_TIME = 21; // 21:00 (9 PM)
const OPENING_TIME = 9; // 09:00 (9 AM)

// Calculate max start time based on duration
function getMaxStartTime(durationMinutes) {
  const durationHours = durationMinutes / 60;
  const maxStartHour = CLOSING_TIME - durationHours;

  // Convert to HH:MM format
  const hours = Math.floor(maxStartHour);
  const minutes = (maxStartHour - hours) * 60;

  return `${hours.toString().padStart(2, "0")}:${minutes
    .toString()
    .padStart(2, "0")}`;
}

// Validate if start time is valid for duration
function validateOperationalHours(startTime, durationMinutes) {
  const [hours, minutes] = startTime.split(":").map(Number);
  const startTimeDecimal = hours + minutes / 60;
  const endTimeDecimal = startTimeDecimal + durationMinutes / 60;

  // Check if start time is before opening
  if (startTimeDecimal < OPENING_TIME) {
    return {
      valid: false,
      message: `Rebah Massage opens at ${OPENING_TIME}:00 AM`,
    };
  }

  // Check if end time exceeds closing
  if (endTimeDecimal > CLOSING_TIME) {
    const maxStart = getMaxStartTime(durationMinutes);
    return {
      valid: false,
      message: `For ${durationMinutes} minutes duration, the latest booking time is ${maxStart} to finish before closing time (9:00 PM)`,
    };
  }

  return { valid: true };
}

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
// INITIALIZATION
// ============================================

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  console.log("Booking page loaded");

  // Create notification modal
  createNotificationModal();

  loadBranches();
  setMinDate();
  generateTimeSlots();
});

// ============================================
// LOAD DATA FUNCTIONS
// ============================================

// Load branches
function loadBranches() {
  console.log("Loading branches...");
  fetch("api/get_branches.php")
    .then((response) => {
      console.log("Response status:", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Branches data:", data);
      if (data.success) {
        displayBranches(data.branches);
      } else {
        showNotification(
          "error",
          "Failed to load branches: " + (data.message || "An error occurred")
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("error", "An error occurred while loading branches");
    });
}

// Display branches
function displayBranches(branches) {
  const branchGrid = document.getElementById("branchGrid");
  branchGrid.innerHTML = "";

  branches.forEach((branch) => {
    const branchCard = document.createElement("div");
    branchCard.className = "branch-card";
    branchCard.innerHTML = `
      <img src="${branch.image || "public/branch-default.jpg"}" alt="${
      branch.name
    }" class="branch-image">
      <div class="branch-name">${branch.name}</div>
      <div class="branch-address">${branch.address}</div>
    `;

    branchCard.addEventListener("click", () =>
      selectBranch(branch.id, branchCard)
    );
    branchGrid.appendChild(branchCard);
  });
}

// Select branch
function selectBranch(branchId, cardElement) {
  document.querySelectorAll(".branch-card").forEach((card) => {
    card.classList.remove("selected");
  });

  cardElement.classList.add("selected");
  selectedBranch = branchId;
  document.getElementById("selectedBranch").value = branchId;

  loadCategories(branchId);
  document.getElementById("categorySection").classList.remove("hidden");
  document.getElementById("formSection").classList.add("hidden");

  setTimeout(() => {
    document.getElementById("categorySection").scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }, 100);
}

// Load categories
function loadCategories(branchId) {
  console.log("Loading categories for branch:", branchId);
  fetch(`api/get_categories.php?branch_id=${branchId}`)
    .then((response) => response.json())
    .then((data) => {
      console.log("Categories data:", data);
      if (data.success) {
        displayCategories(data.categories);
      } else {
        showNotification("error", "Failed to load service categories");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("error", "An error occurred while loading categories");
    });
}

// Display categories
function displayCategories(categories) {
  const categoryGrid = document.getElementById("categoryGrid");
  categoryGrid.innerHTML = "";

  categories.forEach((category) => {
    const categoryCard = document.createElement("div");
    categoryCard.className = "category-card";
    categoryCard.innerHTML = `
      <img src="${category.image || "public/categories-default.png"}" alt="${
      category.name
    }" class="category-icon">
      <div class="category-name">${category.name}</div>
      <div class="category-description">${category.description || ""}</div>
    `;

    categoryCard.addEventListener("click", () =>
      selectCategory(category.id, categoryCard)
    );
    categoryGrid.appendChild(categoryCard);
  });
}

// Select category
function selectCategory(categoryId, cardElement) {
  document.querySelectorAll(".category-card").forEach((card) => {
    card.classList.remove("selected");
  });

  cardElement.classList.add("selected");
  selectedCategory = categoryId;
  document.getElementById("selectedCategory").value = categoryId;

  loadRooms(selectedBranch);
  document.getElementById("formSection").classList.remove("hidden");

  setTimeout(() => {
    document.getElementById("formSection").scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }, 100);
}

// Load rooms
function loadRooms(branchId) {
  console.log("Loading rooms for branch:", branchId);
  fetch(`api/get_rooms.php?branch_id=${branchId}`)
    .then((response) => response.json())
    .then((data) => {
      console.log("Rooms data:", data);
      if (data.success) {
        const roomSelect = document.getElementById("room");
        roomSelect.innerHTML = '<option value="">Select room</option>';

        data.rooms.forEach((room) => {
          const option = document.createElement("option");
          option.value = room.id;
          option.textContent = `Room ${room.name}`;
          roomSelect.appendChild(option);
        });
      }
    })
    .catch((error) => console.error("Error:", error));
}

// ============================================
// FORM SETUP WITH TIME VALIDATION
// ============================================

// Set minimum date (today)
function setMinDate() {
  const dateInput = document.getElementById("date");
  const today = new Date().toISOString().split("T")[0];
  dateInput.min = today;
  dateInput.value = today;
}

// Generate time slots (09:00 - 22:00) - will be filtered by duration
function generateTimeSlots() {
  const timeSelect = document.getElementById("time");
  timeSelect.innerHTML = '<option value="">Select time</option>';

  for (let hour = OPENING_TIME; hour <= 22; hour++) {
    const time = `${hour.toString().padStart(2, "0")}:00`;
    const option = document.createElement("option");
    option.value = time;
    option.textContent = time;
    timeSelect.appendChild(option);
  }

  // Add 19:30 for 90-minute bookings
  const option1930 = document.createElement("option");
  option1930.value = "19:30";
  option1930.textContent = "19:30";

  // Insert before 20:00
  const options = Array.from(timeSelect.options);
  const index2000 = options.findIndex((opt) => opt.value === "20:00");
  if (index2000 > -1) {
    timeSelect.insertBefore(option1930, timeSelect.options[index2000]);
  }
}

// Filter time slots based on duration
function filterTimeSlots(durationMinutes) {
  const timeSelect = document.getElementById("time");
  const currentValue = timeSelect.value;
  const maxStartTime = getMaxStartTime(durationMinutes);

  // Store all options
  const allOptions = Array.from(timeSelect.options);

  // Clear and rebuild
  timeSelect.innerHTML = '<option value="">Select time</option>';

  allOptions.forEach((option) => {
    if (option.value === "") return; // Skip placeholder

    // Only show times that are valid for this duration
    if (
      option.value <= maxStartTime &&
      option.value >= `${OPENING_TIME.toString().padStart(2, "0")}:00`
    ) {
      timeSelect.appendChild(option.cloneNode(true));
    }
  });

  // If current selection is invalid, clear it
  const newOptions = Array.from(timeSelect.options);
  const isCurrentValid = newOptions.some((opt) => opt.value === currentValue);

  if (!isCurrentValid && currentValue) {
    timeSelect.value = "";
    showNotification(
      "warning",
      `Selected time is not valid for ${durationMinutes} minutes duration. Please select time up to ${maxStartTime}`,
      "Invalid Time"
    );
  }
}

// Duration change handler - filter time slots
document.getElementById("duration")?.addEventListener("change", function () {
  const duration = parseInt(this.value);
  if (duration > 0) {
    filterTimeSlots(duration);

    const maxStart = getMaxStartTime(duration);
    console.log(`Duration ${duration} minutes - Max start time: ${maxStart}`);

    // Show info message
    const infoMessages = {
      60: "For 60 minutes duration, you can book until 8:00 PM",
      90: "For 90 minutes duration, you can book until 7:30 PM",
      120: "For 120 minutes duration, you can book until 7:00 PM",
    };

    if (infoMessages[duration]) {
      console.log("INFO:", infoMessages[duration]);
    }
  }

  // Trigger availability check
  checkAvailability();
});

// ============================================
// AVAILABILITY CHECKING WITH TIME VALIDATION
// ============================================

// Check availability when inputs change
document.getElementById("date")?.addEventListener("change", checkAvailability);
document.getElementById("time")?.addEventListener("change", checkAvailability);
document.getElementById("room")?.addEventListener("change", checkAvailability);

function checkAvailability() {
  const date = document.getElementById("date").value;
  const time = document.getElementById("time").value;
  const duration = document.getElementById("duration").value;
  const room = document.getElementById("room").value;

  console.log("=== CHECK AVAILABILITY ===");
  console.log(
    "Date:",
    date,
    "Time:",
    time,
    "Duration:",
    duration,
    "Room:",
    room
  );

  // VALIDATE OPERATIONAL HOURS FIRST
  if (time && duration) {
    const validation = validateOperationalHours(time, parseInt(duration));
    if (!validation.valid) {
      showNotification("error", validation.message, "Invalid Time");

      // Clear therapist dropdown
      const therapistSelect = document.getElementById("therapist");
      therapistSelect.innerHTML = '<option value="">Select therapist</option>';
      return;
    }
  }

  if (date && time && duration && room && selectedBranch) {
    console.log("All fields ready, loading therapists...");
    loadTherapists();

    // Check room availability in background
    fetch("api/check_availability.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        branch_id: selectedBranch,
        room_id: room,
        date: date,
        start_time: time,
        duration: duration,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Room availability:", data);
        if (data.success && !data.available) {
          showNotification(
            "warning",
            "This room is not available at the selected time. Please choose another time.",
            "Room Not Available"
          );
        }
      })
      .catch((error) => console.error("Error checking room:", error));
  }
}

// Load therapists
function loadTherapists() {
  const date = document.getElementById("date").value;
  const time = document.getElementById("time").value;
  const duration = document.getElementById("duration").value;

  if (!date || !time || !duration || !selectedBranch) {
    console.log("Missing required fields for therapist loading");
    return;
  }

  console.log("Loading therapists...");

  fetch("api/get_therapists.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      branch_id: selectedBranch,
      date: date,
      start_time: time,
      duration: duration,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Therapist data:", data);

      if (data.success) {
        const therapistSelect = document.getElementById("therapist");
        therapistSelect.innerHTML =
          '<option value="">Select therapist</option>';

        data.therapists.forEach((therapist) => {
          const option = document.createElement("option");
          option.value = therapist.id;
          const gender = therapist.gender || "N/A";
          const name = therapist.name || "Unknown";
          option.textContent = `${name} (${gender})`;
          therapistSelect.appendChild(option);
        });

        if (data.therapists.length === 0) {
          showNotification(
            "warning",
            "No therapists available at the selected time. Please choose another time.",
            "Therapist Not Available"
          );
        }
      } else {
        showNotification(
          "error",
          data.message || "Failed to load therapist list"
        );
      }
    })
    .catch((error) => {
      console.error("Error loading therapists:", error);
      showNotification("error", "An error occurred while loading therapists");
    });
}

// ============================================
// FORM SUBMISSION WITH VALIDATION
// ============================================

// Handle form submission
document
  .getElementById("bookingForm")
  ?.addEventListener("submit", function (e) {
    e.preventDefault();

    const time = document.getElementById("time").value;
    const duration = document.getElementById("duration").value;

    // FINAL VALIDATION BEFORE SUBMIT
    const validation = validateOperationalHours(time, parseInt(duration));
    if (!validation.valid) {
      showNotification("error", validation.message, "Invalid Booking");
      return;
    }

    const btnSubmit = document.getElementById("btnSubmit");
    const btnText = document.getElementById("btnText");
    const btnLoader = document.getElementById("btnLoader");

    // Disable button and show loader
    btnSubmit.disabled = true;
    btnText.textContent = "Processing...";
    btnLoader.classList.remove("hidden");

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    fetch("api/create_booking.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        // ALWAYS re-enable button first
        btnSubmit.disabled = false;
        btnText.textContent = "Confirm Booking";
        btnLoader.classList.add("hidden");

        if (data.success) {
          showNotification(
            "success",
            "Booking created successfully! Waiting for admin approval.\n\nYou will be redirected to the history page...",
            "Booking Successful"
          );

          // Redirect after success
          setTimeout(() => {
            window.location.href = "history.php";
          }, 3000);
        } else {
          // Handle specific error messages
          let errorMessage = data.message || "Failed to create booking";

          if (
            errorMessage.toLowerCase().includes("operational hours") ||
            errorMessage.toLowerCase().includes("jam operasional")
          ) {
            showNotification("error", errorMessage, "Invalid Operating Hours");
          } else if (
            errorMessage.toLowerCase().includes("room") &&
            errorMessage.toLowerCase().includes("not available")
          ) {
            showNotification(
              "error",
              "The selected room is not available at that time. Please choose another room or time.",
              "Room Not Available"
            );
          } else if (
            errorMessage.toLowerCase().includes("therapist") &&
            errorMessage.toLowerCase().includes("not available")
          ) {
            showNotification(
              "error",
              "The selected therapist is not available at that time. Please choose another therapist or time.",
              "Therapist Not Available"
            );
          } else {
            showNotification("error", errorMessage);
          }
        }
      })
      .catch((error) => {
        // ALWAYS re-enable button in catch too
        btnSubmit.disabled = false;
        btnText.textContent = "Confirm Booking";
        btnLoader.classList.add("hidden");

        console.error("Error:", error);
        showNotification(
          "error",
          "An error occurred while creating booking: " + error.message
        );
      });
  });

// Legacy showAlert function for backwards compatibility (optional)
function showAlert(type, message) {
  showNotification(type, message);
}
