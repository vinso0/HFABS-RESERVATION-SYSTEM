// frontend/public/js/customer-profile.js

const API_BASE = '../../backend/public/index.php?url=auth';

// ── Load profile on page load ────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadProfile();
});

async function loadProfile() {
  try {
    const res  = await fetch(`${API_BASE}/getProfile`, {
      credentials: 'same-origin',
    });
    const data = await res.json();

    if (!data.success) {
      showToast(data.message || 'Failed to load profile.', 'error');
      return;
    }

    const u = data.data;

    // ── Header ──
    document.getElementById('displayName').textContent = u.username || '—';
    document.getElementById('displayMemberSince').innerHTML =
      `<i class="fas fa-calendar-alt"></i> Member since ${formatDate(u.created_at)}`;

    // ── Info view ──
    document.getElementById('viewName').textContent    = u.username       || '—';
    document.getElementById('viewEmail').textContent   = u.email          || '—';
    document.getElementById('viewContact').textContent = u.contact_number || '—';

    const statusEl = document.getElementById('viewStatus');
    if (parseInt(u.is_active) === 1) {
      statusEl.innerHTML = '<span class="status-active"><i class="fas fa-circle-check"></i> Active</span>';
    } else {
      statusEl.innerHTML = '<span class="status-inactive"><i class="fas fa-circle-xmark"></i> Inactive</span>';
    }

    // ── Pre-fill edit form ──
    document.getElementById('editName').value    = u.username       || '';
    document.getElementById('editEmail').value   = u.email          || '';
    document.getElementById('editContact').value = u.contact_number || '';

  } catch (err) {
    console.error('loadProfile error:', err);
    showToast('Could not connect to server.', 'error');
  }
}

// ── Toggle edit form ─────────────────────────────────────────────
function toggleEdit() {
  const viewSection = document.getElementById('viewSection');
  const editSection = document.getElementById('editSection');
  const isEditing   = editSection.style.display === 'block';

  viewSection.style.display = isEditing ? 'block' : 'none';
  editSection.style.display = isEditing ? 'none'  : 'block';
}

// ── Submit Edit Profile ──────────────────────────────────────────
async function submitEditProfile(e) {
  e.preventDefault();

  const btn = e.target.querySelector('.btn-save');
  btn.disabled    = true;
  btn.innerHTML   = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  const payload = {
    username:       document.getElementById('editName').value.trim(),
    email:          document.getElementById('editEmail').value.trim(),
    contact_number: document.getElementById('editContact').value.trim(),
  };

  try {
    const res  = await fetch(`${API_BASE}/updateProfile`, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      showToast('Profile updated successfully!', 'success');
      await loadProfile();
      toggleEdit();
    } else {
      showToast(data.message || 'Update failed.', 'error');
    }

  } catch (err) {
    console.error('updateProfile error:', err);
    showToast('Could not connect to server.', 'error');
  } finally {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-check"></i> Save Changes';
  }
}

// ── Submit Change Password ───────────────────────────────────────
async function submitChangePassword(e) {
  e.preventDefault();

  const currentPw = document.getElementById('currentPassword').value;
  const newPw     = document.getElementById('newPassword').value;
  const confirmPw = document.getElementById('confirmPassword').value;

  if (newPw !== confirmPw) {
    showToast('New passwords do not match.', 'error');
    return;
  }

  if (newPw.length < 6) {
    showToast('Password must be at least 6 characters.', 'error');
    return;
  }

  const btn = e.target.querySelector('.btn-save');
  btn.disabled  = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

  try {
    const res  = await fetch(`${API_BASE}/changePassword`, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify({
        current_password: currentPw,
        new_password:     newPw,
      }),
    });
    const data = await res.json();

    if (data.success) {
      showToast('Password changed successfully!', 'success');
      e.target.reset();
    } else {
      showToast(data.message || 'Password update failed.', 'error');
    }

  } catch (err) {
    console.error('changePassword error:', err);
    showToast('Could not connect to server.', 'error');
  } finally {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-check"></i> Update Password';
  }
}

// ── Toggle password field visibility ────────────────────────────
function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon  = btn.querySelector('i');

  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

// ── Date formatter ───────────────────────────────────────────────
function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', {
    year:  'numeric',
    month: 'long',
    day:   'numeric',
  });
}
