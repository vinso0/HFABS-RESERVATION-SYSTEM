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
      Toast.error(data.message || 'Failed to load profile.');
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
    Toast.error('Could not connect to server.');
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
      Toast.success('Profile updated successfully!');
      await loadProfile();
      toggleEdit();
    } else {
      Toast.error(data.message || 'Update failed.');
    }

  } catch (err) {
    console.error('updateProfile error:', err);
    Toast.error('Could not connect to server.');
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
    Toast.error('New passwords do not match.');
    return;
  }

  if (newPw.length < 6) {
    Toast.error('Password must be at least 6 characters.');
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
      Toast.success('Password changed successfully!');
      e.target.reset();
    } else {
      Toast.error(data.message || 'Password update failed.');
    }

  } catch (err) {
    console.error('changePassword error:', err);
    Toast.error('Could not connect to server.');
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
