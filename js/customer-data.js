// Customer Data JavaScript - Complete File
// File: js/customer-data.js

// Global variables
let currentPage = 1;

document.addEventListener("DOMContentLoaded", function () {
  console.log("Customer Data page loaded");

  // Sidebar toggle functionality
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.querySelector(".main-content");

  if (toggleBtn && sidebar && mainContent) {
    // Check localStorage for sidebar state
    const sidebarState = localStorage.getItem("sidebarState");
    if (sidebarState === "closed") {
      sidebar.classList.add("closed");
      mainContent.classList.add("expanded");
    }

    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("closed");
      mainContent.classList.toggle("expanded");

      // Save state to localStorage
      if (sidebar.classList.contains("closed")) {
        localStorage.setItem("sidebarState", "closed");
      } else {
        localStorage.setItem("sidebarState", "open");
      }
    });
  }

  // Load customers on page load
  loadCustomers();
});

// Load customers function
function loadCustomers() {
  const entriesPerPage = document.getElementById("entriesPerPage").value;
  console.log("=== LOADING CUSTOMERS ===");
  console.log("Current page:", currentPage);
  console.log("Entries per page:", entriesPerPage);

  const params = new URLSearchParams({
    page: currentPage,
    limit: entriesPerPage,
  });

  console.log("Fetching:", `api/get_customers.php?${params}`);

  fetch(`api/get_customers.php?${params}`)
    .then((r) => {
      console.log("Response status:", r.status);
      if (!r.ok) {
        throw new Error(`HTTP error! status: ${r.status}`);
      }
      return r.text(); // Ambil sebagai text dulu untuk debugging
    })
    .then((text) => {
      console.log("Raw response:", text.substring(0, 200)); // Log first 200 chars
      try {
        const data = JSON.parse(text);
        console.log("Parsed data:", data);

        if (data.success) {
          console.log("Number of customers:", data.customers.length);
          displayCustomers(data.customers);
          updatePagination(data.pagination);
        } else {
          console.error("API returned error:", data.message);
          showAlert("error", data.message || "Failed to load customers");
          document.getElementById("customerTableBody").innerHTML =
            '<tr><td colspan="5" style="text-align:center;">Error: ' +
            (data.message || "Failed to load") +
            "</td></tr>";
        }
      } catch (e) {
        console.error("JSON Parse Error:", e);
        console.error("Text was:", text);
        showAlert("error", "Invalid response from server");
        throw new Error("Invalid JSON response");
      }
    })
    .catch((err) => {
      console.error("Fetch Error:", err);
      showAlert("error", "Failed to load customers: " + err.message);
      document.getElementById("customerTableBody").innerHTML =
        '<tr><td colspan="5" style="text-align:center; color: red;">Error loading data: ' +
        err.message +
        "</td></tr>";
    });
}

// Display customers in table
function displayCustomers(customers) {
  console.log("=== DISPLAYING CUSTOMERS ===");
  console.log("Customers to display:", customers);

  const tbody = document.getElementById("customerTableBody");

  if (!tbody) {
    console.error("ERROR: customerTableBody element not found!");
    return;
  }

  if (customers.length === 0) {
    console.log("No customers found");
    tbody.innerHTML =
      '<tr><td colspan="5" style="text-align:center;">No customers found</td></tr>';
    return;
  }

  const html = customers
    .map((customer) => {
      return `
            <tr>
                <td>${customer.full_name || "-"}</td>
                <td>${customer.email || "-"}</td>
                <td>${customer.phone || "-"}</td>
                <td>${
                  customer.gender ? capitalizeFirst(customer.gender) : "-"
                }</td>
                <td>${formatDate(customer.created_at)}</td>
            </tr>
        `;
    })
    .join("");

  console.log("Generated HTML length:", html.length);
  tbody.innerHTML = html;
  console.log(
    "✓ Table updated successfully with",
    customers.length,
    "customers"
  );
}

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

  // Add previous button
  if (currentPage > 1) {
    html += `<button class="pagination-btn" onclick="changePage(${
      currentPage - 1
    })">←</button>`;
  }

  // Add page numbers
  for (let i = 1; i <= pagination.pages; i++) {
    html += `<button class="pagination-btn ${
      i === currentPage ? "active" : ""
    }" onclick="changePage(${i})">${i}</button>`;
  }

  // Add next button
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
  loadCustomers();
}

// Capitalize first letter
function capitalizeFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
}

// Format date
function formatDate(dateStr) {
  if (!dateStr) return "-";
  try {
    const date = new Date(dateStr);
    return date.toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  } catch (e) {
    console.error("Error formatting date:", e);
    return dateStr;
  }
}

// Show alert
function showAlert(type, message) {
  const container = document.getElementById("alertContainer");
  if (!container) {
    console.error("alertContainer not found!");
    return;
  }

  const alert = document.createElement("div");
  alert.className = `alert alert-${type}`;
  alert.textContent = message;
  alert.style.display = "block";
  container.appendChild(alert);

  setTimeout(() => {
    alert.remove();
  }, 5000);
}

// Test function - untuk debugging di console
function testTable() {
  console.log("=== TESTING TABLE ELEMENTS ===");

  const tbody = document.getElementById("customerTableBody");
  console.log("tbody element:", tbody);

  const table = document.querySelector(".customer-table");
  console.log("table element:", table);

  const container = document.querySelector(".customer-table-container");
  console.log("container element:", container);

  if (tbody) {
    tbody.innerHTML = `
            <tr>
                <td>Test Name</td>
                <td>test@email.com</td>
                <td>08123456789</td>
                <td>Male</td>
                <td>21 Nov 2025</td>
            </tr>
        `;
    console.log("✓ Test data inserted into table");
  } else {
    console.error("✗ tbody element not found!");
  }
}

// Download Excel
function downloadTherapistExcel() {
    const params = new URLSearchParams({
        branch_id: ADMIN_BRANCH_ID,
        export: 'excel'
    });

    console.log('Downloading therapist Excel with params:', params.toString());
    
    // Open download in new window
    window.open(`api/export_customer_excel.php?${params}`, '_blank');
}