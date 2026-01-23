// frontend/public/js/auth.js

// Password visibility toggle
const toggleButtons = document.querySelectorAll(".password-toggle");

toggleButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const targetId = btn.dataset.target;
    const input = document.getElementById(targetId);
    if (!input) return;

    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    btn.classList.toggle('visible', !isPassword);
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
  });
});

// Helper: convert FormData to plain object
const formDataToJson = (form) =>
  Object.fromEntries(new FormData(form).entries());

// Helper: generic submit via fetch
async function handleApiFormSubmit(form, endpoint) {
  try {
    const payload = formDataToJson(form);

    const response = await fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      credentials: "same-origin", // keeps PHP sessions working
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok || data.success === false) {
      const message =
        data.message ||
        data.error ||
        "Something went wrong. Please try again.";
      alert(message);
      return;
    }

    if (data.redirect) {
      window.location.href = data.redirect;
    } else {
      // Fallback: simple success notice
      alert("Success.");
    }
  } catch (err) {
    console.error(err);
    alert("Network error. Please check your connection and try again.");
  }
}

// Customer login
const customerLoginForm = document.getElementById("customer-login-form");
if (customerLoginForm) {
  customerLoginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    handleApiFormSubmit(
      customerLoginForm,
      "../../backend/public/index.php?url=auth/login"
    );
  });
}

// Customer registration
const registerForm = document.getElementById("customer-register-form");

if (registerForm) {
  registerForm.addEventListener("submit", (e) => {
    e.preventDefault();
    handleApiFormSubmit(
      registerForm,
      "../../backend/public/index.php?url=auth/register"
    );
  });
}

// Admin login
const adminLoginForm = document.getElementById("admin-login-form");
if (adminLoginForm) {
  adminLoginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    handleApiFormSubmit(
      adminLoginForm,
      "../../backend/public/index.php?url=auth/login"
    );
  });
}
