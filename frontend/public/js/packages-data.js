const PACKAGE_API = '../../backend/public/index.php?url=packages/';

let allPackages = [];
let branchServices = [];
let editingPackageId = null;
let deletingPackageId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadPackages();
    loadBranchServices();
    bindEvents();
});

function bindEvents() {
    document.getElementById('openAddPackageBtn').addEventListener('click', openAddModal);
    document.getElementById('closePackageModal').addEventListener('click', closeModal);
    document.getElementById('cancelPackageBtn').addEventListener('click', closeModal);
    document.getElementById('packageForm').addEventListener('submit', submitPackageForm);

    document.getElementById('searchPackage').addEventListener('input', renderPackages);
    document.getElementById('availabilityFilter').addEventListener('change', renderPackages);

    document.getElementById('packageModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // Delete modal events
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDelete').addEventListener('click', handleDeletePackage);

    document.getElementById('deleteModal').addEventListener('click', function (e) {
        if (e.target === this) closeDeleteModal();
    });
}

function confirmDeletePackage(packageId) {
    deletingPackageId = packageId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deletingPackageId = null;
}

async function handleDeletePackage() {
    if (!deletingPackageId) return;

    try {
        const response = await fetch(PACKAGE_API + 'delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ package_id: deletingPackageId })
        });

        const result = await response.json();

        if (!result.success) {
            Toast.error(result.message || 'Failed to delete package.');
            return;
        }

        closeDeleteModal();
        await loadPackages();
        Toast.success('Package deleted successfully!');
    } catch (error) {
        Toast.error('Failed to delete package.');
        console.error(error);
    }
}

async function loadPackages() {
    try {
        const response = await fetch(PACKAGE_API + 'getAll');
        const result = await response.json();

        if (result.success) {
            allPackages = result.data || [];
            renderPackages();
        } else {
            renderEmpty(result.message || 'Failed to load packages.');
        }
    } catch (error) {
        renderEmpty('Failed to load packages.');
        console.error(error);
    }
}

async function loadBranchServices() {
    try {
        const response = await fetch(PACKAGE_API + 'getBranchServices');
        const result = await response.json();

        if (result.success) {
            branchServices = result.data || [];
            renderServiceOptions();
        } else {
            document.getElementById('serviceList').innerHTML = `
                <div class="service-loading">${escapeHtml(result.message || 'Failed to load services.')}</div>
            `;
        }
    } catch (error) {
        document.getElementById('serviceList').innerHTML = `
            <div class="service-loading">Failed to load services.</div>
        `;
        console.error(error);
    }
}

function renderPackages() {
    const tbody = document.getElementById('packagesTableBody');
    const search = document.getElementById('searchPackage').value.trim().toLowerCase();
    const availability = document.getElementById('availabilityFilter').value;

    const filtered = allPackages.filter(pkg => {
        const matchSearch =
            !search ||
            (pkg.package_name && pkg.package_name.toLowerCase().includes(search)) ||
            (pkg.included_services && pkg.included_services.toLowerCase().includes(search));

        const matchAvailability =
            availability === 'all' || String(pkg.is_available) === availability;

        return matchSearch && matchAvailability;
    });

    if (!filtered.length) {
        renderEmpty('No packages found.');
        return;
    }

    tbody.innerHTML = filtered.map(pkg => {
        const tags = (pkg.included_services || '')
            .split(',')
            .map(item => item.trim())
            .filter(Boolean)
            .map(item => `<span class="service-tag">${escapeHtml(item)}</span>`)
            .join('');

        return `
            <tr>
                <td>#${pkg.package_id}</td>
                <td>
                    <strong>${escapeHtml(pkg.package_name)}</strong><br>
                    <small>${escapeHtml(pkg.description || '')}</small>
                </td>
                <td><div class="service-tags">${tags || '<span class="service-tag">No services</span>'}</div></td>
                <td>₱${Number(pkg.package_price).toFixed(2)}</td>
                <td>${Number(pkg.total_duration_minutes || 0)} mins</td>
                <td>
                    <span class="status-badge ${String(pkg.is_available) === '1' ? 'status-active' : 'status-inactive'}">
                        ${String(pkg.is_available) === '1' ? 'Available' : 'Unavailable'}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn edit-btn" onclick="openEditModal(${pkg.package_id})" title="Edit Package">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="action-btn delete-btn" onclick="confirmDeletePackage(${pkg.package_id})" title="Delete Package">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderEmpty(message) {
    document.getElementById('packagesTableBody').innerHTML = `
        <tr>
            <td colspan="7" class="empty-cell">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>${escapeHtml(message)}</p>
                    <span class="subtitle">Try adjusting your filters</span>
                </div>
            </td>
        </tr>
    `;
}

function renderServiceOptions(selectedIds = []) {
    const container = document.getElementById('serviceList');

    if (!branchServices.length) {
        container.innerHTML = `<div class="service-loading">No available branch services found.</div>`;
        return;
    }

    container.innerHTML = branchServices.map(service => {
        const checked = selectedIds.includes(Number(service.branch_service_override_id)) ? 'checked' : '';
        return `
            <div class="service-item">
                <label>
                    <input
                        type="checkbox"
                        class="service-checkbox"
                        value="${service.branch_service_override_id}"
                        data-duration="${service.duration_minutes}"
                        ${checked}
                    >
                    <div>
                        <div class="service-item-title">${escapeHtml(service.service_name)}</div>
                        <div class="service-item-meta">
                            ${Number(service.duration_minutes)} mins • ₱${Number(service.service_price).toFixed(2)}
                        </div>
                    </div>
                </label>
            </div>
        `;
    }).join('');

    document.querySelectorAll('.service-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateDurationPreview);
    });

    updateDurationPreview();
}

function openAddModal() {
    editingPackageId = null;
    document.getElementById('modalTitle').textContent = 'Add Package';
    document.getElementById('packageForm').reset();
    document.getElementById('packageId').value = '';
    document.getElementById('packageFormError').style.display = 'none';
    renderServiceOptions([]);
    document.getElementById('packageModal').style.display = 'flex';
}

async function openEditModal(packageId) {
    editingPackageId = packageId;
    document.getElementById('modalTitle').textContent = 'Edit Package';
    document.getElementById('packageFormError').style.display = 'none';

    try {
        const response = await fetch(PACKAGE_API + 'getOne/' + packageId);
        const result = await response.json();

        if (!result.success) {
            Toast.error(result.message || 'Failed to load package.');
            return;
        }

        const pkg = result.data;
        document.getElementById('packageId').value = pkg.package_id;
        document.getElementById('packageName').value = pkg.package_name || '';
        document.getElementById('packageDescription').value = pkg.description || '';
        document.getElementById('packagePrice').value = pkg.package_price || '';
        document.getElementById('packageAvailability').value = String(pkg.is_available);
        renderServiceOptions((pkg.selected_services || []).map(Number));
        document.getElementById('packageModal').style.display = 'flex';
    } catch (error) {
        Toast.error('Failed to load package.');
        console.error(error);
    }
}

function closeModal() {
    document.getElementById('packageModal').style.display = 'none';
}

function getSelectedServiceIds() {
    return Array.from(document.querySelectorAll('.service-checkbox:checked'))
        .map(cb => Number(cb.value));
}

function updateDurationPreview() {
    const selected = Array.from(document.querySelectorAll('.service-checkbox:checked'));
    const totalDuration = selected.reduce((sum, cb) => sum + Number(cb.dataset.duration || 0), 0);

    document.getElementById('selectedCount').textContent = `${selected.length} selected`;
    document.getElementById('totalDurationPreview').textContent = `${totalDuration} minutes`;
}

async function submitPackageForm(e) {
    e.preventDefault();

    const selectedServices = getSelectedServiceIds();
    const errorBox = document.getElementById('packageFormError');

    if (selectedServices.length < 2) {
        errorBox.textContent = 'A package must contain at least 2 services.';
        errorBox.style.display = 'block';
        return;
    }

    errorBox.style.display = 'none';

    const payload = {
        package_id: document.getElementById('packageId').value,
        package_name: document.getElementById('packageName').value.trim(),
        description: document.getElementById('packageDescription').value.trim(),
        package_price: document.getElementById('packagePrice').value,
        is_available: document.getElementById('packageAvailability').value,
        service_ids: selectedServices
    };

    const endpoint = editingPackageId ? 'update' : 'create';

    try {
        const response = await fetch(PACKAGE_API + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (!result.success) {
            errorBox.textContent = result.message || 'Failed to save package.';
            errorBox.style.display = 'block';
            return;
        }

        closeModal();
        await loadPackages();
        Toast.success(editingPackageId ? 'Package updated successfully!' : 'Package created successfully!');
    } catch (error) {
        errorBox.textContent = 'Failed to save package.';
        errorBox.style.display = 'block';
        console.error(error);
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
