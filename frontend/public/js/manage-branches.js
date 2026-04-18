const API = '../../backend/public/index.php?url=superadmin/';

let branchesData         = [];
let deleteBranchTargetId = null;
let filteredBranches     = [];
let pagination;

// ── Format time to 12-hr ──
function formatTime(t) {
    if (!t) return '—';
    var parts = t.split(':');
    var hr    = parseInt(parts[0]);
    var min   = parts[1];
    return (hr % 12 || 12) + ':' + min + ' ' + (hr < 12 ? 'AM' : 'PM');
}

// ── Google Maps preview for location input ──────────────────────
function updateLocationPreview(value) {
    var preview = document.getElementById('locationPreview');
    var link    = document.getElementById('locationPreviewLink');

    if (!preview || !link) return; // safety check

    if (!value || !value.trim()) {
        preview.style.display = 'none';
        return;
    }

    var encoded = encodeURIComponent(value.trim());
    link.href   = 'https://www.google.com/maps/search/?api=1&query=' + encoded;
    preview.style.display = 'block';
}

// ── Load branches from API ──
function loadBranches() {
    fetch(API + 'branches')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                branchesData     = json.data;
                filteredBranches = [...branchesData];
                pagination.updateTotalItems(filteredBranches.length);
                renderBranches();
            } else {
                showToast(json.message || 'Failed to load branches.', 'error', 'Error');
            }
        })
        .catch(function(err) {
            console.error(err);
            showToast('Network error loading branches.', 'error', 'Error');
        });
}

// ── Render Table ──
function renderBranches() {
    var tbody   = document.getElementById('branches-tbody');
    var countEl = document.getElementById('branch-count');
    countEl.textContent = filteredBranches.length + ' record' + (filteredBranches.length !== 1 ? 's' : '');

    if (!filteredBranches.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-store-slash"></i><p>No branches found.</p></div></td></tr>';
        pagination.updateTotalItems(0);
        return;
    }

    var range = pagination.getCurrentPageRange();
    var slice = filteredBranches.slice(range.start, range.end);

    tbody.innerHTML = slice.map(function(b, idx) {
        var statusClass = b.status === 'active' ? 'badge-active' : 'badge-inactive';
        var safeName    = b.branch_name.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
        return '<tr>' +
            '<td>' + (range.start + idx + 1) + '</td>' +
            '<td><strong style="text-transform:capitalize;">' + b.branch_name + '</strong></td>' +
            '<td>' + makeMapLink(b.branch_location, { cssClass: 'maps-link maps-link-cell', label: b.branch_location }) + '</td>' +
            '<td>' + (b.contact_number || '—') + '</td>' +
            '<td>' + (b.email || '—') + '</td>' +
            '<td style="font-size:12px;">' + formatTime(b.opening_time) + ' – ' + formatTime(b.closing_time) + '</td>' +
            '<td>' + (parseFloat(b.down_payment_rate) * 100).toFixed(0) + '%</td>' +
            '<td><span class="badge ' + statusClass + '">' + b.status + '</span></td>' +
            '<td>' +
                '<button class="btn btn-outline btn-icon btn-sm" onclick=\'openEditBranchModal(' + JSON.stringify(b) + ')\' title="Edit"><i class="fas fa-edit"></i></button>' +
            '</td>' +
        '</tr>';
    }).join('');

    pagination.updateTotalItems(filteredBranches.length);
}

// ── Live preview of Google Maps link as admin types location ──
function updateLocationPreview(value) {
    var preview = document.getElementById('locationPreview');
    var link    = document.getElementById('locationPreviewLink');

    if (!value || !value.trim()) {
        preview.style.display = 'none';
        return;
    }

    var encoded  = encodeURIComponent(value.trim());
    var url      = 'https://www.google.com/maps/search/?api=1&query=' + encoded;
    link.href    = url;
    preview.style.display = 'block';
}

// ── Search / Filter ──
function filterBranches() {
    var q      = document.getElementById('branchSearch').value.toLowerCase();
    var status = document.getElementById('statusFilter').value;

    filteredBranches = branchesData.filter(function(b) {
        return (!status || b.status === status) &&
               (b.branch_name.toLowerCase().includes(q) || b.branch_location.toLowerCase().includes(q));
    });
    pagination.goToPage(1);
    renderBranches();
}

// ── Open Add Modal ──
function openAddBranchModal() {
    document.getElementById('branchModalTitle').textContent = 'Add Branch';
    document.getElementById('branchForm').reset();
    updateLocationPreview('');
    document.getElementById('locationPreview').style.display = 'none';
    document.getElementById('editBranchId').value = '';
    document.getElementById('branchModal').classList.add('open');
}

// ── Open Edit Modal ──
function openEditBranchModal(b) {
    document.getElementById('branchModalTitle').textContent = 'Edit Branch';
    document.getElementById('editBranchId').value           = b.branch_id;
    document.getElementById('bName').value                  = b.branch_name;
    document.getElementById('bLocation').value              = b.branch_location;
    updateLocationPreview(b.branch_location || '');
    document.getElementById('bContact').value               = b.contact_number || '';
    document.getElementById('bEmail').value                 = b.email || '';
    document.getElementById('bOpenTime').value              = b.opening_time.slice(0, 5);
    document.getElementById('bCloseTime').value             = b.closing_time.slice(0, 5);
    document.getElementById('bDownRate').value              = b.down_payment_rate;
    document.getElementById('bStatus').value                = b.status;
    document.getElementById('branchModal').classList.add('open');
}

// ── Save (Add or Edit) ──
function saveBranch(e) {
    e.preventDefault();
    var id = document.getElementById('editBranchId').value;

    var payload = {
        branch_name:       document.getElementById('bName').value.trim(),
        branch_location:   document.getElementById('bLocation').value.trim(),
        contact_number:    document.getElementById('bContact').value.trim(),
        email:             document.getElementById('bEmail').value.trim(),
        opening_time:      document.getElementById('bOpenTime').value + ':00',
        closing_time:      document.getElementById('bCloseTime').value + ':00',
        down_payment_rate: parseFloat(document.getElementById('bDownRate').value),
        status:            document.getElementById('bStatus').value
    };

    var url    = id ? API + 'branches/' + id : API + 'branches';
    var method = id ? 'PUT' : 'POST';

    var saveBtn = document.querySelector('#branchForm button[type="submit"]');
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
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Branch';
        if (json.success) {
            closeModal('branchModal');
            showToast(json.message, 'success', id ? 'Branch Updated' : 'Branch Added');
            loadBranches();
        } else {
            showToast(json.message || 'Operation failed.', 'error', 'Error');
        }
    })
    .catch(function(err) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Branch';
        console.error(err);
        showToast('Network error. Please try again.', 'error', 'Error');
    });
}

// ── Open Delete Modal ──
function openDeleteBranchModal(id, name) {
    deleteBranchTargetId = id;
    document.getElementById('deleteBranchName').textContent = name;
    document.getElementById('deleteBranchModal').classList.add('open');
}

// ── Confirm Delete ──
function confirmDeleteBranch() {
    var delBtn = document.querySelector('#deleteBranchModal .btn-danger');
    delBtn.disabled = true;
    delBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';

    fetch(API + 'branches/' + deleteBranchTargetId, { method: 'DELETE' })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            delBtn.disabled = false;
            delBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
            closeModal('deleteBranchModal');
            if (json.success) {
                showToast(json.message, 'success', 'Branch Deleted');
                loadBranches();
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
        onPageChange: function() { renderBranches(); }
    });

    loadBranches();

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
});