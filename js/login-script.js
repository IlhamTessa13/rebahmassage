document.addEventListener("DOMContentLoaded", function () {
  // Wrap form inputs with icon containers
  const form = document.querySelector("form.card");

  if (form) {
    const emailInput = form.querySelector('input[type="email"]');
    const passwordInput = form.querySelector('input[type="password"]');
    const submitButton = form.querySelector('button[type="submit"]');

    // Add input wrappers and icons
    if (emailInput) {
      wrapInput(emailInput, "📧");
    }

    if (passwordInput) {
      const wrapper = wrapInput(passwordInput, "🔒");
      addPasswordToggle(passwordInput, wrapper);
    }

    // Add input validation effects
    addInputValidation(emailInput);
    addInputValidation(passwordInput);

    // Add form submission handler
    if (submitButton) {
      form.addEventListener("submit", function () {
        submitButton.classList.add("loading");
        submitButton.textContent = "Memproses...";
      });
    }

    // Animate error message if exists
    const errorMsg = document.querySelector('.help[style*="color:#dc2626"]');
    if (errorMsg) {
      errorMsg.classList.add("error");
    }
  }

  // Wrap container
  wrapContainer();
});

// Function to wrap input with icon
function wrapInput(input, icon) {
  const wrapper = document.createElement("div");
  wrapper.className = "input-wrapper";

  const label = input.previousElementSibling;
  if (label && label.tagName === "LABEL") {
    label.parentNode.insertBefore(wrapper, label.nextSibling);
  } else {
    input.parentNode.insertBefore(wrapper, input);
  }

  wrapper.appendChild(input);

  const iconSpan = document.createElement("span");
  iconSpan.className = "input-icon";
  iconSpan.textContent = icon;
  wrapper.appendChild(iconSpan);

  return wrapper;
}

// Function to add password toggle
function addPasswordToggle(input, wrapper) {
  const toggle = document.createElement("button");
  toggle.type = "button";
  toggle.className = "password-toggle";
  toggle.innerHTML = "👁️";
  toggle.setAttribute("aria-label", "Toggle password visibility");
  toggle.dataset.visible = "false"; // Track visibility state

  // Remove the emoji icon, add toggle instead
  const existingIcon = wrapper.querySelector(".input-icon");
  if (existingIcon) {
    existingIcon.remove();
  }

  wrapper.appendChild(toggle);

  toggle.addEventListener("click", function (e) {
    e.preventDefault(); // Prevent form submission

    if (toggle.dataset.visible === "false") {
      // Show password
      input.type = "text";
      toggle.innerHTML = "🙈";
      toggle.dataset.visible = "true";
    } else {
      // Hide password
      input.type = "password";
      toggle.innerHTML = "👁️";
      toggle.dataset.visible = "false";
    }
    input.focus();
  });
}

// Function to add input validation effects
function addInputValidation(input) {
  if (!input) return;

  input.addEventListener("blur", function () {
    if (this.value.trim() === "") {
      this.style.borderColor = "#fc8181";
    }
  });

  input.addEventListener("input", function () {
    if (this.value.trim() !== "") {
      this.style.borderColor = "#e2e8f0";
    }
  });

  // Add floating animation on focus
  input.addEventListener("focus", function () {
    const label = this.closest(".input-wrapper").previousElementSibling;
    if (label && label.tagName === "LABEL") {
      label.style.color = "#667eea";
    }
  });

  input.addEventListener("blur", function () {
    const label = this.closest(".input-wrapper").previousElementSibling;
    if (label && label.tagName === "LABEL") {
      label.style.color = "#4a5568";
    }
  });
}

// Function to wrap entire content in container
function wrapContainer() {
  const h1 = document.querySelector("h1");
  const form = document.querySelector("form.card");
  const helps = document.querySelectorAll(".help");

  if (h1 && form) {
    const container = document.createElement("div");
    container.className = "login-container";

    h1.parentNode.insertBefore(container, h1);
    container.appendChild(h1);
    container.appendChild(form);

    helps.forEach((help) => {
      container.appendChild(help);
    });
  }
}

// Add keyboard navigation enhancement
document.addEventListener("keydown", function (e) {
  if (e.key === "Enter") {
    const focusedElement = document.activeElement;
    if (focusedElement.tagName === "INPUT") {
      const form = focusedElement.closest("form");
      if (form) {
        const inputs = Array.from(form.querySelectorAll("input"));
        const currentIndex = inputs.indexOf(focusedElement);

        if (currentIndex < inputs.length - 1) {
          e.preventDefault();
          inputs[currentIndex + 1].focus();
        }
      }
    }
  }
});

// Add particle effect on click (optional eye-candy)
document.addEventListener("click", function (e) {
  createRipple(e.clientX, e.clientY);
});

function createRipple(x, y) {
  const ripple = document.createElement("div");
  ripple.style.position = "fixed";
  ripple.style.width = "10px";
  ripple.style.height = "10px";
  ripple.style.borderRadius = "50%";
  ripple.style.background = "rgba(255, 255, 255, 0.5)";
  ripple.style.left = x + "px";
  ripple.style.top = y + "px";
  ripple.style.pointerEvents = "none";
  ripple.style.transform = "translate(-50%, -50%)";
  ripple.style.animation = "ripple 0.6s ease-out";
  ripple.style.zIndex = "9999";

  document.body.appendChild(ripple);

  setTimeout(() => ripple.remove(), 600);
}

// Add CSS for ripple animation
const style = document.createElement("style");
style.textContent = `
  @keyframes ripple {
    to {
      transform: translate(-50%, -50%) scale(10);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
