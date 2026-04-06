const API = '../../backend/public/index.php?url=superadmin/';

let servicesData = [];
let deactivatedServicesData = [];
let filteredServices = [];
let branchesForDropdown = [];
let deleteTargetId   = null;
let pagination;

// ── Load services from API ──
function loadServices() {
    fetch(API + 'services')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                servicesData     = json.data;
                filteredServices = [...servicesData];
                pagination.updateTotalItems(filteredServices.length);
                renderServices();
            } else {
                showToast(json.message || 'Failed to load services.', 'error', 'Error');
            }
        })
        .catch(function(err) {
            console.error(err);
            showToast('Network error loading services.', 'error', 'Error');
        });
}

// ── Load Deactivated Services ──
function loadDeactivatedServices() {
    fetch(API + 'services/deactivated')
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.success) {
                deactivatedServicesData = res.data;
            } else {
                console.error('Failed to load deactivated services:', res.message);
            }
        })
        .catch(function(err) { console.error('Error loading deactivated services:', err); });
}

// ── Load branches for dropdown ──
function loadBranchesForDropdown() {
    fetch(API + 'branches')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                branchesForDropdown = json.data;
                populateBranchCheckboxes();
            }
        })
        .catch(function(err) { console.error(err); });
}

// ── Render Table ──
function renderServices() {
    var tbody   = document.getElementById('services-tbody');
    var countEl = document.getElementById('service-count');
    countEl.textContent = filteredServices.length + ' record' + (filteredServices.length !== 1 ? 's' : '');

    if (!filteredServices.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fas fa-concierge-bell"></i><p>No services found.</p></div></td></tr>';
        pagination.updateTotalItems(0);
        return;
    }

    var range = pagination.getCurrentPageRange();
    var slice = filteredServices.slice(range.start, range.end);

    tbody.innerHTML = slice.map(function(s, idx) {
        var date        = new Date(s.created_at).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
        var activeClass = parseInt(s.is_active) === 1 ? 'badge-active' : 'badge-inactive';
        var activeLabel = parseInt(s.is_active) === 1 ? 'Active' : 'Inactive';
        var safeName    = s.service_name.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
        var price       = parseFloat(s.base_price).toFixed(2);
        var branches    = s.branch_names ? s.branch_names.split(',') : ['All Branches'];
        var branchDisplay = branches.length > 3 ? branches.slice(0, 3).join(', ') + ' +' + (branches.length - 3) : branches.join(', ');
        
        return '<tr>' +
            '<td>' + (range.start + idx + 1) + '</td>' +
            '<td><strong>' + s.service_name + '</strong></td>' +
            '<td>' + (s.description || '—') + '</td>' +
            '<td>₱' + price + '</td>' +
            '<td>' + s.duration_minutes + ' min</td>' +
            '<td>' + (s.category_name || 'Category ' + s.category_id) + '</td>' +
            '<td><small>' + branchDisplay + '</small></td>' +
            '<td><span class="badge ' + activeClass + '">' + activeLabel + '</span></td>' +
            '<td>' + date + '</td>' +
            '<td>' +
                '<button class="btn btn-outline btn-icon btn-sm" onclick=\'openEditModal(' + JSON.stringify(s) + ')\' title="Edit"><i class="fas fa-edit"></i></button>' +
            '</td>' +
        '</tr>';
    }).join('');

    pagination.updateTotalItems(filteredServices.length);
}

// ── Search / Filter ──
function filterServices() {
    var q      = document.getElementById('serviceSearch').value.toLowerCase();
    var status = document.getElementById('statusFilter').value;

    filteredServices = servicesData.filter(function(s) {
        var matchStatus = status === '' || parseInt(s.is_active) === parseInt(status);
        var matchSearch = s.service_name.toLowerCase().includes(q) ||
                         (s.description || '').toLowerCase().includes(q) ||
                         (s.category_name || '').toLowerCase().includes(q);
        return matchStatus && matchSearch;
    });
    pagination.goToPage(1);
    renderServices();
}

// ── Populate Branch Checkboxes ──
function populateBranchCheckboxes() {
    var container = document.getElementById('branchCheckboxes');
    if (container) {
        container.innerHTML = '';
        
        branchesForDropdown.forEach(function(branch) {
            var checkboxItem = document.createElement('div');
            checkboxItem.className = 'checkbox-option';
            checkboxItem.innerHTML = 
                '<input type="checkbox" id="branch_' + branch.branch_id + '" value="' + branch.branch_id + '">' +
                '<label for="branch_' + branch.branch_id + '">' + branch.branch_name + '</label>';
            container.appendChild(checkboxItem);
        });
    }

    // Also populate reactivation checkboxes
    var reactivateContainer = document.getElementById('reactivateBranchCheckboxes');
    if (reactivateContainer) {
        reactivateContainer.innerHTML = '';
        
        branchesForDropdown.forEach(function(branch) {
            var checkboxItem = document.createElement('div');
            checkboxItem.className = 'checkbox-option';
            checkboxItem.innerHTML = 
                '<input type="checkbox" id="reactivate_branch_' + branch.branch_id + '" value="' + branch.branch_id + '">' +
                '<label for="reactivate_branch_' + branch.branch_id + '">' + branch.branch_name + '</label>';
            reactivateContainer.appendChild(checkboxItem);
        });
    }
}

// ── Get Selected Branch IDs ──
function getSelectedBranchIds() {
    var checkboxes = document.querySelectorAll('#branchCheckboxes input[type="checkbox"]:checked');
    return Array.from(checkboxes).map(function(cb) { return parseInt(cb.value); });
}

// ── Set Selected Branches ──
function setSelectedBranches(branchIds) {
    // Clear all checkboxes first
    document.querySelectorAll('#branchCheckboxes input[type="checkbox"]').forEach(function(cb) {
        cb.checked = false;
    });
    
    // Check the specified branches
    if (branchIds && branchIds.length > 0) {
        branchIds.forEach(function(branchId) {
            var checkbox = document.getElementById('branch_' + branchId);
            if (checkbox) checkbox.checked = true;
        });
    }
}

// ── Populate Categories Dropdown ──
function populateCategoriesDropdown() {
    var modalSel = document.getElementById('fieldCategory');
    modalSel.innerHTML = '<option value="1">Hair</option>';
    modalSel.innerHTML += '<option value="2">Massage</option>';
    modalSel.innerHTML += '<option value="3">Nail</option>';
    modalSel.innerHTML += '<option value="4">Facial</option>';
}

// ── Open Add Modal ──
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Service';
    document.getElementById('serviceForm').reset();
    document.getElementById('editServiceId').value = '';
    populateCategoriesDropdown();
    populateBranchCheckboxes();
    
    // Show reactivation section if there are deactivated services
    if (deactivatedServicesData && deactivatedServicesData.length > 0) {
        populateDeactivatedServicesList();
        document.getElementById('reactivateSection').style.display = 'block';
    } else {
        document.getElementById('reactivateSection').style.display = 'none';
    }
    
    document.getElementById('serviceModal').classList.add('open');
}

// ── Populate Deactivated Services List ──
function populateDeactivatedServicesList() {
    var container = document.getElementById('deactivatedServicesList');
    container.innerHTML = '';
    
    if (!deactivatedServicesData || deactivatedServicesData.length === 0) {
        container.innerHTML = '<p class="text-muted">No deactivated services available.</p>';
        return;
    }
    
    deactivatedServicesData.forEach(function(service) {
        var item = document.createElement('div');
        item.className = 'deactivated-service-item';
        item.innerHTML = 
            '<input type="radio" name="reactivate_service" id="reactivate_' + service.service_id + '" value="' + service.service_id + '" onchange="handleServiceSelection(' + service.service_id + ')">' +
            '<label for="reactivate_' + service.service_id + '">' +
                '<strong>' + service.service_name + '</strong><br>' +
                '<small class="text-muted">' + (service.description || 'No description') + '</small><br>' +
                '<small>Category: ' + (service.category_name || 'N/A') + ' | Price: ₱' + parseFloat(service.base_price).toFixed(2) + '</small>' +
            '</label>';
        container.appendChild(item);
    });
}

// ── Handle Reactivate Selection ──
function handleReactivateSelection() {
    // Show the deactivated services list
    document.getElementById('deactivatedServicesList').style.display = 'block';
    
    // Hide new service fields and show reactivation fields
    document.getElementById('newServiceFields').style.display = 'none';
    document.getElementById('reactivateFields').style.display = 'block';
    
    // Wait for user to select a specific service
    // The actual reactivation will be handled in saveService()
}

// ── Handle New Service Selection ──
function handleNewServiceSelection() {
    // Hide the deactivated services list
    document.getElementById('deactivatedServicesList').style.display = 'none';
    
    // Show new service form and hide reactivation fields
    document.getElementById('newServiceFields').style.display = 'block';
    document.getElementById('reactivateFields').style.display = 'none';
    
    // Clear reactivation selection
    var selectedRadio = document.querySelector('input[name="reactivate_service"]:checked');
    if (selectedRadio) {
        selectedRadio.checked = false;
    }
    
    // Reset form
    document.getElementById('serviceForm').reset();
    document.getElementById('editServiceId').value = '';
}

// ── Handle Service Selection for Reactivation ──
function handleServiceSelection(reactivateId) {
    var service = deactivatedServicesData.find(function(s) { return s.service_id === reactivateId; });
    if (service) {
        // Populate form with deactivated service data
        document.getElementById('fieldServiceName').value = service.service_name;
        document.getElementById('fieldDescription').value = service.description || '';
        document.getElementById('fieldBasePrice').value = service.base_price;
        document.getElementById('fieldDuration').value = service.duration_minutes;
        document.getElementById('fieldCategory').value = service.category_id || 1;
        document.getElementById('fieldStatus').value = 1; // Always reactivate as active
        
        // Populate branch checkboxes for reactivation
        populateReactivateBranchCheckboxes();
    }
}

// ── Populate Reactivate Branch Checkboxes ──
function populateReactivateBranchCheckboxes() {
    var container = document.getElementById('reactivateBranchCheckboxes');
    container.innerHTML = '';
    
    branchesForDropdown.forEach(function(branch) {
        var checkboxItem = document.createElement('div');
        checkboxItem.className = 'branch-checkbox-item';
        checkboxItem.innerHTML = 
            '<input type="checkbox" id="reactivate_branch_' + branch.branch_id + '" value="' + branch.branch_id + '">' +
            '<label for="reactivate_branch_' + branch.branch_id + '">' + branch.branch_name + '</label>';
        container.appendChild(checkboxItem);
    });
}

// ── Open Edit Modal ──
function openEditModal(service) {
    document.getElementById('modalTitle').textContent = 'Edit Service';
    document.getElementById('editServiceId').value = service.service_id;
    document.getElementById('fieldServiceName').value = service.service_name;
    document.getElementById('fieldDescription').value = service.description || '';
    document.getElementById('fieldBasePrice').value = service.base_price;
    document.getElementById('fieldDuration').value = service.duration_minutes;
    document.getElementById('fieldCategory').value = service.category_id || 1;
    document.getElementById('fieldStatus').value = service.is_active;
    populateCategoriesDropdown();
    populateBranchCheckboxes();
    
    // Set selected branches - handle empty or null branch_ids
    var branchIds = [];
    if (service.branch_ids && service.branch_ids.trim() !== '') {
        branchIds = service.branch_ids.split(',').map(function(id) { 
            return parseInt(id.trim()); 
        }).filter(function(id) { 
            return !isNaN(id); 
        });
    }
    setSelectedBranches(branchIds);
    
    document.getElementById('serviceModal').classList.add('open');
}

// ── Save (Add or Edit) ──
function saveService(e) {
    e.preventDefault();
    
    var id = document.getElementById('editServiceId').value;
    var selectedRadio = document.querySelector('input[name="reactivate_service"]:checked');
    var reactivateId = selectedRadio ? parseInt(selectedRadio.value) : null;
    var isReactivation = document.getElementById('reactivateFields').style.display !== 'none';

    // Get selected branch IDs (same pattern as categories)
    const branchCheckboxes = document.querySelectorAll('#branchCheckboxes input[type="checkbox"]:checked');
    const branchIds = Array.from(branchCheckboxes).map(cb => cb.value);

    const formData = {
        service_name:     document.getElementById('fieldServiceName').value.trim(),
        description:      document.getElementById('fieldDescription').value.trim(),
        base_price:      parseFloat(document.getElementById('fieldBasePrice').value) || 0,
        duration_minutes: parseInt(document.getElementById('fieldDuration').value) || 30,
        category_id:     document.getElementById('fieldCategory').value || 1,
        is_active:       parseInt(document.getElementById('fieldStatus').value),
        branch_ids:      isReactivation ? getReactivateSelectedBranchIds() : branchIds
    };

    // Add reactivate_id if reactivating
    if (reactivateId) {
        formData.reactivate_id = reactivateId;
    }

    const url = id 
        ? API + 'services/' + id
        : API + 'services';
    
    const method = id ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            closeModal('serviceModal');
            loadServices();
            loadDeactivatedServices(); // Refresh deactivated services list
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving service:', error);
        showToast('Error saving service', 'error');
    });
}

// ── Get Reactivate Selected Branch IDs ──
function getReactivateSelectedBranchIds() {
    var checkboxes = document.querySelectorAll('#reactivateBranchCheckboxes input[type="checkbox"]:checked');
    return Array.from(checkboxes).map(function(cb) { return parseInt(cb.value); });
}

// ── Open Delete Modal ──
function openDeleteModal(id, name) {
deleteTargetId = id;
document.getElementById('deleteServiceName').textContent = name;
document.getElementById('deleteModal').classList.add('open');
}

// ── Confirm Delete ──
function confirmDelete() {
var delBtn = document.querySelector('#deleteModal .btn-danger');
delBtn.disabled = true;
delBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';

fetch(API + 'services/' + deleteTargetId, { method: 'DELETE' })
    .then(function(res) { return res.json(); })
    .then(function(json) {
        delBtn.disabled = false;
        delBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
        closeModal('deleteModal');
        if (json.success) {
            showToast(json.message, 'success', 'Service Deleted');
            loadServices();
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

// ── Initialize ──
document.addEventListener('DOMContentLoaded', function() {
    pagination = new Pagination({
        totalItems: 0,
        itemsPerPage: 10,
        currentPage: 1,
        onPageChange: function() { renderServices(); }
    });

    loadBranchesForDropdown();
    loadServices();
    loadDeactivatedServices();

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
});

// ── Close Modal ──
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}