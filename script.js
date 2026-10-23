
document.addEventListener("DOMContentLoaded", () => {

  // 1. Light/Dark theme with localStorage
  const themeToggle = document.getElementById("themeToggle");

  function updateThemeButton() {
    if (!themeToggle) return;

    themeToggle.textContent =
      document.body.classList.contains("dark-mode")
        ? "Switch to Light Mode"
        : "Switch to Dark Mode";
  }

  // Restore saved theme
  try {
    if (localStorage.getItem("studenthub-theme") === "dark") {
      document.body.classList.add("dark-mode");
    }
  } catch (error) {
    console.warn("Could not load saved theme.", error);
  }

  updateThemeButton();

  if (themeToggle) {
    themeToggle.addEventListener("click", () => {
      document.body.classList.toggle("dark-mode");

      const theme = document.body.classList.contains("dark-mode")
        ? "dark"
        : "light";

      try {
        localStorage.setItem("studenthub-theme", theme);
      } catch (error) {
        console.warn("Could not save theme preference.", error);
      }

      updateThemeButton();
    });
  }


  // 2. Hamburger navigation menu
  const menuToggle = document.getElementById("menuToggle");
  const navLinks = document.querySelector("header nav ul");

  if (menuToggle && navLinks) {
    menuToggle.addEventListener("click", () => {
      const isOpen = navLinks.classList.toggle("menu-open");

      menuToggle.setAttribute("aria-expanded", String(isOpen));
    });
  }


  // 3. Notification banner
  const notification = document.getElementById("notification");
  const closeNotification =
    document.getElementById("closeNotification");

  if (notification) {
    notification.hidden = false;
  }

  if (closeNotification && notification) {
    closeNotification.addEventListener("click", () => {
      notification.hidden = true;
    });
  }


  // 4. Collapsible FAQ
  document.querySelectorAll(".faq-question").forEach((question) => {
    question.addEventListener("click", () => {
      const answer = question.nextElementSibling;

      if (!answer) return;

      const isOpen =
        question.getAttribute("aria-expanded") === "true";

      question.setAttribute("aria-expanded", String(!isOpen));
      answer.hidden = isOpen;
    });
  });


  // 5. Modal popup
  const modal = document.getElementById("modal");
  const openModal = document.getElementById("openModal");
  const closeModal = document.getElementById("closeModal");

  if (modal && openModal && closeModal) {

    openModal.addEventListener("click", () => {
      modal.hidden = false;
      closeModal.focus();
    });

    function hideModal() {
      modal.hidden = true;
      openModal.focus();
    }

    closeModal.addEventListener("click", hideModal);

    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        hideModal();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !modal.hidden) {
        hideModal();
      }
    });
  }


  // 6. Content slider
  const slides = document.querySelectorAll(".slide");
  const nextSlide = document.getElementById("nextSlide");
  const prevSlide = document.getElementById("prevSlide");

  let currentSlide = 0;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.hidden = i !== index;
      slide.classList.toggle("active", i === index);
    });
  }

  if (slides.length > 0) {
    showSlide(currentSlide);

    if (nextSlide) {
      nextSlide.addEventListener("click", () => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
      });
    }

    if (prevSlide) {
      prevSlide.addEventListener("click", () => {
        currentSlide =
          (currentSlide - 1 + slides.length) % slides.length;

        showSlide(currentSlide);
      });
    }
  }

});
