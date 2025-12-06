document.addEventListener("DOMContentLoaded", function () {
  console.log("Landing page loaded");

  // Navbar active link on scroll
  const sections = document.querySelectorAll("section[id]");
  const navLinks = document.querySelectorAll(".nav-link");

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

      // Only prevent default for anchor links (starting with #)
      if (href.startsWith("#")) {
        e.preventDefault();
        const targetId = href.substring(1);
        const targetSection = document.getElementById(targetId);

        if (targetSection) {
          const offsetTop = targetSection.offsetTop - 80;
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

// Load services from API
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

// Render services HTML
function renderServices(services) {
  const track = document.getElementById("servicesTrack");
  track.innerHTML = "";

  // Create all cards (original + clones for infinite loop)
  const allCards = [];

  // Clone before (for seamless loop)
  services.forEach((service) => {
    allCards.push(createServiceCard(service, true));
  });

  // Original cards
  services.forEach((service) => {
    allCards.push(createServiceCard(service, false));
  });

  // Clone after (for seamless loop)
  services.forEach((service) => {
    allCards.push(createServiceCard(service, true));
  });

  // Append all cards
  allCards.forEach((card) => track.appendChild(card));
}

// Create service card element
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

// Show error message
function showServicesError(message) {
  const track = document.getElementById("servicesTrack");
  track.innerHTML = `
    <div class="service-error">
      <p>${message}</p>
    </div>
  `;

  // Hide navigation buttons
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

  // Start at the first original card (after clones)
  currentSlide = servicesData.length;
  updateCarousel(false);

  // Create dots
  createCarouselDots();

  // Start auto slide
  startAutoSlide();

  // Pause auto slide on hover
  const track = document.getElementById("servicesTrack");
  track.addEventListener("mouseenter", stopAutoSlide);
  track.addEventListener("mouseleave", startAutoSlide);
}

function slideServices(direction) {
  if (isTransitioning || servicesData.length === 0) return;

  isTransitioning = true;
  currentSlide += direction;
  updateCarousel(true);

  // Handle infinite loop reset
  setTimeout(() => {
    const totalCards = servicesData.length * 3;

    if (currentSlide >= servicesData.length * 2) {
      // Reset to start of original cards
      currentSlide = servicesData.length;
      updateCarousel(false);
    } else if (currentSlide < servicesData.length) {
      // Reset to end of original cards
      currentSlide = servicesData.length * 2 - 1;
      updateCarousel(false);
    }

    isTransitioning = false;
  }, 500);

  updateDots();

  // Reset auto slide timer
  stopAutoSlide();
  startAutoSlide();
}

function updateCarousel(animate = true) {
  const track = document.getElementById("servicesTrack");
  const cards = track.querySelectorAll(".service-card");

  if (cards.length === 0) return;

  const visibleCards = getVisibleCards();
  const cardElement = cards[0];
  const cardWidth = cardElement.offsetWidth;
  const gap = 24; // 1.5rem = 24px

  const moveAmount = (cardWidth + gap) * currentSlide;

  if (!animate) {
    track.style.transition = "none";
  } else {
    track.style.transition = "transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
  }

  track.style.transform = `translateX(-${moveAmount}px)`;

  // Re-enable transition after instant move
  if (!animate) {
    setTimeout(() => {
      track.style.transition = "transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
    }, 50);
  }
}

function getVisibleCards() {
  const width = window.innerWidth;
  if (width <= 768) return 1;
  if (width <= 1024) return 2;
  return 3;
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

  // Reset auto slide timer
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

// Handle window resize
let resizeTimer;
window.addEventListener("resize", () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(() => {
    updateCarousel(false);
  }, 250);
});
