// Auth Script with Modal Notifications and Elegant Icons

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
// DOCUMENT READY
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  // Create notification modal
  createNotificationModal();

  const authContainer = document.querySelector(".auth-container");
  const slidingPanel = document.querySelector(".sliding-panel");
  const signUpLink = document.getElementById("signUpLink");
  const signInLink = document.getElementById("signInLink");
  const backToLogin = document.getElementById("backToLogin");

  // Check if mobile/tablet
  function isMobile() {
    return window.innerWidth <= 768;
  }

  // Function to switch to register
  function showRegister() {
    authContainer.classList.add("register-mode");
    if (!isMobile() && slidingPanel) {
      slidingPanel.classList.add("slide-right");
    }
    window.scrollTo(0, 0);
  }

  // Function to switch to login
  function showLogin() {
    authContainer.classList.remove("register-mode");
    if (!isMobile() && slidingPanel) {
      slidingPanel.classList.remove("slide-right");
    }
    window.scrollTo(0, 0);
  }

  // Toggle between login and register
  if (signUpLink) {
    signUpLink.addEventListener("click", function (e) {
      e.preventDefault();
      showRegister();
    });
  }

  if (signInLink) {
    signInLink.addEventListener("click", function (e) {
      e.preventDefault();
      showLogin();
    });
  }

  if (backToLogin) {
    backToLogin.addEventListener("click", function (e) {
      e.preventDefault();
      showLogin();
    });
  }

  // Add password toggles
  const passwordInputs = document.querySelectorAll('input[type="password"]');
  passwordInputs.forEach((input) => {
    addPasswordToggle(input);
  });

  // Add form validation
  const forms = document.querySelectorAll(".auth-form");
  forms.forEach((form) => {
    const inputs = form.querySelectorAll("input[required]");
    inputs.forEach((input) => {
      addInputValidation(input);
    });

    // Handle form submission
    form.addEventListener("submit", function () {
      const submitBtn = form.querySelector(".submit-btn");
      if (submitBtn) {
        submitBtn.classList.add("loading");
        submitBtn.textContent = "Processing...";
      }
    });
  });

  // Check PHP error/success messages and show modal notifications
  const errorMessages = document.querySelectorAll(".error-message");
  const successMessages = document.querySelectorAll(".success-message");

  errorMessages.forEach((errorMsg) => {
    const message = errorMsg.textContent.trim();
    if (message) {
      showNotification("error", message);
      // Hide the original error message
      errorMsg.style.display = "none";
    }
  });

  successMessages.forEach((successMsg) => {
    const message = successMsg.textContent.trim();
    if (message) {
      showNotification("success", message);
      // Hide the original success message
      successMsg.style.display = "none";
    }
  });

  // Check if there's an error in register form (show register view)
  const errorInRegister = document.querySelector(
    ".register-container .error-message"
  );
  if (errorInRegister && errorInRegister.textContent.trim()) {
    showRegister();
  }

  // Handle window resize - reset animation state
  let resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      // Force correct state based on current mode
      const isRegisterMode = authContainer.classList.contains("register-mode");

      if (slidingPanel) {
        if (isMobile()) {
          // Mobile: no sliding panel
          slidingPanel.style.transition = "none";
          slidingPanel.classList.remove("slide-right");
        } else {
          // Desktop: restore transition
          slidingPanel.style.transition = "";
          if (isRegisterMode) {
            slidingPanel.classList.add("slide-right");
          } else {
            slidingPanel.classList.remove("slide-right");
          }
        }
      }
    }, 250);
  });
});

// ============================================
// PASSWORD TOGGLE - ELEGANT SVG ICONS
// ============================================

// Function to add password toggle with elegant SVG icons
function addPasswordToggle(input) {
  const wrapper = input.parentElement;

  if (wrapper.classList.contains("input-wrapper")) {
    // Check if toggle already exists
    if (wrapper.querySelector(".password-toggle")) {
      return;
    }

    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "password-toggle";
    toggle.setAttribute("aria-label", "Toggle password visibility");
    toggle.dataset.visible = "false";

    // Eye icon (password hidden)
    const eyeIcon = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12" cy="12" r="3"></circle>
      </svg>
    `;

    // Eye-off icon (password visible)
    const eyeOffIcon = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
        <line x1="1" y1="1" x2="23" y2="23"></line>
      </svg>
    `;

    toggle.innerHTML = eyeIcon;
    wrapper.appendChild(toggle);

    toggle.addEventListener("click", function (e) {
      e.preventDefault();

      if (toggle.dataset.visible === "false") {
        input.type = "text";
        toggle.innerHTML = eyeOffIcon;
        toggle.dataset.visible = "true";
      } else {
        input.type = "password";
        toggle.innerHTML = eyeIcon;
        toggle.dataset.visible = "false";
      }
      input.focus();
    });
  }
}

// ============================================
// INPUT VALIDATION
// ============================================

// Function to add input validation effects
function addInputValidation(input) {
  input.addEventListener("blur", function () {
    if (this.value.trim() === "" && this.hasAttribute("required")) {
      this.style.borderColor = "#fc8181";
    }
  });

  input.addEventListener("input", function () {
    if (this.value.trim() !== "") {
      this.style.borderColor = "#e2e8f0";
    }
  });

  // Email validation
  if (input.type === "email") {
    input.addEventListener("blur", function () {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (this.value && !emailRegex.test(this.value)) {
        this.style.borderColor = "#fc8181";
      }
    });
  }

  // Phone validation (Indonesian format)
  if (input.type === "tel" || input.name === "phone") {
    input.addEventListener("input", function () {
      this.value = this.value.replace(/[^0-9+]/g, "");
    });
  }
}

// ============================================
// KEYBOARD NAVIGATION
// ============================================

// Add keyboard navigation
document.addEventListener("keydown", function (e) {
  if (e.key === "Enter") {
    const focusedElement = document.activeElement;
    if (
      focusedElement.tagName === "INPUT" &&
      focusedElement.type !== "submit" &&
      focusedElement.type !== "radio"
    ) {
      const form = focusedElement.closest("form");
      if (form) {
        const inputs = Array.from(
          form.querySelectorAll(
            'input:not([type="radio"]):not([type="hidden"]), select'
          )
        );
        const currentIndex = inputs.indexOf(focusedElement);

        if (currentIndex < inputs.length - 1) {
          e.preventDefault();
          inputs[currentIndex + 1].focus();
        }
      }
    }
  }
});
