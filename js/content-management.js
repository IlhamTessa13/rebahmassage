// Menu Services Management JavaScript with Modal Notifications
let editingServiceId = null;
let deletingServiceId = null;

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
// DOCUMENT READY
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Menu Services Management loaded");

  // Create notification modal
  createNotificationModal();

  // Load services
  loadServices();

  // Setup sidebar toggle
  setupSidebarToggle();

  // Setup image preview
  setupImagePreview();
});

// ============================================
// SETUP FUNCTIONS
// ============================================

function setupSidebarToggle() {
  const toggleBtn = document.getElementById("toggleBtn");
  const sidebar =
    document.getElementById("sidebar") || document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (!toggleBtn || !sidebar || !mainContent) {
    console.warn("Sidebar elements not found");
    return;
  }

  const sidebarState = localStorage.getItem("sidebarState");
  if (sidebarState === "closed") {
    sidebar.classList.add("collapsed");
    sidebar.classList.add("closed");
    mainContent.classList.add("expanded");
  }

  toggleBtn.addEventListener("click", function () {
    sidebar.classList.toggle("collapsed");
    sidebar.classList.toggle("closed");
    mainContent.classList.toggle("expanded");

    if (
      sidebar.classList.contains("collapsed") ||
      sidebar.classList.contains("closed")
    ) {
      localStorage.setItem("sidebarState", "closed");
    } else {
      localStorage.setItem("sidebarState", "open");
    }
  });
}

function setupImagePreview() {
  const imageInput = document.getElementById("serviceImage");
  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          showNotification(
            "error",
            "Ukuran gambar harus kurang dari 5MB",
            "File Terlalu Besar"
          );
          e.target.value = "";
          return;
        }

        // Validate file type
        if (!file.type.match("image.*")) {
          showNotification(
            "error",
            "Silakan pilih file gambar (JPG, PNG)",
            "Format File Salah"
          );
          e.target.value = "";
          return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
          document.getElementById("previewImage").src = e.target.result;
          document.getElementById("imagePreview").style.display = "block";
        };
        reader.readAsDataURL(file);
      } else {
        document.getElementById("imagePreview").style.display = "none";
      }
    });
  }
}

// ============================================
// LOAD & DISPLAY SERVICES
// ============================================

function loadServices() {
  fetch("api/get_menu_services.php")
    .then((r) => {
      if (!r.ok) {
        throw new Error(`HTTP error! status: ${r.status}`);
      }
      return r.json();
    })
    .then((data) => {
      console.log("Services data:", data);
      if (data.success) {
        displayServices(data.services);
      } else {
        showNotification("error", data.message || "Gagal memuat layanan");
        showError(data.message || "Failed to load services");
      }
    })
    .catch((err) => {
      console.error("Error loading services:", err);
      showNotification("error", "Gagal memuat layanan: " + err.message);
      showError("Failed to load services: " + err.message);
    });
}

function displayServices(services) {
  const container = document.getElementById("servicesCardsContainer");

  if (services.length === 0) {
    container.innerHTML = `
      <div class="no-services">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p>No services found</p>
        <button class="btn-add-new" onclick="openAddModal()">Add Your First Service</button>
      </div>
    `;
    return;
  }

  const timestamp = new Date().getTime();

  container.innerHTML = services
    .map(
      (service) => `
        <div class="service-card" data-service-id="${service.id}">
            <div class="service-card-image">
                <img src="/php/public/${service.image}?v=${timestamp}" 
                     alt="${escapeHtml(service.name)}"
                     onerror="this.src='/php/public/placeholder.png'">
            </div>
            <div class="service-card-content">
                <h3 class="service-card-title">${escapeHtml(service.name)}</h3>
                <div class="service-card-meta">
                    <span class="service-id">Service #${service.id}</span>
                    <span class="service-date">Added: ${formatDate(
                      service.created_at
                    )}</span>
                </div>
                <div class="service-actions">
                    <button class="btn-edit" onclick="openEditModal(${
                      service.id
                    })">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit
                    </button>
                    <button class="btn-delete-card" onclick="openDeleteModal(${
                      service.id
                    }, '${escapeHtml(service.name)}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    `
    )
    .join("");
}

function showError(message) {
  const container = document.getElementById("servicesCardsContainer");
  container.innerHTML = `<div class="error-message">${message}</div>`;
}

// ============================================
// ADD MODAL
// ============================================

function openAddModal() {
  editingServiceId = null;
  document.getElementById("formMode").value = "add";
  document.getElementById("modalTitle").textContent = "Add New Service";
  document.getElementById("serviceForm").reset();
  document.getElementById("serviceId").value = "";
  document.getElementById("serviceImage").required = true;
  document.getElementById("currentImageGroup").style.display = "none";
  document.getElementById("imagePreview").style.display = "none";
  document.getElementById("serviceModal").style.display = "block";
  document.body.style.overflow = "hidden";
}

// ============================================
// EDIT MODAL
// ============================================

function openEditModal(serviceId) {
  editingServiceId = serviceId;
  document.getElementById("formMode").value = "edit";
  document.getElementById("modalTitle").textContent = "Edit Service";

  fetch(`api/get_service_detail.php?id=${serviceId}`)
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        const service = data.service;

        document.getElementById("serviceId").value = service.id;
        document.getElementById("serviceName").value = service.name;

        // Show current image with cache busting
        const timestamp = new Date().getTime();
        const currentImg = document.getElementById("currentImagePreview");
        currentImg.src = `/php/public/${service.image}?v=${timestamp}`;
        document.getElementById("currentImageGroup").style.display = "block";

        // Make image optional for edit
        document.getElementById("serviceImage").required = false;

        // Clear file input and preview
        document.getElementById("serviceImage").value = "";
        document.getElementById("imagePreview").style.display = "none";

        document.getElementById("serviceModal").style.display = "block";
        document.body.style.overflow = "hidden";
      } else {
        showNotification(
          "error",
          data.message || "Gagal memuat detail layanan"
        );
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification("error", "Gagal memuat detail layanan");
    });
}

function closeServiceModal() {
  document.getElementById("serviceModal").style.display = "none";
  document.getElementById("serviceForm").reset();
  document.body.style.overflow = "auto";
  editingServiceId = null;
}

// ============================================
// SAVE SERVICE WITH MODAL NOTIFICATIONS
// ============================================

function saveService() {
  const form = document.getElementById("serviceForm");

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const name = document.getElementById("serviceName").value.trim();
  const mode = document.getElementById("formMode").value;
  const fileInput = document.getElementById("serviceImage");

  if (!name) {
    showNotification(
      "warning",
      "Nama layanan harus diisi",
      "Data Tidak Lengkap"
    );
    return;
  }

  // Check image for add mode
  if (mode === "add" && !fileInput.files[0]) {
    showNotification(
      "warning",
      "Silakan pilih gambar untuk layanan",
      "Gambar Diperlukan"
    );
    return;
  }

  const formData = new FormData(form);

  const saveBtn = document.querySelector(".btn-save");
  const originalText = saveBtn.textContent;
  saveBtn.textContent = "Saving...";
  saveBtn.disabled = true;

  const endpoint =
    mode === "add" ? "api/add_menu_service.php" : "api/update_menu_service.php";

  fetch(endpoint, {
    method: "POST",
    body: formData,
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        const successMessage =
          mode === "add"
            ? "Layanan berhasil ditambahkan!"
            : "Layanan berhasil diperbarui!";

        showNotification("success", successMessage);
        closeServiceModal();

        setTimeout(() => {
          loadServices();
        }, 300);
      } else {
        // Handle specific error messages
        let errorMessage = data.message || "Gagal menyimpan layanan";

        if (
          errorMessage.toLowerCase().includes("image") &&
          errorMessage.toLowerCase().includes("size")
        ) {
          showNotification(
            "error",
            "Ukuran gambar terlalu besar. Maksimal 5MB",
            "File Terlalu Besar"
          );
        } else if (
          errorMessage.toLowerCase().includes("image") &&
          errorMessage.toLowerCase().includes("type")
        ) {
          showNotification(
            "error",
            "Format gambar tidak valid. Gunakan JPG atau PNG",
            "Format File Salah"
          );
        } else {
          showNotification("error", errorMessage);
        }
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification(
        "error",
        "Terjadi kesalahan saat menyimpan: " + err.message
      );
    })
    .finally(() => {
      saveBtn.textContent = originalText;
      saveBtn.disabled = false;
    });
}

// ============================================
// DELETE MODAL WITH CONFIRMATION
// ============================================

function openDeleteModal(serviceId, serviceName) {
  deletingServiceId = serviceId;

  // Show confirmation modal instead of old delete modal
  showConfirmation(
    "Hapus Layanan",
    `Apakah Anda yakin ingin menghapus layanan "${serviceName}"?\n\nTindakan ini tidak dapat dibatalkan.`
  ).then((confirmed) => {
    if (confirmed) {
      confirmDelete();
    } else {
      deletingServiceId = null;
    }
  });
}

function closeDeleteModal() {
  // Legacy function - keeping for compatibility
  deletingServiceId = null;
}

function confirmDelete() {
  if (!deletingServiceId) return;

  fetch("api/delete_menu_service.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ service_id: deletingServiceId }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        showNotification("success", "Layanan berhasil dihapus!");
        deletingServiceId = null;

        setTimeout(() => {
          loadServices();
        }, 300);
      } else {
        showNotification("error", data.message || "Gagal menghapus layanan");
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      showNotification(
        "error",
        "Terjadi kesalahan saat menghapus: " + err.message
      );
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  const options = { year: "numeric", month: "short", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

// Close modal when clicking outside (only for service modal, not notifications)
window.onclick = function (event) {
  const serviceModal = document.getElementById("serviceModal");

  if (event.target === serviceModal) {
    closeServiceModal();
  }
};
