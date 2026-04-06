// frontend/public/js/auth.js

// ── Password toggle (Font Awesome fa-eye / fa-eye-slash) ──────────
document.querySelectorAll('.password-toggle').forEach((btn) => {
  // Seed the icon on load
  btn.innerHTML = '<i class="fas fa-eye"></i>';

  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.querySelector('i').className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
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
  const originalHTML = submitBtn.innerHTML;
  submitBtn.disabled = true;
submitBtn.innerHTML = '<span class="auth-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:8px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;animation:authSpin 0.75s linear infinite;"></span> Please wait...';
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
      submitBtn.innerHTML = originalHTML;
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
const sendOtpBtn = document.getElementById('send-otp-btn');
const otpGroup = document.getElementById('otp-group');
const emailInput = document.getElementById('reg-email');
const otpInput = document.getElementById('reg-otp');

let otpSent = false;

// Send OTP functionality
if (sendOtpBtn) {
  sendOtpBtn.addEventListener('click', async () => {
    const email = emailInput.value.trim();
    const username = document.getElementById('reg-fullname').value.trim();
    
    if (!email) {
      showAuthError(registerForm, 'Please enter your email address first', 'error');
      return;
    }
    
    if (!validateEmail(email)) {
      showAuthError(registerForm, 'Only trusted email domains are allowed (Gmail, Outlook, Yahoo, .edu.ph, .gov.ph, etc.)', 'error');
      return;
    }
    
    if (!username) {
      showAuthError(registerForm, 'Please enter your full name first', 'error');
      return;
    }
    
    // Disable button and show loading
    const originalText = sendOtpBtn.textContent;
    sendOtpBtn.disabled = true;
    sendOtpBtn.textContent = 'Sending...';
    
    try {
      const response = await fetch('../../backend/public/index.php?url=auth/sendEmailVerificationOTP', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ email, username }),
      });
      
      const data = await response.json();
      
      if (response.ok && data.success) {
        showAuthError(registerForm, data.message, 'success');
        otpGroup.style.display = 'block';
        otpSent = true;
        
        // Start countdown
        startOtpCountdown();
      } else {
        showAuthError(registerForm, data.message || 'Failed to send verification code', 'error');
        sendOtpBtn.disabled = false;
        sendOtpBtn.textContent = originalText;
      }
    } catch (err) {
      console.error('Error:', err);
      showAuthError(registerForm, 'Network error. Please try again.', 'error');
      sendOtpBtn.disabled = false;
      sendOtpBtn.textContent = originalText;
    }
  });
}

// OTP countdown timer
function startOtpCountdown() {
  let countdown = 60; // 60 seconds
  sendOtpBtn.textContent = `Resend (${countdown}s)`;
  
  const interval = setInterval(() => {
    countdown--;
    if (countdown > 0) {
      sendOtpBtn.textContent = `Resend (${countdown}s)`;
    } else {
      clearInterval(interval);
      sendOtpBtn.disabled = false;
      sendOtpBtn.textContent = 'Resend OTP';
    }
  }, 1000);
}

// Enhanced email validation with allowlist
function validateEmail(email) {
  // Basic format validation
  const basicRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!basicRegex.test(email)) {
    return false;
  }

  // List of allowed trusted domains
  const allowedDomains = [
    // Major email providers
    'gmail.com', 'googlemail.com',
    'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
    'yahoo.com', 'ymail.com', 'rocketmail.com',
    
    // Other popular providers
    'icloud.com', 'me.com', 'mac.com',
    'aol.com', 'protonmail.com', 'tutanota.com',
    
    // Philippine educational domains
    'up.edu.ph', 'dlsu.edu.ph', 'ust.edu.ph', 'admu.edu.ph',
    'pnu.edu.ph', 'feu.edu.ph', 'cebu.edu.ph', 'slsu.edu.ph',
    'msuiit.edu.ph', 'usc.edu.ph', 'xu.edu.ph', 'wvsu.edu.ph',
    'bsu.edu.ph', 'isu.edu.ph', 'msu.edu.ph', 'clsu.edu.ph',
    'pup.edu.ph', 'tips.edu.ph', 'ceu.edu.ph', 'hnu.edu.ph',
    'usc.edu.ph', 'csu.edu.ph', 'nmsc.edu.ph', 'uvis.edu.ph',
    
    // Philippine government domains
    'gov.ph', 'dost.gov.ph', 'deped.gov.ph', 'ched.gov.ph',
  ];

  // Extract domain from email
  const domain = email.substring(email.lastIndexOf('@') + 1).toLowerCase();
  
  // Check if domain is exactly in allowed list
  if (allowedDomains.includes(domain)) {
    return true;
  }
  
  // Check if it's a .edu.ph domain (allow all educational institutions)
  if (domain.endsWith('.edu.ph')) {
    return true;
  }
  
  // Check if it's a .gov.ph domain (allow government institutions)
  if (domain.endsWith('.gov.ph')) {
    return true;
  }

  return false;
}

if (registerForm) {
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const password        = document.getElementById('reg-password').value;
    const confirmPassword = document.getElementById('reg-confirm-password').value;
    const otp            = otpInput.value.trim();

    // Check if OTP was sent
    if (!otpSent) {
      showAuthError(registerForm, 'Please request a verification code first', 'error');
      return;
    }

    // Check if OTP is entered
    if (!otp) {
      showAuthError(registerForm, 'Please enter the verification code sent to your email', 'error');
      return;
    }

    // Password validation
    if (password.length < 8) {
      showAuthError(registerForm, 'Password must be at least 8 characters long!', 'error');
      return;
    }

    if (password !== confirmPassword) {
      showAuthError(registerForm, 'Passwords do not match!', 'error');
      return;
    }

    // Handle form submission with OTP
    await handleRegistrationWithOTP();
  });
}

async function handleRegistrationWithOTP() {
  const submitBtn = registerForm.querySelector('[type="submit"]');
  const originalHTML = submitBtn.innerHTML;
  
  // Loading state
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="auth-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:8px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;animation:authSpin 0.75s linear infinite;"></span> Creating Account...';
  
  // Clear previous alert
  registerForm.querySelector('.auth-alert')?.remove();

  try {
    const payload = {
      username: document.getElementById('reg-fullname').value.trim(),
      email: emailInput.value.trim(),
      password: document.getElementById('reg-password').value,
      contact_number: document.getElementById('reg-contact').value.trim(),
      otp: otpInput.value.trim()
    };

    const response = await fetch('../../backend/public/index.php?url=auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok || data.success === false) {
      const message = data.message || data.error || `Server error (${response.status}). Please try again.`;
      showAuthError(registerForm, message, 'error');
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalHTML;
      return;
    }

    if (data.success) {
      showAuthError(registerForm, 'Registration successful! Redirecting...', 'success');
      setTimeout(() => { window.location.href = './customer-login.html'; }, 1800);
    }

  } catch (err) {
    console.error('Error:', err);
    showAuthError(registerForm, 'Network error. Please check your connection and try again.', 'error');
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalHTML;
  }
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