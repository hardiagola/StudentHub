
document.addEventListener("DOMContentLoaded", () => {
  // 1. Light/Dark theme
  const themeToggle = document.getElementById("themeToggle");

  function updateThemeButton() {
    if (themeToggle) {
      themeToggle.textContent =
        document.body.classList.contains("dark-mode")
          ? "Switch to Light Mode"
          : "Switch to Dark Mode";
    }
  }

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

      try {
        localStorage.setItem(
          "studenthub-theme",
          document.body.classList.contains("dark-mode") ? "dark" : "light"
        );
      } catch (error) {
        console.warn("Could not save theme.", error);
      }

      updateThemeButton();
    });
  }

  // 2. Hamburger menu
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
  const closeNotification = document.getElementById("closeNotification");

  if (notification) notification.hidden = false;

  if (notification && closeNotification) {
    closeNotification.addEventListener("click", () => {
      notification.hidden = true;
    });
  }

  // 4. Collapsible FAQ
  document.querySelectorAll(".faq-question").forEach(question => {
    question.addEventListener("click", () => {
      const answer = question.nextElementSibling;
      if (!answer) return;

      const isOpen = question.getAttribute("aria-expanded") === "true";
      question.setAttribute("aria-expanded", String(!isOpen));
      answer.hidden = isOpen;
    });
  });

  // 5. Modal popup
  const modal = document.getElementById("modal");
  const openModal = document.getElementById("openModal");
  const closeModal = document.getElementById("closeModal");

  if (modal && openModal && closeModal) {
    function hideModal() {
      modal.hidden = true;
      openModal.focus();
    }

    openModal.addEventListener("click", () => {
      modal.hidden = false;
      closeModal.focus();
    });

    closeModal.addEventListener("click", hideModal);

    modal.addEventListener("click", event => {
      if (event.target === modal) hideModal();
    });

    document.addEventListener("keydown", event => {
      if (event.key === "Escape" && !modal.hidden) hideModal();
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
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
      });
    }
  }

  // 7. Practical 5 and 7: Registration validation
  const form = document.getElementById("registrationForm");

  if (!form) return;

  const get = id => document.getElementById(id);

  const fields = {
    fullname: get("fullname"),
    email: get("email"),
    mobile: get("mobile"),
    enrollment: get("enrollment"),
    password: get("password"),
    confirmPassword: get("confirmPassword"),
    course: get("course"),
    year: get("year"),
    terms: get("terms")
  };

  const errorIds = {
    fullname: "fullnameError",
    email: "emailError",
    mobile: "mobileError",
    enrollment: "enrollmentError",
    password: "passwordError",
    confirmPassword: "confirmPasswordError",
    course: "courseError",
    year: "yearError",
    gender: "genderError",
    terms: "termsError"
  };

  const formMessage = get("formMessage");

  function setError(name, message) {
    const errorElement = get(errorIds[name]);
    if (errorElement) errorElement.textContent = message;

    if (fields[name]) {
      fields[name].setAttribute("aria-invalid", String(Boolean(message)));
    }

    if (name === "gender") {
      document.querySelectorAll('input[name="gender"]').forEach(radio => {
        radio.setAttribute("aria-invalid", String(Boolean(message)));
      });
    }
  }

  function validate(name) {
    const field = fields[name];
    const value = field && typeof field.value === "string"
      ? field.value.trim()
      : "";

    let message = "";

    switch (name) {
      case "fullname":
        if (!value) message = "Please enter your full name.";
        else if (!/^[A-Za-z][A-Za-z\s'-]{1,59}$/.test(value))
          message = "Enter a valid name using letters and spaces.";
        break;

      case "email":
        if (!value) message = "Please enter your email.";
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
          message = "Please enter a valid email address.";
        break;

      case "mobile":
        if (!value) message = "Please enter your mobile number.";
        else if (!/^[6-9]\d{9}$/.test(value))
          message = "Enter a valid 10-digit Indian mobile number.";
        break;

      case "enrollment":
        if (!value) message = "Please enter your enrollment number.";
        else if (value.length > 30)
          message = "Enrollment number must be 30 characters or fewer.";
        break;

      case "password": {
        const password = field.value;
        if (password.length < 8) message = "Use at least 8 characters.";
        else if (!/[A-Z]/.test(password)) message = "Add an uppercase letter.";
        else if (!/[a-z]/.test(password)) message = "Add a lowercase letter.";
        else if (!/\d/.test(password)) message = "Add a number.";
        else if (!/[^A-Za-z0-9]/.test(password))
          message = "Add a special character.";
        break;
      }

      case "confirmPassword":
        if (!field.value) message = "Please confirm your password.";
        else if (field.value !== fields.password.value)
          message = "Passwords do not match.";
        break;

      case "course":
        if (!value) message = "Please select your course.";
        break;

      case "year":
        if (!value) message = "Please select your academic year.";
        break;

      case "gender":
        if (!document.querySelector('input[name="gender"]:checked'))
          message = "Please select a gender option.";
        break;

      case "terms":
        if (!field.checked) message = "Please accept the terms and conditions.";
        break;
    }

    setError(name, message);
    return message === "";
  }

  // Password strength meter
  function updateStrength() {
    const password = fields.password.value;
    const score = [
      password.length >= 8,
      /[A-Z]/.test(password),
      /[a-z]/.test(password),
      /\d/.test(password),
      /[^A-Za-z0-9]/.test(password)
    ].filter(Boolean).length;

    get("passwordStrength").value = Math.min(score, 4);

    get("strengthText").textContent = !password ? "Not entered"
      : score <= 2 ? "Weak"
      : score === 3 ? "Fair"
      : score === 4 ? "Good" : "Strong";
  }

  fields.password.addEventListener("input", () => {
    updateStrength();
    validate("password");

    if (fields.confirmPassword.value) validate("confirmPassword");
  });

  [
    "fullname", "email", "mobile", "enrollment",
    "confirmPassword", "course", "year"
  ].forEach(name => {
    fields[name].addEventListener("input", () => validate(name));
    fields[name].addEventListener("change", () => validate(name));
  });

  document.querySelectorAll('input[name="gender"]').forEach(radio => {
    radio.addEventListener("change", () => validate("gender"));
  });

  fields.terms.addEventListener("change", () => validate("terms"));

  // IMPORTANT: allow valid forms to reach register.php
  form.addEventListener("submit", event => {
    const names = [
      "fullname", "email", "mobile", "enrollment",
      "password", "confirmPassword", "course", "year",
      "gender", "terms"
    ];

    const valid = names.map(validate).every(Boolean);

    if (!valid) {
      event.preventDefault();
      formMessage.textContent = "Please correct the errors above.";

      const firstInvalid = form.querySelector('[aria-invalid="true"]');
      if (firstInvalid) firstInvalid.focus();
      return;
    }

    formMessage.textContent = "Submitting registration...";
    // Do not prevent the default action here.
    // The browser submits the form to register.php.
  });

  form.addEventListener("reset", () => {
    setTimeout(() => {
      Object.keys(errorIds).forEach(name => setError(name, ""));

      formMessage.textContent = "";
      get("passwordStrength").value = 0;
      get("strengthText").textContent = "Not entered";
    }, 0);
  });
});
