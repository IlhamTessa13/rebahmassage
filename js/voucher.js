// Voucher Management JavaScript with Bulk Generation
// File: js/voucher.js

// Global variables
let currentPage = 1;
let html5QrCode = null;
let currentQRData = null;

// ============================================
// NOTIFICATION MODAL FUNCTIONS
// ============================================

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
  document
    .getElementById("notificationOkBtn")
    .addEventListener("click", closeNotificationModal);
}

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

function closeNotificationModal() {
  const modal = document.getElementById("notificationModal");
  modal.classList.remove("show");
  setTimeout(() => {
    modal.style.display = "none";
  }, 300);
}

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
// DOCUMENT READY
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Voucher Management page loaded");

  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.querySelector(".main-content");

  if (toggleBtn && sidebar && mainContent) {
    const sidebarState = localStorage.getItem("sidebarState");
    if (sidebarState === "closed") {
      sidebar.classList.add("closed");
      mainContent.classList.add("expanded");
    }

    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("closed");
      mainContent.classList.toggle("expanded");

      if (sidebar.classList.contains("closed")) {
        localStorage.setItem("sidebarState", "closed");
      } else {
        localStorage.setItem("sidebarState", "open");
      }
    });
  }

  createNotificationModal();

  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const minDate = tomorrow.toISOString().split("T")[0];
  const expiredDateInput = document.getElementById("expiredDate");
  if (expiredDateInput) {
    expiredDateInput.min = minDate;
  }

  loadVouchers();
});

// ============================================
// LOAD VOUCHERS
// ============================================

function loadVouchers() {
  const entriesPerPage = document.getElementById("entriesPerPage").value;
  console.log(
    "Loading vouchers - Page:",
    currentPage,
    "Limit:",
    entriesPerPage
  );

  const params = new URLSearchParams({
    page: currentPage,
    limit: entriesPerPage,
    branch_id: ADMIN_BRANCH_ID,
  });

  fetch(`api/get_vouchers.php?${params}`)
    .then((r) => {
      if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
      return r.json();
    })
    .then((data) => {
      console.log("Vouchers loaded:", data);

      if (data.success) {
        displayVouchers(data.vouchers);
        updatePagination(data.pagination);
      } else {
        showNotification("error", data.message || "Failed to load vouchers");
        document.getElementById("voucherTableBody").innerHTML =
          '<tr><td colspan="6" style="text-align:center;">Error loading data</td></tr>';
      }
    })
    .catch((err) => {
      console.error("Fetch Error:", err);
      showNotification("error", "Failed to load vouchers: " + err.message);
      document.getElementById("voucherTableBody").innerHTML =
        '<tr><td colspan="6" style="text-align:center; color: red;">Error loading data</td></tr>';
    });
}

function displayVouchers(vouchers) {
  const tbody = document.getElementById("voucherTableBody");

  if (!tbody) {
    console.error("ERROR: voucherTableBody not found!");
    return;
  }

  if (vouchers.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" style="text-align:center;">No vouchers found</td></tr>';
    return;
  }

  const html = vouchers
    .map((voucher) => {
      const statusClass = `status-${voucher.status}`;
      const statusText =
        voucher.status.charAt(0).toUpperCase() + voucher.status.slice(1);

      let discountText = "-";
      if (voucher.discount_type) {
        if (voucher.discount_type === "percentage") {
          discountText = `${voucher.discount}%`;
        } else if (
          voucher.discount_type === "fixed" ||
          voucher.discount_type === "cashback"
        ) {
          discountText = `Rp ${Number(voucher.discount).toLocaleString(
            "id-ID"
          )}`;
        } else if (voucher.discount_type === "free_service") {
          discountText = "Free Service";
        }
      }

      return `
        <tr>
            <td><strong>${voucher.code}</strong></td>
            <td>${voucher.name}</td>
            <td>${discountText}</td>
            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            <td>${formatDate(voucher.expired_at)}</td>
            <td class="action-buttons">
                <button class="btn-action btn-view-qr" onclick='viewQRCode(${JSON.stringify(
                  voucher
                )})' title="View QR Code">
                    📱 QR Code
                </button>
                <button class="btn-action btn-delete" onclick="deleteVoucher(${
                        voucher.id
                      })" title="Delete">
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

  tbody.innerHTML = html;
}

// ============================================
// VOUCHER ACTIONS - BULK GENERATION
// ============================================

function toggleDiscountField() {
  const discountType = document.getElementById("discountType").value;
  const discountValueGroup = document.getElementById("discountValueGroup");
  const discountValueInput = document.getElementById("discountValue");

  if (discountType === "" || discountType === "free_service") {
    discountValueGroup.style.display = "none";
    discountValueInput.required = false;
    discountValueInput.value = "";
  } else {
    discountValueGroup.style.display = "block";
    discountValueInput.required = true;
  }
}

function toggleBulkFields() {
  const quantity = parseInt(document.getElementById("voucherQuantity").value);
  const codeInput = document.getElementById("voucherCode");
  const codeHelp = document.querySelector("#voucherCode + small");

  if (quantity > 1) {
    codeInput.placeholder =
      "e.g., rebah (will generate: rebah_1, rebah_2, ...)";
    codeHelp.textContent = `Will generate ${quantity} vouchers with suffix: _1, _2, ... _${quantity}`;
    codeHelp.style.color = "#28a745";
  } else {
    codeInput.placeholder = "e.g., REBAH2024-001";
    codeHelp.textContent = "Must be unique";
    codeHelp.style.color = "#666";
  }
}

function openAddModal() {
  console.log("Opening add voucher modal");
  document.getElementById("voucherForm").reset();
  document.getElementById("voucherQuantity").value = "1";
  toggleDiscountField();
  toggleBulkFields();

  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  document.getElementById("expiredDate").min = tomorrow
    .toISOString()
    .split("T")[0];

  document.getElementById("voucherModal").style.display = "block";
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
  if (modalId === "voucherModal") {
    document.getElementById("voucherForm").reset();
  }
}

function saveVoucher() {
  const form = document.getElementById("voucherForm");

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const formData = new FormData(form);
  const quantity = parseInt(formData.get("quantity"));
  const baseCode = formData.get("code").trim();

  const data = {
    code: baseCode,
    name: formData.get("name"),
    discount_type: formData.get("discount_type") || null,
    discount: formData.get("discount") || null,
    expired_at: formData.get("expired_at"),
    branch_id: ADMIN_BRANCH_ID,
    quantity: quantity, // NEW: Send quantity
  };

  console.log("Saving voucher(s):", data);

  // Show confirmation for bulk
  if (quantity > 1) {
    showConfirmation(
      "Create Bulk Vouchers",
      `Anda akan membuat ${quantity} voucher dengan kode: ${baseCode}_1, ${baseCode}_2, ... ${baseCode}_${quantity}. Lanjutkan?`
    ).then((confirmed) => {
      if (confirmed) {
        processSaveVoucher(data);
      }
    });
  } else {
    processSaveVoucher(data);
  }
}

function processSaveVoucher(data) {
  const btnSave = document.querySelector("#voucherModal .btn-save");
  const originalText = btnSave.innerHTML;
  btnSave.disabled = true;
  btnSave.innerHTML =
    '<span style="margin-right: 8px;">⏳</span> Processing...';

  fetch("api/create_voucher.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((r) => r.json())
    .then((response) => {
      btnSave.disabled = false;
      btnSave.innerHTML = originalText;

      console.log("Save response:", response);
      if (response.success) {
        const message =
          data.quantity > 1
            ? `${
                response.created_count || data.quantity
              } voucher berhasil ditambahkan!`
            : `Voucher "${data.code}" berhasil ditambahkan!`;

        showNotification("success", message);
        closeModal("voucherModal");
        loadVouchers();

        // Show QR for single voucher
        if (data.quantity === 1 && response.voucher) {
          setTimeout(() => {
            viewQRCode(response.voucher);
          }, 500);
        }
      } else {
        showNotification(
          "error",
          response.message || "Gagal menambahkan voucher"
        );
      }
    })
    .catch((err) => {
      btnSave.disabled = false;
      btnSave.innerHTML = originalText;
      console.error("Error saving voucher:", err);
      showNotification("error", "Terjadi kesalahan: " + err.message);
    });
}

function viewQRCode(voucher) {
  console.log("Viewing QR Code for:", voucher);

  currentQRData = voucher;

  const container = document.getElementById("qrCodeContainer");
  container.innerHTML = "";

  new QRCode(container, {
    text: voucher.code,
    width: 256,
    height: 256,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H,
  });

  document.getElementById("qrCodeText").textContent = voucher.code;
  document.getElementById("qrVoucherName").textContent = voucher.name;

  document.getElementById("qrModal").style.display = "block";
}

function downloadQRCode() {
  if (!currentQRData) return;

  const canvas = document.querySelector("#qrCodeContainer canvas");
  if (!canvas) {
    showNotification("error", "QR Code tidak ditemukan");
    return;
  }

  const link = document.createElement("a");
  link.download = `voucher_${currentQRData.code}.png`;
  link.href = canvas.toDataURL();
  link.click();

  showNotification("success", "QR Code berhasil diunduh!");
}

function deleteVoucher(voucherId) {
  showConfirmation(
    "Hapus Voucher",
    "Apakah Anda yakin ingin menghapus voucher ini?"
  ).then((confirmed) => {
    if (!confirmed) return;

    fetch("api/delete_voucher.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ voucher_id: voucherId }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          showNotification("success", "Voucher berhasil dihapus!");
          loadVouchers();
        } else {
          showNotification("error", data.message || "Gagal menghapus voucher");
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("error", "Terjadi kesalahan: " + err.message);
      });
  });
}

// ============================================
// CLAIM VOUCHER FUNCTIONS (unchanged)
// ============================================

function openClaimModal() {
  document.getElementById("claimMethodSelection").style.display = "block";
  document.getElementById("claimManualInput").style.display = "none";
  document.getElementById("claimScanInput").style.display = "none";
  document.getElementById("claimModal").style.display = "block";
}

function closeClaimModal() {
  if (html5QrCode && html5QrCode.isScanning) {
    html5QrCode.stop();
  }
  document.getElementById("claimModal").style.display = "none";
}

function selectClaimMethod(method) {
  document.getElementById("claimMethodSelection").style.display = "none";

  if (method === "manual") {
    document.getElementById("claimManualInput").style.display = "block";
    document.getElementById("claimCode").focus();
  } else if (method === "scan") {
    document.getElementById("claimScanInput").style.display = "block";
    startScanner();
  }
}

function backToMethodSelection() {
  document.getElementById("claimManualInput").style.display = "none";
  document.getElementById("claimScanInput").style.display = "none";
  document.getElementById("claimMethodSelection").style.display = "block";
  document.getElementById("claimForm").reset();
}

function startScanner() {
  html5QrCode = new Html5Qrcode("qrReader");

  html5QrCode
    .start(
      { facingMode: "environment" },
      { fps: 10, qrbox: { width: 250, height: 250 } },
      (decodedText) => {
        html5QrCode.stop();
        processClaimVoucher(decodedText);
      },
      (errorMessage) => {}
    )
    .catch((err) => {
      console.error("Scanner error:", err);
      showNotification("error", "Gagal memulai kamera");
      backToMethodSelection();
    });
}

function stopScanner() {
  if (html5QrCode && html5QrCode.isScanning) {
    html5QrCode.stop().then(() => {
      backToMethodSelection();
    });
  } else {
    backToMethodSelection();
  }
}

function claimVoucher() {
  const code = document.getElementById("claimCode").value.trim();
  if (!code) {
    showNotification("warning", "Silakan masukkan kode voucher");
    return;
  }
  processClaimVoucher(code);
}

function processClaimVoucher(code) {
  fetch("api/claim_voucher.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ code: code, branch_id: ADMIN_BRANCH_ID }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        showNotification("success", `Voucher "${code}" berhasil digunakan!`);
        closeClaimModal();
        loadVouchers();
      } else {
        showNotification("error", data.message || "Gagal menggunakan voucher");
        document.getElementById("claimCode").value = "";
        document.getElementById("claimCode").focus();
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Terjadi kesalahan: " + err.message);
    });
}

// ============================================
// PAGINATION & UTILITIES
// ============================================

function updatePagination(pagination) {
  const info = document.getElementById("paginationInfo");
  if (info) {
    info.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total} entries`;
  }

  const controls = document.getElementById("paginationControls");
  if (!controls) return;

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
  loadVouchers();
}

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
    return dateStr;
  }
}

window.onclick = function (event) {
  if (
    event.target.id === "voucherModal" ||
    event.target.id === "qrModal" ||
    event.target.id === "claimModal"
  ) {
    if (event.target.id === "claimModal") {
      stopScanner();
    }
    event.target.style.display = "none";
  }
};

// Download Excel
function downloadVoucherExcel() {
    const params = new URLSearchParams({
        branch_id: ADMIN_BRANCH_ID,
        export: 'excel'
    });

    console.log('Downloading voucher Excel with params:', params.toString());
    window.open(`api/export_voucher_excel.php?${params}`, '_blank');
}