const API = '../../backend/public/index.php?url=superadmin/';

let adminsData          = [];
let branchesForDropdown = [];
let deleteTargetId      = null;
let filteredAdmins      = [];
let pagination;

// ── Load admins from API ──
function loadAdmins() {
    fetch(API + 'admins')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                adminsData     = json.data;
                filteredAdmins = [...adminsData];
                pagination.updateTotalItems(filteredAdmins.length);
                renderAdmins();
            } else {
                showToast(json.message || 'Failed to load admins.', 'error', 'Error');
            }
        })
        .catch(function(err) {
            console.error(err);
            showToast('Network error loading admins.', 'error', 'Error');
        });
}

// ── Load branches for dropdown ──
function loadBranchesForDropdown() {
    fetch(API + 'branches')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                branchesForDropdown = json.data;
            }
        })
        .catch(function(err) { console.error(err); });
}

// ── Render Table ──
function renderAdmins() {
    var tbody   = document.getElementById('admins-tbody');
    var countEl = document.getElementById('admin-count');
    countEl.textContent = filteredAdmins.length + ' record' + (filteredAdmins.length !== 1 ? 's' : '');

    if (!filteredAdmins.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No admin accounts found.</p></div></td></tr>';
        pagination.updateTotalItems(0);
        return;
    }

    var range = pagination.getCurrentPageRange();
    var slice = filteredAdmins.slice(range.start, range.end);

    tbody.innerHTML = slice.map(function(a, idx) {
        var date        = new Date(a.created_at).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
        var roleClass   = a.role === 'admin' ? 'badge-admin' : 'badge-cashier';
        var activeClass = parseInt(a.is_active) === 1 ? 'badge-active' : 'badge-inactive';
        var activeLabel = parseInt(a.is_active) === 1 ? 'Active' : 'Inactive';
        var safeName    = a.username.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
        return '<tr>' +
            '<td>' + (range.start + idx + 1) + '</td>' +
            '<td><strong>' + a.username + '</strong></td>' +
            '<td>' + a.email + '</td>' +
            '<td>' + (a.contact_number || '—') + '</td>' +
            '<td><span class="badge ' + roleClass + '">' + a.role + '</span></td>' +
            '<td>' + (a.branch_name || '—') + '</td>' +
            '<td><span class="badge ' + activeClass + '">' + activeLabel + '</span></td>' +
            '<td>' + date + '</td>' +
            '<td>' +
                '<button class="btn btn-outline btn-icon btn-sm" onclick=\'openEditModal(' + JSON.stringify(a) + ')\' title="Edit"><i class="fas fa-edit"></i></button> ' +
                '<button class="btn btn-warning btn-icon btn-sm" onclick=\'openPasswordResetModal(' + a.user_id + ', "' + safeName + '")\' title="Reset Password"><i class="fas fa-key"></i></button>' +
            '</td>' +
        '</tr>';
    }).join('');

    pagination.updateTotalItems(filteredAdmins.length);
}

// ── Search / Filter ──
function filterAdmins() {
    var q    = document.getElementById('adminSearch').value.toLowerCase();
    var role = document.getElementById('roleFilter').value;

    filteredAdmins = adminsData.filter(function(a) {
        var matchRole   = !role || a.role === role;
        var matchSearch = a.username.toLowerCase().includes(q) ||
                          a.email.toLowerCase().includes(q) ||
                          (a.branch_name || '').toLowerCase().includes(q);
        return matchRole && matchSearch;
    });
    pagination.goToPage(1);
    renderAdmins();
}

// ── Populate Branch Dropdown ──
function populateBranchDropdown(selectedId) {
    var sel = document.getElementById('fieldBranch');
    sel.innerHTML = '<option value="">— Select Branch —</option>';
    branchesForDropdown.forEach(function(b) {
        var selected = (selectedId && parseInt(selectedId) === parseInt(b.branch_id)) ? ' selected' : '';
        sel.innerHTML += '<option value="' + b.branch_id + '"' + selected + '>' + b.branch_name + '</option>';
    });
}

// ── Open Add Modal ──
function openAddModal() {
    document.getElementById('modalTitle').textContent             = 'Add Admin';
    document.getElementById('adminForm').reset();
    document.getElementById('editUserId').value                   = '';
    document.getElementById('passwordGroup').style.display        = 'block';
    document.getElementById('fieldPassword').required             = true;
    populateBranchDropdown(null);
    document.getElementById('adminModal').classList.add('open');
}

// ── Open Edit Modal ──
function openEditModal(admin) {
    document.getElementById('modalTitle').textContent             = 'Edit Admin';
    document.getElementById('editUserId').value                   = admin.user_id;
    document.getElementById('fieldUsername').value                = admin.username;
    document.getElementById('fieldEmail').value                   = admin.email;
    document.getElementById('fieldContact').value                 = admin.contact_number || '';
    document.getElementById('fieldRole').value                    = admin.role;
    document.getElementById('fieldStatus').value                  = admin.is_active;
    document.getElementById('passwordGroup').style.display        = 'none';
    document.getElementById('fieldPassword').required             = false;
    populateBranchDropdown(admin.branch_id);
    document.getElementById('adminModal').classList.add('open');
}

// ── Save (Add or Edit) ──
function saveAdmin(e) {
    e.preventDefault();
    var id = document.getElementById('editUserId').value;

    var payload = {
        username:       document.getElementById('fieldUsername').value.trim(),
        email:          document.getElementById('fieldEmail').value.trim(),
        contact_number: document.getElementById('fieldContact').value.trim(),
        role:           document.getElementById('fieldRole').value,
        branch_id:      document.getElementById('fieldBranch').value || null,
        is_active:      parseInt(document.getElementById('fieldStatus').value)
    };

    if (!id) {
        payload.password = document.getElementById('fieldPassword').value;
    }

    var url    = id ? API + 'admins/' + id : API + 'admins';
    var method = id ? 'PUT' : 'POST';

    // Disable save button to prevent double submit
    var saveBtn = document.querySelector('#adminForm button[type="submit"]');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(res) { return res.json(); })
    .then(function(json) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        if (json.success) {
            closeModal('adminModal');
            showToast(json.message, 'success', id ? 'Admin Updated' : 'Admin Added');
            loadAdmins();
        } else {
            showToast(json.message || 'Operation failed.', 'error', 'Error');
        }
    })
    .catch(function(err) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        console.error(err);
        showToast('Network error. Please try again.', 'error', 'Error');
    });
}

// ── Open Delete Modal ──
function openDeleteModal(id, name) {
    deleteTargetId = id;
    document.getElementById('deleteAdminName').textContent = name;
    document.getElementById('deleteModal').classList.add('open');
}

// ── Confirm Delete ──
function confirmDelete() {
    var delBtn = document.querySelector('#deleteModal .btn-danger');
    delBtn.disabled = true;
    delBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';

    fetch(API + 'admins/' + deleteTargetId, { method: 'DELETE' })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            delBtn.disabled = false;
            delBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
            closeModal('deleteModal');
            if (json.success) {
                showToast(json.message, 'success', 'Admin Deleted');
                loadAdmins();
            } else {
                showToast(json.message || 'Delete failed.', 'error', 'Error');
            }
        })
        .catch(function(err) {
            delBtn.disabled = false;
            delBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
            console.error(err);
            showToast('Network error. Please try again.', 'error', 'Error');
        });
}

// ── Open Password Reset Modal ──
function openPasswordResetModal(id, name) {
    document.getElementById('resetAdminId').value = id;
    document.getElementById('resetAdminName').textContent = name;
    document.getElementById('passwordResetForm').reset();
    document.getElementById('passwordResetModal').classList.add('open');
}

// ── Reset Admin Password ──
function resetAdminPassword(e) {
    e.preventDefault();
    
    var adminId = document.getElementById('resetAdminId').value;
    var payload = {
        superadmin_password: document.getElementById('superadminPassword').value,
        new_password: document.getElementById('newAdminPassword').value,
        confirm_password: document.getElementById('confirmNewPassword').value
    };
    
    // Validate form
    if (!payload.superadmin_password || !payload.new_password || !payload.confirm_password) {
        showToast('All fields are required', 'error', 'Validation Error');
        return;
    }
    
    if (payload.new_password.length < 8) {
        showToast('New password must be at least 8 characters long', 'error', 'Validation Error');
        return;
    }
    
    if (payload.new_password !== payload.confirm_password) {
        showToast('New passwords do not match', 'error', 'Validation Error');
        return;
    }
    
    // Disable button to prevent double submit
    var resetBtn = document.querySelector('#passwordResetForm button[type="submit"]');
    var originalText = resetBtn.innerHTML;
    resetBtn.disabled = true;
    resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting…';
    
    fetch(API + 'resetAdminPassword/' + adminId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(res) { return res.json(); })
    .then(function(json) {
        resetBtn.disabled = false;
        resetBtn.innerHTML = originalText;
        
        if (json.success) {
            closeModal('passwordResetModal');
            showToast(json.message, 'success', 'Password Reset');
        } else {
            showToast(json.message || 'Password reset failed.', 'error', 'Error');
        }
    })
    .catch(function(err) {
        resetBtn.disabled = false;
        resetBtn.innerHTML = originalText;
        console.error(err);
        showToast('Network error. Please try again.', 'error', 'Error');
    });
}

// ── Close Modal ──
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// ── Init ──
document.addEventListener('DOMContentLoaded', function() {
    pagination = new Pagination({
        totalItems: 0,
        itemsPerPage: 10,
        currentPage: 1,
        onPageChange: function() { renderAdmins(); }
    });

    loadBranchesForDropdown();
    loadAdmins();

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
});

// A quick regex check is instant
const validateEmail = (email) => {
  return String(email)
    .toLowerCase()
    .match(/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/);
};

// ── Password Toggle (Font Awesome fa-eye / fa-eye-slash) ──────────
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