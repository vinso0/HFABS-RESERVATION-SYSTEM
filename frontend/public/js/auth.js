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
    
    // Debug: Log what we're sending
    console.log('Sending payload:', payload);

    const response = await fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));
    
    // Debug: Log response
    console.log('Response status:', response.status);
    console.log('Response data:', data);

    if (!response.ok || data.success === false) {
      const message =
        data.message ||
        data.error ||
        `Server error (${response.status}). Please try again.`;
      alert(message);
      return;
    }

    // Store authentication data if available
    if (data.token && data.userData) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('userData', JSON.stringify(data.userData));
      console.log('User logged in, data stored in localStorage');
    }

    if (data.redirect) {
       window.location.href = data.redirect;
     } else if (data.success) {
       alert("Registration successful! Redirecting to login...");
       window.location.href = "./customer-login.html";
     } else {
       alert("Success.");
     }
  } catch (err) {
    console.error('Error:', err);
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
    
    // Client-side validation
    const password = document.getElementById('reg-password').value;
    const confirmPassword = document.getElementById('reg-confirm-password').value;
    
    if (password !== confirmPassword) {
      alert('Passwords do not match!');
      return;
    }
    
    if (password.length < 8) {
      alert('Password must be at least 8 characters long!');
      return;
    }
    
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

// Superadmin login
const superadminLoginForm = document.getElementById("superadmin-login-form");
if (superadminLoginForm) {
  superadminLoginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    handleApiFormSubmit(
      superadminLoginForm,
      "../../backend/public/index.php?url=auth/login"
    );
  });
}
