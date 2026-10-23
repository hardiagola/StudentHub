
document.addEventListener("DOMContentLoaded", () => {

  // 1. Light/Dark theme with saved preference
  const themeToggle = document.getElementById("themeToggle");

  function updateThemeButton() {
    if (!themeToggle) return;

    themeToggle.textContent =
      document.body.classList.contains("dark-mode")
        ? "Switch to Light Mode"
        : "Switch to Dark Mode";
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
  const closeNotification = document.getElementById("closeNotification");

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


  // 7. Practical 5: Registration form validation
  const form = document.getElementById("registrationForm");

  if (form) {
    const fields = {
      fullname: document.getElementById("fullname"),
      email: document.getElementById("email"),
      mobile: document.getElementById("mobile"),
      enrollment: document.getElementById("enrollment"),
      password: document.getElementById("password"),
      confirmPassword: document.getElementById("confirmPassword"),
      course: document.getElementById("course"),
      year: document.getElementById("year"),
      terms: document.getElementById("terms")
    };

    const formMessage = document.getElementById("formMessage");
    const strengthMeter = document.getElementById("passwordStrength");
    const strengthText = document.getElementById("strengthText");

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

    function setError(fieldName, message) {
      const errorElement = document.getElementById(errorIds[fieldName]);

      if (errorElement) {
        errorElement.textContent = message;
      }

      const field = fields[fieldName];

      if (field) {
        field.setAttribute("aria-invalid", String(Boolean(message)));
      }

      if (fieldName === "gender") {
        document.querySelectorAll('input[name="gender"]').forEach((radio) => {
          radio.setAttribute("aria-invalid", String(Boolean(message)));
        });
      }
    }

    function validateField(fieldName) {
      let message = "";
      const field = fields[fieldName];
      const value = field && typeof field.value === "string"
        ? field.value.trim()
        : "";

      switch (fieldName) {
        case "fullname":
          if (!value) {
            message = "Please enter your full name.";
          } else if (!/^[A-Za-z][A-Za-z\s'-]{1,59}$/.test(value)) {
            message = "Enter a name using letters, spaces, apostrophes or hyphens.";
          }
          break;

        case "email":
          if (!value) {
            message = "Please enter your email address.";
          } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            message = "Please enter a valid email address.";
          }
          break;

        case "mobile":
          if (!value) {
            message = "Please enter your mobile number.";
          } else if (!/^[6-9]\d{9}$/.test(value)) {
            message = "Enter a valid 10-digit Indian mobile number.";
          }
          break;

        case "enrollment":
          if (!value) {
            message = "Please enter your enrollment number.";
          } else if (value.length > 30) {
            message = "Enrollment number must be 30 characters or fewer.";
          }
          break;

        case "password": {
          const password = field.value;
          if (!password) {
            message = "Please create a password.";
          } else if (password.length < 8) {
            message = "Password must contain at least 8 characters.";
          } else if (!/[A-Z]/.test(password)) {
            message = "Include at least one uppercase letter.";
          } else if (!/[a-z]/.test(password)) {
            message = "Include at least one lowercase letter.";
          } else if (!/\d/.test(password)) {
            message = "Include at least one number.";
          } else if (!/[^A-Za-z0-9]/.test(password)) {
            message = "Include at least one special character.";
          }
          break;
        }

        case "confirmPassword":
          if (!field.value) {
            message = "Please confirm your password.";
          } else if (field.value !== fields.password.value) {
            message = "Passwords do not match.";
          }
          break;

        case "course":
          if (!field.value) {
            message = "Please select your course.";
          }
          break;

        case "year":
          if (!field.value) {
            message = "Please select your academic year.";
          }
          break;

        case "gender":
          if (!document.querySelector('input[name="gender"]:checked')) {
            message = "Please select a gender option.";
          }
          break;

        case "terms":
          if (!field.checked) {
            message = "You must accept the terms and conditions.";
          }
          break;
      }

      setError(fieldName, message);
      return message === "";
    }


    // Password strength meter
    function updatePasswordStrength() {
      const password = fields.password.value;
      let score = 0;

      if (password.length >= 8) score++;
      if (/[A-Z]/.test(password)) score++;
      if (/[a-z]/.test(password)) score++;
      if (/\d/.test(password)) score++;
      if (/[^A-Za-z0-9]/.test(password)) score++;

      if (strengthMeter) {
        strengthMeter.value = Math.min(score, 4);
      }

      if (strengthText) {
        if (!password) {
          strengthText.textContent = "Not entered";
        } else if (score <= 2) {
          strengthText.textContent = "Weak";
        } else if (score === 3) {
          strengthText.textContent = "Fair";
        } else if (score === 4) {
          strengthText.textContent = "Good";
        } else {
          strengthText.textContent = "Strong";
        }
      }
    }

    fields.password.addEventListener("input", () => {
      updatePasswordStrength();

      if (fields.password.value) {
        validateField("password");
      }

      if (fields.confirmPassword.value) {
        validateField("confirmPassword");
      }
    });


    // Real-time validation for text fields and select menus
    ["fullname", "email", "mobile", "enrollment",
     "confirmPassword", "course", "year"].forEach((fieldName) => {
      const field = fields[fieldName];

      field.addEventListener("input", () => {
        if (field.value) {
          validateField(fieldName);
        }
      });

      field.addEventListener("change", () => {
        if (field.value) {
          validateField(fieldName);
        }
      });
    });

    document.querySelectorAll('input[name="gender"]').forEach((radio) => {
      radio.addEventListener("change", () => validateField("gender"));
    });

    fields.terms.addEventListener("change", () => {
      validateField("terms");
    });


    // Validate all fields when submitting
    form.addEventListener("submit", (event) => {
      event.preventDefault();

      const fieldNames = [
        "fullname",
        "email",
        "mobile",
        "enrollment",
        "password",
        "confirmPassword",
        "course",
        "year",
        "gender",
        "terms"
      ];

      const results = fieldNames.map(validateField);
      const isValid = results.every(Boolean);

      if (!isValid) {
        formMessage.textContent =
          "Please correct the errors highlighted above.";

        const firstInvalid = form.querySelector('[aria-invalid="true"]');

        if (firstInvalid) {
          firstInvalid.focus();
        }

        return;
      }

      formMessage.textContent =
        "Validation successful! Your form is ready for submission.";

      // This is a frontend-only practical.
      // No data is sent to a server.
    });


    // Reset error messages when the form is cleared
    form.addEventListener("reset", () => {
      setTimeout(() => {
        Object.keys(errorIds).forEach((fieldName) => {
          setError(fieldName, "");
        });

        if (formMessage) formMessage.textContent = "";
        if (strengthMeter) strengthMeter.value = 0;
        if (strengthText) strengthText.textContent = "Not entered";
      }, 0);
    });
  }

});
