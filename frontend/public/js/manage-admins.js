// ── Mock Data — mirrors `users` table (admin/cashier rows) ──
let adminsData = [
    {
        user_id: 1,
        username: 'cal branch admin',
        email: 'admin@admin',
        contact_number: '',
        password: '',
        role: 'admin',
        branch_id: 1,
        branch_name: 'Caloocan Branch',
        is_active: 1,
        deleted_at: null,
        created_at: '2026-01-09 11:42:55'
    },
    {
        user_id: 7,
        username: 'cal branch cashier',
        email: 'cashier@gmail.com',
        contact_number: '12342141',
        password: '',
        role: 'cashier',
        branch_id: 1,
        branch_name: 'Caloocan Branch',
        is_active: 1,
        deleted_at: null,
        created_at: '2026-02-20 14:00:32'
    }
];

// ── Mock Data — mirrors `branch` table ──
const branchesData = [
    {
        branch_id: 1,
        branch_name: 'Caloocan Branch',
        branch_location: '102 Caimito Rd., Caloocan City',
        contact_number: '09054543104',
        opening_time: '10:00:00',
        closing_time: '21:00:00',
        down_payment_rate: 0.5,
        email: 'hfabscal@gmail.com',
        status: 'active'
    },
    {
        branch_id: 2,
        branch_name: 'Quezon City Branch',
        branch_location: '850 Atherton, Quezon City',
        contact_number: '0946 178 23',
        opening_time: '08:00:00',
        closing_time: '20:00:00',
        down_payment_rate: 0.5,
        email: 'hfabsqc@gmail.com',
        status: 'active'
    }
];

let deleteTargetId = null;
let filteredAdmins = [...adminsData];
let pagination;

// ── Render Table ──
function renderAdmins() {
    const tbody = document.getElementById('admins-tbody');
    const countEl = document.getElementById('admin-count');
    countEl.textContent = filteredAdmins.length + ' record' + (filteredAdmins.length !== 1 ? 's' : '');

    if (!filteredAdmins.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No admin accounts found.</p></div></td></tr>';
        pagination.updateTotalItems(0);
        return;
    }

    // Get current page range from pagination
    const range = pagination.getCurrentPageRange();
    const adminsToDisplay = filteredAdmins.slice(range.start, range.end);

    tbody.innerHTML = adminsToDisplay.map(function (a) {
        const date = new Date(a.created_at).toLocaleDateString('en-PH', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
        const roleClass = a.role === 'admin' ? 'badge-admin' : 'badge-cashier';
        return '<tr>' +
            '<td>' + a.user_id + '</td>' +
            '<td><strong>' + a.username + '</strong></td>' +
            '<td>' + a.email + '</td>' +
            '<td>' + (a.contact_number || '—') + '</td>' +
            '<td><span class="badge ' + roleClass + '">' + a.role + '</span></td>' +
            '<td>' + (a.branch_name || '—') + '</td>' +
            '<td><span class="badge ' + (a.is_active ? 'badge-active' : 'badge-inactive') + '">' + (a.is_active ? 'Active' : 'Inactive') + '</span></td>' +
            '<td>' + date + '</td>' +
            '<td>' +
                '<button class="btn btn-outline btn-icon btn-sm" onclick=\'openEditModal(' + JSON.stringify(a) + ')\' title="Edit"><i class="fas fa-edit"></i></button> ' +
                '<button class="btn btn-danger btn-icon btn-sm" onclick=\'openDeleteModal(' + a.user_id + ', "' + a.username + '")\' title="Delete"><i class="fas fa-trash-alt"></i></button>' +
            '</td>' +
        '</tr>';
    }).join('');

    // Update pagination
    pagination.updateTotalItems(filteredAdmins.length);
}

// ── Search / Filter ──
function filterAdmins() {
    const q    = document.getElementById('adminSearch').value.toLowerCase();
    const role = document.getElementById('roleFilter').value;

    filteredAdmins = adminsData.filter(function (a) {
        const matchRole   = !role || a.role === role;
        const matchSearch = a.username.toLowerCase().includes(q) ||
                            a.email.toLowerCase().includes(q) ||
                            (a.branch_name || '').toLowerCase().includes(q);
        return matchRole && matchSearch;
    });
    pagination.goToPage(1); // Reset to first page
    renderAdmins();
}

// ── Populate Branch Dropdown ──
function populateBranchDropdown() {
    const sel = document.getElementById('fieldBranch');
    sel.innerHTML = '<option value="">— Select Branch —</option>';
    branchesData.forEach(function (b) {
        sel.innerHTML += '<option value="' + b.branch_id + '">' + b.branch_name + '</option>';
    });
}

// ── Open Add Modal ──
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Admin';
    document.getElementById('adminForm').reset();
    document.getElementById('editUserId').value = '';
    document.getElementById('passwordGroup').style.display = 'block';
    populateBranchDropdown();
    document.getElementById('adminModal').classList.add('open');
}

// ── Open Edit Modal ──
function openEditModal(admin) {
    document.getElementById('modalTitle').textContent = 'Edit Admin';
    document.getElementById('editUserId').value     = admin.user_id;
    document.getElementById('fieldUsername').value  = admin.username;
    document.getElementById('fieldEmail').value     = admin.email;
    document.getElementById('fieldContact').value   = admin.contact_number;
    document.getElementById('fieldRole').value      = admin.role;
    document.getElementById('fieldStatus').value    = admin.is_active;
    document.getElementById('passwordGroup').style.display = 'none';
    populateBranchDropdown();
    document.getElementById('fieldBranch').value    = admin.branch_id || '';
    document.getElementById('adminModal').classList.add('open');
}

// ── Save (Add or Edit) ──
function saveAdmin(e) {
    e.preventDefault();
    const id       = document.getElementById('editUserId').value;
    const branchId = parseInt(document.getElementById('fieldBranch').value) || null;
    const branch   = branchesData.find(function (b) { return b.branch_id === branchId; });

    if (id) {
        const idx = adminsData.findIndex(function (a) { return a.user_id == id; });
        if (idx > -1) {
            adminsData[idx].username       = document.getElementById('fieldUsername').value;
            adminsData[idx].email          = document.getElementById('fieldEmail').value;
            adminsData[idx].contact_number = document.getElementById('fieldContact').value;
            adminsData[idx].role           = document.getElementById('fieldRole').value;
            adminsData[idx].branch_id      = branchId;
            adminsData[idx].branch_name    = branch ? branch.branch_name : '';
            adminsData[idx].is_active      = parseInt(document.getElementById('fieldStatus').value);
        }
    } else {
        adminsData.push({
            user_id:        Date.now(),
            username:       document.getElementById('fieldUsername').value,
            email:          document.getElementById('fieldEmail').value,
            contact_number: document.getElementById('fieldContact').value,
            password:       '',
            role:           document.getElementById('fieldRole').value,
            branch_id:      branchId,
            branch_name:    branch ? branch.branch_name : '',
            is_active:      parseInt(document.getElementById('fieldStatus').value),
            deleted_at:     null,
            created_at:     new Date().toISOString()
        });
    }

    closeModal('adminModal');
    filterAdmins(); // Reapply filters to update displayed data
}

// ── Open Delete Modal ──
function openDeleteModal(id, name) {
    deleteTargetId = id;
    document.getElementById('deleteAdminName').textContent = name;
    document.getElementById('deleteModal').classList.add('open');
}

// ── Confirm Delete ──
function confirmDelete() {
    adminsData = adminsData.filter(function (a) { return a.user_id !== deleteTargetId; });
    closeModal('deleteModal');
    filterAdmins(); // Reapply filters to update displayed data
}

// ── Close Any Modal ──
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// ── Init ──
document.addEventListener('DOMContentLoaded', function () {
    // Initialize pagination
    pagination = new Pagination({
        totalItems: filteredAdmins.length,
        itemsPerPage: 10,
        currentPage: 1,
        onPageChange: function(page, itemsPerPage) {
            renderAdmins();
        }
    });

    renderAdmins();

    // Close modal on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
});
