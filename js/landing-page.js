document.addEventListener("DOMContentLoaded", function () {
  console.log("Landing page loaded");

  // Mobile Navigation Toggle
  const hamburger = document.getElementById("hamburgerBtn");
  const navContainer = document.getElementById("navContainer");
  const navClose = document.getElementById("navClose");
  const navLinks = document.querySelectorAll(".nav-link");

  function openNav() {
    navContainer.classList.add("active");
    hamburger.classList.add("hide");
    document.body.classList.add("nav-active");
    document.body.style.overflow = "hidden";
  }

  function closeNav() {
    navContainer.classList.remove("active");
    hamburger.classList.remove("hide");
    document.body.classList.remove("nav-active");
    document.body.style.overflow = "";
  }

  if (hamburger) {
    hamburger.addEventListener("click", openNav);
  }

  if (navClose) {
    navClose.addEventListener("click", closeNav);
  }

  // Click pada body::after (blur overlay) untuk close nav
  document.addEventListener("click", function (e) {
    if (
      document.body.classList.contains("nav-active") &&
      !navContainer.contains(e.target) &&
      !hamburger.contains(e.target)
    ) {
      closeNav();
    }
  });

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

  // Navbar active link on scroll
  const sections = document.querySelectorAll("section[id]");

  window.addEventListener("scroll", function () {
    let current = "";

    sections.forEach((section) => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.clientHeight;
      if (pageYOffset >= sectionTop - 100) {
        current = section.getAttribute("id");
      }
    });

    navLinks.forEach((link) => {
      link.classList.remove("active");
      if (link.getAttribute("href") === "#" + current) {
        link.classList.add("active");
      }
    });
  });

  // Smooth scroll for navigation links
  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      if (href.startsWith("#")) {
        e.preventDefault();
        const targetId = href.substring(1);
        const targetSection = document.getElementById(targetId);

        if (targetSection) {
          // Offset untuk navbar fixed di mobile
          const navbarHeight = window.innerWidth <= 768 ? 70 : 80;
          const offsetTop = targetSection.offsetTop - navbarHeight;

          window.scrollTo({
            top: offsetTop,
            behavior: "smooth",
          });
        }
      }
    });
  });

  // Load Services from API
  loadServices();

  // Add hover effect to map cards
  const mapCards = document.querySelectorAll(".map-card");
  mapCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
    });
  });
});

// Services Carousel Functionality
let currentSlide = 0;
let servicesData = [];
let autoSlideInterval;
let isTransitioning = false;

function loadServices() {
  console.log("Loading services from API...");

  fetch("/php/api/get_menu_services.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Services loaded:", data);
      if (data.success && data.services.length > 0) {
        servicesData = data.services;
        renderServices(data.services);
        initServicesCarousel();
      } else {
        showServicesError("No services available");
      }
    })
    .catch((error) => {
      console.error("Error loading services:", error);
      showServicesError("Failed to load services. Please refresh the page.");
    });
}

function renderServices(services) {
  const track = document.getElementById("servicesTrack");
  track.innerHTML = "";

  const allCards = [];

  services.forEach((service) => {
    allCards.push(createServiceCard(service, true));
  });

  services.forEach((service) => {
    allCards.push(createServiceCard(service, false));
  });

  services.forEach((service) => {
    allCards.push(createServiceCard(service, true));
  });

  allCards.forEach((card) => track.appendChild(card));
}

function createServiceCard(service, isClone = false) {
  const card = document.createElement("div");
  card.className = "service-card";
  if (isClone) card.dataset.clone = "true";

  card.innerHTML = `
    <div class="service-image-wrapper">
      <img src="/php/public/${service.image}" 
           alt="${service.name}" 
           class="service-image"
           onerror="this.src='/php/public/placeholder.png'">
      <div class="service-overlay">
        <h3 class="service-name">${service.name}</h3>
      </div>
    </div>
  `;

  return card;
}

function showServicesError(message) {
  const track = document.getElementById("servicesTrack");
  track.innerHTML = `
    <div class="service-error">
      <p>${message}</p>
    </div>
  `;

  const prevBtn = document.querySelector(".carousel-prev");
  const nextBtn = document.querySelector(".carousel-next");
  if (prevBtn) prevBtn.style.display = "none";
  if (nextBtn) nextBtn.style.display = "none";
}

function initServicesCarousel() {
  if (servicesData.length === 0) {
    showServicesError("No services available");
    return;
  }

  currentSlide = servicesData.length;
  updateCarousel(false);

  createCarouselDots();
  startAutoSlide();

  const track = document.getElementById("servicesTrack");
  track.addEventListener("mouseenter", stopAutoSlide);
  track.addEventListener("mouseleave", startAutoSlide);
}

function slideServices(direction) {
  if (isTransitioning || servicesData.length === 0) return;

  isTransitioning = true;
  currentSlide += direction;
  updateCarousel(true);

  setTimeout(() => {
    if (currentSlide >= servicesData.length * 2) {
      currentSlide = servicesData.length;
      updateCarousel(false);
    } else if (currentSlide < servicesData.length) {
      currentSlide = servicesData.length * 2 - 1;
      updateCarousel(false);
    }

    isTransitioning = false;
  }, 500);

  updateDots();
  stopAutoSlide();
  startAutoSlide();
}

function updateCarousel(animate = true) {
  const track = document.getElementById("servicesTrack");
  const cards = track.querySelectorAll(".service-card");

  if (cards.length === 0) return;

  const cardElement = cards[0];
  const cardWidth = cardElement.offsetWidth;

  let gap = 24;
  if (window.innerWidth <= 768) {
    gap = 16;
  }

  const moveAmount = (cardWidth + gap) * currentSlide;

  if (!animate) {
    track.style.transition = "none";
  } else {
    track.style.transition = "transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
  }

  track.style.transform = `translateX(-${moveAmount}px)`;

  if (!animate) {
    setTimeout(() => {
      track.style.transition = "transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
    }, 50);
  }
}

function createCarouselDots() {
  const dotsContainer = document.getElementById("carouselDots");
  dotsContainer.innerHTML = "";

  for (let i = 0; i < servicesData.length; i++) {
    const dot = document.createElement("button");
    dot.className = "carousel-dot";
    dot.onclick = () => goToSlide(i);
    dotsContainer.appendChild(dot);
  }

  updateDots();
}

function updateDots() {
  const dots = document.querySelectorAll(".carousel-dot");
  const actualIndex =
    (currentSlide - servicesData.length + servicesData.length) %
    servicesData.length;

  dots.forEach((dot, index) => {
    if (index === actualIndex) {
      dot.classList.add("active");
    } else {
      dot.classList.remove("active");
    }
  });
}

function goToSlide(index) {
  if (isTransitioning) return;

  currentSlide = servicesData.length + index;
  updateCarousel(true);
  updateDots();

  stopAutoSlide();
  startAutoSlide();
}

function startAutoSlide() {
  autoSlideInterval = setInterval(() => {
    slideServices(1);
  }, 4000);
}

function stopAutoSlide() {
  if (autoSlideInterval) {
    clearInterval(autoSlideInterval);
  }
}

let resizeTimer;
window.addEventListener("resize", () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(() => {
    updateCarousel(false);
  }, 250);
});
