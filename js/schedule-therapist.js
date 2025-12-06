// Schedule Therapist JavaScript
let currentPage = 1;

document.addEventListener("DOMContentLoaded", function () {
  console.log("Schedule Therapist loaded");
  console.log(
    "Branch ID:",
    typeof ADMIN_BRANCH_ID !== "undefined" ? ADMIN_BRANCH_ID : "NOT DEFINED"
  );

  // Load schedules
  loadSchedules();

  // Sidebar toggle functionality
  setupSidebarToggle();
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

// Load schedules
function loadSchedules() {
  const entriesPerPage = document.getElementById("entriesPerPage").value;

  const params = new URLSearchParams({
    page: currentPage,
    limit: entriesPerPage,
    branch_id: ADMIN_BRANCH_ID,
    status: "approved",
  });

  console.log("Loading schedules with params:", params.toString());

  fetch(`/php/api/get_therapist_schedules.php?${params}`)
    .then((r) => {
      console.log("Response status:", r.status);
      if (!r.ok) {
        throw new Error(`HTTP error! status: ${r.status}`);
      }
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
          '<tr><td colspan="6" style="text-align:center;">Error: ' +
          (data.message || "Failed to load") +
          "</td></tr>";
      }
    })
    .catch((err) => {
      console.error("Error loading schedules:", err);
      showAlert("error", "Failed to load schedules: " + err.message);
      document.getElementById("scheduleTableBody").innerHTML =
        '<tr><td colspan="6" style="text-align:center;">Error loading data</td></tr>';
    });
}

// Display schedules in table
function displaySchedules(schedules) {
  const tbody = document.getElementById("scheduleTableBody");

  if (schedules.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" style="text-align:center;">No approved schedules found</td></tr>';
    return;
  }

  tbody.innerHTML = schedules
    .map(
      (schedule) => `
        <tr>
            <td>${schedule.therapist_name || "-"}</td>
            <td>${schedule.room_name || "-"}</td>
            <td>${schedule.category_name || "-"}</td>
            <td>${formatDate(schedule.booking_date)}</td>
            <td>${schedule.start_time.substring(0, 5)}</td>
            <td>${schedule.end_time.substring(0, 5)}</td>
        </tr>
    `
    )
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

// Format date
function formatDate(dateStr) {
  if (!dateStr) return "-";
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
