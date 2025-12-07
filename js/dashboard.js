// Dashboard JavaScript
let reservationChart = null;

document.addEventListener("DOMContentLoaded", function () {
  console.log("Dashboard loaded");
  console.log(
    "Branch ID:",
    typeof ADMIN_BRANCH_ID !== "undefined" ? ADMIN_BRANCH_ID : "NOT DEFINED"
  );

  // Sidebar toggle functionality
  setupSidebarToggle();

  // Load dashboard data
  loadDashboardStats();
  loadChartData("7days");
  loadRecentBookings();
  loadTherapistToday();

  // Auto cancel expired bookings
  autoCancelExpired();

  // Setup chart period buttons
  setupChartButtons();
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

// Load dashboard statistics
function loadDashboardStats() {
  fetch(`api/get_dashboard_stats.php?branch_id=${ADMIN_BRANCH_ID}`)
    .then((r) => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then((data) => {
      console.log("Stats data:", data);
      if (data.success) {
        document.getElementById("totalReservasi").textContent =
          data.stats.reservasi;
        document.getElementById("totalCustomer").textContent =
          data.stats.customer;
        document.getElementById("totalTherapist").textContent =
          data.stats.therapist;
      } else {
        console.error("Failed to load stats:", data.message);
        showError("Failed to load statistics");
      }
    })
    .catch((err) => {
      console.error("Error loading stats:", err);
      showError("Error loading statistics");
    });
}

// Load chart data
function loadChartData(period) {
  fetch(`api/get_chart_data.php?branch_id=${ADMIN_BRANCH_ID}&period=${period}`)
    .then((r) => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then((data) => {
      console.log("Chart data:", data);
      if (data.success) {
        updateChart(data.labels, data.data);
        document.getElementById("currentValue").textContent = data.total;
      } else {
        console.error("Failed to load chart data:", data.message);
        showError("Failed to load chart data");
      }
    })
    .catch((err) => {
      console.error("Error loading chart data:", err);
      showError("Error loading chart data");
    });
}

// Update chart
function updateChart(labels, data) {
  const ctx = document.getElementById("reservationChart").getContext("2d");

  if (reservationChart) {
    reservationChart.data.labels = labels;
    reservationChart.data.datasets[0].data = data;
    reservationChart.update("active");
  } else {
    reservationChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Jumlah Reservasi",
            data: data,
            borderColor: "#46486F",
            backgroundColor: "rgba(70, 72, 111, 0.1)",
            fill: true,
            tension: 0.4,
            pointBackgroundColor: "#46486F",
            pointBorderColor: "#fff",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            backgroundColor: "rgba(0, 0, 0, 0.8)",
            padding: 10,
            titleFont: {
              size: 12,
            },
            bodyFont: {
              size: 13,
              weight: "bold",
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "rgba(0, 0, 0, 0.05)",
            },
            ticks: {
              font: {
                size: 11,
              },
            },
          },
          x: {
            grid: {
              display: false,
            },
            ticks: {
              font: {
                size: 11,
              },
            },
          },
        },
      },
    });
  }
}

// Setup chart period buttons
function setupChartButtons() {
  document.querySelectorAll(".period-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      // Update active button
      document
        .querySelectorAll(".period-btn")
        .forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      // Load chart data for selected period
      const period = this.dataset.period;
      loadChartData(period);
    });
  });
}

// Load recent bookings
function loadRecentBookings() {
  fetch(`api/get_recent_bookings.php?branch_id=${ADMIN_BRANCH_ID}`)
    .then((r) => r.json())
    .then((data) => {
      console.log("Recent bookings:", data);
      if (data.success) {
        displayRecentBookings(data.bookings);
      } else {
        console.error("Failed to load recent bookings:", data.message);
      }
    })
    .catch((err) => {
      console.error("Error loading recent bookings:", err);
    });
}

// Display recent bookings
function displayRecentBookings(bookings) {
  const tbody = document.getElementById("recentBookingsBody");

  if (bookings.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="7" style="text-align:center;">No bookings today</td></tr>';
    return;
  }

  tbody.innerHTML = bookings
    .map(
      (booking) => `
        <tr>
            <td>#${String(booking.id).padStart(4, "0")}</td>
            <td>${booking.customer_name || "-"}</td>
            <td>${booking.service_name || "-"}</td>
            <td>${booking.therapist_name || "-"}</td>
            <td>${formatDate(booking.booking_date)}</td>
            <td>${booking.start_time.substring(0, 5)}</td>
            <td>
                <span class="status-badge ${booking.status}">
                    ${capitalizeFirst(booking.status)}
                </span>
            </td>
        </tr>
    `
    )
    .join("");
}

// Load therapist today
function loadTherapistToday() {
  fetch(`api/get_therapist_today.php?branch_id=${ADMIN_BRANCH_ID}`)
    .then((r) => r.json())
    .then((data) => {
      console.log("Therapist today:", data);
      if (data.success) {
        displayTherapists(data.therapists);
      } else {
        console.error("Failed to load therapists:", data.message);
      }
    })
    .catch((err) => {
      console.error("Error loading therapists:", err);
    });
}

// Display therapists
function displayTherapists(therapists) {
  const grid = document.getElementById("therapistGrid");

  if (therapists.length === 0) {
    grid.innerHTML =
      '<p style="text-align:center;">No active therapists today</p>';
    return;
  }

  grid.innerHTML = therapists
    .map(
      (therapist) => `
        <div class="therapist-card">
            <div class="therapist-avatar">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            <h3>${therapist.name}</h3>
            <p class="bookings-count">${therapist.bookings_count} Bookings</p>
            <span class="status-indicator ${therapist.status}">
                ${capitalizeFirst(therapist.status)}
            </span>
        </div>
    `
    )
    .join("");
}

// Auto cancel expired bookings
function autoCancelExpired() {
  fetch("api/auto_cancel_expired.php")
    .then((r) => r.json())
    .then((data) => {
      console.log("Auto cancel result:", data);
      if (data.success && data.cancelled.total > 0) {
        console.log(`Cancelled ${data.cancelled.total} expired bookings`);
        // Reload dashboard data after cancellation
        loadDashboardStats();
        loadRecentBookings();
      }
    })
    .catch((err) => {
      console.error("Error auto cancelling:", err);
    });
}

// Helper functions
function formatDate(dateStr) {
  if (!dateStr) return "-";
  const date = new Date(dateStr);
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function capitalizeFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
}

// Show error message
function showError(message) {
  console.error("Dashboard Error:", message);
  // You can add UI notification here if needed
}
