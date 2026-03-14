// frontend/public/js/auth.js

// ── Password toggle ───────────────────────────────────────────────
document.querySelectorAll('.password-toggle').forEach((btn) => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.classList.toggle('visible', !isPassword);
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
  });
});

// ── Toast / inline error helpers ─────────────────────────────────

/**
 * Show an inline error message below a form.
 * @param {HTMLElement} form  - The form element
 * @param {string}      msg   - Message to display
 * @param {string}      type  - 'error' | 'success'
 */
function showAuthError(form, msg, type = 'error') {
  // Remove any existing alert
  const existing = form.querySelector('.auth-alert');
  if (existing) existing.remove();

  const icon = type === 'success'
    ? '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
    : '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';

  const alert = document.createElement('div');
  alert.className = `auth-alert auth-alert--${type}`;
  alert.innerHTML = `
    <span class="auth-alert__icon">${icon}</span>
    <span class="auth-alert__msg">${msg}</span>
    <button type="button" class="auth-alert__close" aria-label="Dismiss">
      <svg viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
      </svg>
    </button>
  `;

  // Insert before the submit button
  const submitBtn = form.querySelector('[type="submit"]');
  form.insertBefore(alert, submitBtn);

  // Dismiss on close button
  alert.querySelector('.auth-alert__close').addEventListener('click', () => alert.remove());

  // Auto-dismiss after 5s for errors, 2s for success
  setTimeout(() => alert?.remove(), type === 'success' ? 2000 : 5000);

  // Shake animation on error
  if (type === 'error') {
    alert.classList.add('auth-alert--shake');
    setTimeout(() => alert.classList.remove('auth-alert--shake'), 500);
  }
}

// ── Generic fetch form handler ────────────────────────────────────
async function handleApiFormSubmit(form, endpoint) {
  const submitBtn = form.querySelector('[type="submit"]');

  // Loading state on button
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = 'Please wait...';

  // Clear previous alert
  form.querySelector('.auth-alert')?.remove();

  try {
    const payload = Object.fromEntries(new FormData(form).entries());
    console.log('Sending payload:', payload);

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));
    console.log('Response status:', response.status);
    console.log('Response data:', data);

    if (!response.ok || data.success === false) {
      const message = data.message || data.error || `Server error (${response.status}). Please try again.`;
      showAuthError(form, message, 'error');
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
      return;
    }

    // Store auth data
    if (data.token && data.userData) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('userData', JSON.stringify(data.userData));
      console.log('User logged in, data stored in localStorage');
    }

    if (data.redirect) {
      window.location.href = data.redirect;
    } else if (data.success) {
      showAuthError(form, 'Registration successful! Redirecting...', 'success');
      setTimeout(() => { window.location.href = './customer-login.html'; }, 1800);
    }

  } catch (err) {
    console.error('Error:', err);
    showAuthError(form, 'Network error. Please check your connection and try again.', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
  }
}

// ── Customer login ────────────────────────────────────────────────
const customerLoginForm = document.getElementById('customer-login-form');
if (customerLoginForm) {
  customerLoginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    handleApiFormSubmit(customerLoginForm, '../../backend/public/index.php?url=auth/login');
  });
}

// ── Customer register ─────────────────────────────────────────────
const registerForm = document.getElementById('customer-register-form');
if (registerForm) {
  registerForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const password        = document.getElementById('reg-password').value;
    const confirmPassword = document.getElementById('reg-confirm-password').value;

    if (password !== confirmPassword) {
      showAuthError(registerForm, 'Passwords do not match!', 'error');
      return;
    }

    if (password.length < 8) {
      showAuthError(registerForm, 'Password must be at least 8 characters long!', 'error');
      return;
    }

    handleApiFormSubmit(registerForm, '../../backend/public/index.php?url=auth/register');
  });
}

// ── Admin login ───────────────────────────────────────────────────
const adminLoginForm = document.getElementById('admin-login-form');
if (adminLoginForm) {
  adminLoginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    handleApiFormSubmit(adminLoginForm, '../../backend/public/index.php?url=auth/login');
  });
}

// ── Superadmin login ──────────────────────────────────────────────
const superadminLoginForm = document.getElementById('superadmin-login-form');
if (superadminLoginForm) {
  superadminLoginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    handleApiFormSubmit(superadminLoginForm, '../../backend/public/index.php?url=auth/login');
  });
}
