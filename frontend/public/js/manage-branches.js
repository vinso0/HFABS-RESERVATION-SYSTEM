// ── Mock Data — mirrors `branch` table schema ──
let branchesData = [
    {
        branch_id: 1,
        branch_name: 'caloocan branch',
        branch_location: '102 Caimito Rd., Caloocan City, Unit 1D, Caimito Place',
        contact_number: '09054543104',
        opening_time: '10:00:00',
        closing_time: '21:00:00',
        down_payment_rate: 0.5,
        email: 'hfabscal@gmail.com',
        status: 'active'
    },
    {
        branch_id: 2,
        branch_name: 'quezon city branch',
        branch_location: '850 Atherton, Quezon City',
        contact_number: '0946 178 23',
        opening_time: '08:00:00',
        closing_time: '20:00:00',
        down_payment_rate: 0.5,
        email: 'hfabsqc@gmail.com',
        status: 'active'
    }
];

let deleteBranchTargetId = null;
let filteredBranches = [...branchesData];
let pagination;

// ── Format time to 12-hr ──
function formatTime(t) {
    if (!t) return '—';
    var parts = t.split(':');
    var hr = parseInt(parts[0]);
    var min = parts[1];
    return (hr % 12 || 12) + ':' + min + ' ' + (hr < 12 ? 'AM' : 'PM');
}

// ── Render Table ──
function renderBranches() {
    const tbody   = document.getElementById('branches-tbody');
    const countEl = document.getElementById('branch-count');
    countEl.textContent = filteredBranches.length + ' record' + (filteredBranches.length !== 1 ? 's' : '');

    if (!filteredBranches.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-store-slash"></i><p>No branches found.</p></div></td></tr>';
        pagination.updateTotalItems(0);
        return;
    }

    // Get current page range from pagination
    const range = pagination.getCurrentPageRange();
    const branchesToDisplay = filteredBranches.slice(range.start, range.end);

    tbody.innerHTML = branchesToDisplay.map(function (b) {
        return '<tr>' +
            '<td>' + b.branch_id + '</td>' +
            '<td><strong style="text-transform:capitalize;">' + b.branch_name + '</strong></td>' +
            '<td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + b.branch_location + '">' + b.branch_location + '</td>' +
            '<td>' + (b.contact_number || '—') + '</td>' +
            '<td>' + (b.email || '—') + '</td>' +
            '<td><span style="font-size:12px;">' + formatTime(b.opening_time) + ' – ' + formatTime(b.closing_time) + '</span></td>' +
            '<td>' + (b.down_payment_rate * 100).toFixed(0) + '%</td>' +
            '<td><span class="badge ' + (b.status === 'active' ? 'badge-active' : 'badge-inactive') + '">' + b.status + '</span></td>' +
            '<td>' +
                '<button class="btn btn-outline btn-icon btn-sm" onclick=\'openEditBranchModal(' + JSON.stringify(b) + ')\' title="Edit"><i class="fas fa-edit"></i></button> ' +
                '<button class="btn btn-danger btn-icon btn-sm" onclick=\'openDeleteBranchModal(' + b.branch_id + ', "' + b.branch_name + '")\' title="Delete"><i class="fas fa-trash-alt"></i></button>' +
            '</td>' +
        '</tr>';
    }).join('');

    // Update pagination
    pagination.updateTotalItems(filteredBranches.length);
}

// ── Search / Filter ──
function filterBranches() {
    const q      = document.getElementById('branchSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;

    filteredBranches = branchesData.filter(function (b) {
        return (!status || b.status === status) &&
               (b.branch_name.toLowerCase().includes(q) || b.branch_location.toLowerCase().includes(q));
    });
    pagination.goToPage(1); // Reset to first page
    renderBranches();
}

// ── Open Add Modal ──
function openAddBranchModal() {
    document.getElementById('branchModalTitle').textContent = 'Add Branch';
    document.getElementById('branchForm').reset();
    document.getElementById('editBranchId').value = '';
    document.getElementById('branchModal').classList.add('open');
}

// ── Open Edit Modal ──
function openEditBranchModal(b) {
    document.getElementById('branchModalTitle').textContent = 'Edit Branch';
    document.getElementById('editBranchId').value  = b.branch_id;
    document.getElementById('bName').value         = b.branch_name;
    document.getElementById('bLocation').value     = b.branch_location;
    document.getElementById('bContact').value      = b.contact_number;
    document.getElementById('bEmail').value        = b.email;
    document.getElementById('bOpenTime').value     = b.opening_time.slice(0, 5);
    document.getElementById('bCloseTime').value    = b.closing_time.slice(0, 5);
    document.getElementById('bDownRate').value     = b.down_payment_rate;
    document.getElementById('bStatus').value       = b.status;
    document.getElementById('branchModal').classList.add('open');
}

// ── Save (Add or Edit) ──
function saveBranch(e) {
    e.preventDefault();
    const id = document.getElementById('editBranchId').value;
    const payload = {
        branch_id: id ? parseInt(id) : Date.now(),
        branch_name: document.getElementById('bName').value,
        branch_location: document.getElementById('bLocation').value,
        contact_number: document.getElementById('bContact').value,
        email: document.getElementById('bEmail').value,
        opening_time: document.getElementById('bOpenTime').value + ':00',
        closing_time: document.getElementById('bCloseTime').value + ':00',
        down_payment_rate: parseFloat(document.getElementById('bDownRate').value),
        status: document.getElementById('bStatus').value
    };

    if (id) {
        const idx = branchesData.findIndex(function (b) { return b.branch_id == id; });
        if (idx > -1) {
            branchesData[idx] = payload;
            closeModal('branchModal');
            renderBranches(branchesData);
            showToast('Branch details updated successfully.', 'success', 'Branch Updated');
        }
    } else {
        branchesData.push(payload);
        closeModal('branchModal');
        renderBranches(branchesData);
        showToast('New branch added successfully.', 'success', 'Branch Added');
    }
}


// ── Open Delete Modal ──
function openDeleteBranchModal(id, name) {
    deleteBranchTargetId = id;
    document.getElementById('deleteBranchName').textContent = name;
    document.getElementById('deleteBranchModal').classList.add('open');
}

// ── Confirm Delete ──
function confirmDeleteBranch() {
    branchesData = branchesData.filter(function (b) { return b.branch_id !== deleteBranchTargetId; });
    closeModal('deleteBranchModal');
    renderBranches(branchesData);
    showToast('Branch deleted successfully.', 'success', 'Branch Deleted');
}

// ── Close Modal ──
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// ── Init ──
document.addEventListener('DOMContentLoaded', function () {
    // Initialize pagination
    pagination = new Pagination({
        totalItems: filteredBranches.length,
        itemsPerPage: 10,
        currentPage: 1,
        onPageChange: function(page, itemsPerPage) {
            renderBranches();
        }
    });

    renderBranches();

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
});
