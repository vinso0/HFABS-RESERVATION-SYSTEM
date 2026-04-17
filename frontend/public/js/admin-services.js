// ==========================================
// ADMIN SERVICES MANAGEMENT
// ==========================================

// Global Variables
let currentCategory = 'all';
let currentPage = 1;
let itemsPerPage = 10;
let defaultServicesData = [];
let servicesData = [];
let categoriesData = [];
let branchCategoriesData = [];
let selectedServiceId = null;
let isCreateNewMode = false;
let servicesPagination;

// API Base URL
const API_BASE_URL = '../../backend/public/index.php?url';

// DOM Elements
const servicesTableBody = document.getElementById('servicesTableBody');
const categoryTabs = document.querySelectorAll('.category-tab');
const addServiceBtn = document.getElementById('addServiceBtn');

// Modal Elements
const addServiceModal = document.getElementById('addServiceModal');
const editServiceModal = document.getElementById('editServiceModal');
const editCapacityModal = document.getElementById('editCapacityModal');
const deleteModal = document.getElementById('deleteModal');

// ==========================================
// INITIALIZATION
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
    loadDefaultServices();
    loadCategories();
    loadBranchCategories();
    loadServices();
});

// ==========================================
// HELPER FUNCTIONS
// ==========================================

// Get current branch ID from URL params, sessionStorage, or default to 1
function getCurrentBranchId() {
    // Check URL params first
    const urlParams = new URLSearchParams(window.location.search);
    const branchParam = urlParams.get('branch');
    if (branchParam) {
        return parseInt(branchParam);
    }
    
    // Check sessionStorage
    const storedBranchId = sessionStorage.getItem('selectedBranchId');
    if (storedBranchId) {
        return parseInt(storedBranchId);
    }
    
    // Default to branch 1 (Caloocan)
    return 1;
}

// ==========================================
// EVENT LISTENERS
// ==========================================

function initializeEventListeners() {
    // Category Tabs
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            categoryTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentCategory = tab.dataset.category;
            currentPage = 1;
            if (servicesPagination) {
                servicesPagination.goToPage(1);
            }
            renderServices();
        });
    });

    // Add Service Button
    addServiceBtn.addEventListener('click', () => openAddServiceModal());

    // Modal Close Buttons - Add Service Modal
    document.getElementById('closeAddModal').addEventListener('click', closeAddServiceModal);
    document.getElementById('cancelAddBtn').addEventListener('click', closeAddServiceModal);

    // Modal Close Buttons - Edit Service Modal
    document.getElementById('closeEditModal').addEventListener('click', closeEditServiceModal);
    document.getElementById('cancelEditBtn').addEventListener('click', closeEditServiceModal);

    // Modal Close Buttons - Edit Capacity Modal
    document.getElementById('closeEditCapacityModal').addEventListener('click', closeEditCapacityModal);
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelEditCapacity').addEventListener('click', closeEditCapacityModal);

    // Form Submits - Add Service
    document.getElementById('addServiceForm').addEventListener('submit', handleAddServiceSubmit);
    // Form Submits - Edit Service
    document.getElementById('editServiceForm').addEventListener('submit', handleEditServiceSubmit);

    // Delete Confirm
    document.getElementById('confirmDelete').addEventListener('click', handleDeleteService);

    // Toggle Create New Service checkbox - Add Modal
    const createNewCheckbox = document.getElementById('createNewServiceAdd');
    if (createNewCheckbox) {
        createNewCheckbox.addEventListener('change', toggleAddServiceMode);
    }

    // Auto-fill form when selecting an existing service
    const serviceSelectAdd = document.getElementById('serviceSelectAdd');
    if (serviceSelectAdd) {
        serviceSelectAdd.addEventListener('change', handleExistingServiceSelect);
    }

    // Click outside modal to close
    [addServiceModal, editServiceModal, editCapacityModal, deleteModal].forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
}

// ==========================================
// DATA LOADING
// ==========================================

function loadDefaultServices() {
    fetch(`${API_BASE_URL}=services/index`, { credentials: 'same-origin' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                defaultServicesData = result.data;
                populateServiceSelects();
            }
        })
        .catch(error => {
            console.error('Error loading default services:', error);
        });
}

function loadCategories() {
    fetch(`${API_BASE_URL}=services/categoriesList`, { credentials: 'same-origin' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                categoriesData = result.data;
                populateCategorySelect();
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            Toast.error('Failed to load categories');
        });
}

function loadBranchCategories() {
    const branchId = getCurrentBranchId();
    fetch(`${API_BASE_URL}=services/branchCategories/${branchId}`, { credentials: 'same-origin' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                branchCategoriesData = result.data;
            }
        })
        .catch(error => {
            console.error('Error loading branch categories:', error);
            Toast.error('Failed to load branch categories');
        });
}

function loadServices() {
    const branchId = getCurrentBranchId();
    fetch(`${API_BASE_URL}=services/branchServices/${branchId}`, { credentials: 'same-origin' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                servicesData = result.data;
                renderServices();
            } else {
                showEmptyState();
            }
        })
        .catch(error => {
            console.error('Error loading services:', error);
            showEmptyState();
            Toast.error('Failed to load services');
        });
}

// ==========================================
// RENDERING FUNCTIONS
// ==========================================

function renderServices() {
    const filteredServices = currentCategory === 'all'
        ? servicesData
        : servicesData.filter(s => s.category_id == currentCategory);

    if (filteredServices.length === 0) {
        showEmptyState();
        return;
    }

    // Initialize pagination if not already initialized
    if (!servicesPagination) {
        servicesPagination = new Pagination({
            totalItems: filteredServices.length,
            itemsPerPage: itemsPerPage,
            currentPage: currentPage,
            onPageChange: function(page, perPage) {
                currentPage = page;
                itemsPerPage = perPage;
                renderServices();
            }
        });
    }

    // Update pagination with current filtered data
    servicesPagination.updateTotalItems(filteredServices.length);

    // Get current page range
    const range = servicesPagination.getCurrentPageRange();
    const pageServices = filteredServices.slice(range.start, range.end);

    const tableHTML = pageServices.map(service => `
        <tr>
            <td>${service.branch_service_override_id}</td>
            <td style="width:48px; padding-left:16px; padding-right:4px;">
                ${service.image_url
                    ? `<img src="${service.image_url}" class="service-thumb" alt="${service.display_name}" loading="lazy" onerror="this.outerHTML='<span class=\\'service-thumb-placeholder\\'><i class=\\'fas fa-image\\'></i></span>'">`
                    : `<span class="service-thumb-placeholder"><i class="fas fa-image"></i></span>`
                }
            </td>
            <td><span class="service-name">${service.display_name}</span></td>
            <td style="padding-left:6px;">
                <span class="category-badge ${getCategoryClass(service.category_id)}">
                    ${getCategoryIcon(service.category_id)}
                    ${service.category}
                </span>
            </td>
            <td>₱${parseFloat(service.price).toFixed(2)}</td>
            <td>${service.duration} mins</td>
            <td style="padding-left:14px;">
                <span class="status-badge ${service.is_available ? 'available' : 'unavailable'}">
                    ${service.is_available ? 'Available' : 'Unavailable'}
                </span>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn-action btn-edit" onclick="editService(${service.branch_service_override_id})" title="Edit Service">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-delete" onclick="confirmDeleteService(${service.branch_service_override_id})" title="Delete Service">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    servicesTableBody.innerHTML = tableHTML;
}

function showEmptyState() {
    servicesTableBody.innerHTML = `
        <tr>
            <td colspan="7">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No services found</p>
                    <p class="subtitle">Add your first service to get started</p>
                </div>
            </td>
        </tr>
    `;
}

function populateServiceSelects() {
    // Add Service Modal
    const serviceSelectAdd = document.getElementById('serviceSelectAdd');
    if (serviceSelectAdd) {
        serviceSelectAdd.innerHTML = '<option value="">Select a service</option>' +
            defaultServicesData.map(service => `
                <option value="${service.service_id}">${service.service_name}</option>
            `).join('');
    }

    // Edit Service Modal
    const serviceSelectEdit = document.getElementById('editServiceSelect');
    if (serviceSelectEdit) {
        serviceSelectEdit.innerHTML = '<option value="">Select a service</option>' +
            defaultServicesData.map(service => `
                <option value="${service.service_id}">${service.service_name}</option>
            `).join('');
    }
}

function populateCategorySelect() {
    const categorySelect = document.getElementById('serviceCategoryAdd');
    if (categorySelect) {
        categorySelect.innerHTML = '<option value="">Select a category</option>' +
            categoriesData.map(cat => `
                <option value="${cat.service_category_id}">${cat.category_name}</option>
            `).join('');
    }
}

// ==========================================
// ADD SERVICE MODAL FUNCTIONS
// ==========================================

function toggleAddServiceMode() {
    const createNewCheckbox = document.getElementById('createNewServiceAdd');
    const existingServiceRow = document.getElementById('existingServiceRowAdd');
    const newServiceFields = document.getElementById('newServiceFieldsAdd');
    
    if (!createNewCheckbox || !existingServiceRow || !newServiceFields) return;
    
    isCreateNewMode = createNewCheckbox.checked;
    
    if (isCreateNewMode) {
        // Clear form fields when switching to create new service mode
        document.getElementById('serviceNameAdd').value = '';
        document.getElementById('serviceDescriptionAdd').value = '';
        document.getElementById('servicePriceAdd').value = '';
        document.getElementById('serviceDurationAdd').value = '';
        
        existingServiceRow.style.display = 'none';
        newServiceFields.style.display = 'block';
    } else {
        existingServiceRow.style.display = 'flex';
        newServiceFields.style.display = 'none';
    }
}

// Auto-fill form when selecting an existing service
function handleExistingServiceSelect() {
    const serviceId = document.getElementById('serviceSelectAdd').value;
    
    if (!serviceId) {
        // Clear form if no service selected
        document.getElementById('serviceNameAdd').value = '';
        document.getElementById('serviceDescriptionAdd').value = '';
        document.getElementById('servicePriceAdd').value = '';
        document.getElementById('serviceDurationAdd').value = '';
        return;
    }
    
    // Find the selected service in defaultServicesData
    const service = defaultServicesData.find(s => s.service_id == serviceId);
    
    if (service) {
        // Auto-fill the form with service details
        document.getElementById('serviceNameAdd').value = service.service_name || '';
        document.getElementById('serviceDescriptionAdd').value = service.description || '';
        document.getElementById('servicePriceAdd').value = service.price || '';
        document.getElementById('serviceDurationAdd').value = service.duration_minutes || '';
    }
}

function openAddServiceModal() {
    closeAllModals();
    
    const form = document.getElementById('addServiceForm');
    const createNewCheckbox = document.getElementById('createNewServiceAdd');
    const serviceSelectAdd = document.getElementById('serviceSelectAdd');
    
    form.reset();
    
    // Reset service select dropdown
    if (serviceSelectAdd) {
        serviceSelectAdd.value = '';
    }
    
    // Reset toggle
    if (createNewCheckbox) {
        createNewCheckbox.checked = false;
        isCreateNewMode = false;
        toggleAddServiceMode();
    }
    
    addServiceModal.classList.add('active');
}

function closeAddServiceModal() {
    addServiceModal.classList.remove('active');
    isCreateNewMode = false;
}

// ==========================================
// EDIT SERVICE MODAL FUNCTIONS
// ==========================================

function editService(serviceId) {
    openEditServiceModal(serviceId);
}

function openEditServiceModal(serviceId) {
    closeAllModals();
    
    const form = document.getElementById('editServiceForm');
    form.reset();
    
    const service = servicesData.find(s => s.branch_service_override_id == serviceId);
    
    if (service) {
        document.getElementById('editBranchServiceId').value  = service.branch_service_override_id;
        document.getElementById('editServiceSelect').value    = service.default_service_id;
        document.getElementById('serviceNameEdit').value      = service.display_name || '';
        document.getElementById('serviceDescriptionEdit').value = service.description || '';
        document.getElementById('servicePriceEdit').value     = service.price || '';
        document.getElementById('serviceDurationEdit').value  = service.duration || '';
        document.getElementById('serviceAvailableEdit').checked = service.is_available;

        // ✅ Populate image preview correctly using the service variable (not undefined selectedService)
        var overrideUrl = service.image_url || null;
        var globalUrl   = service.global_image_url || null; // backend needs to return this too (see note below)
        adminSetImagePreview(overrideUrl, globalUrl);
    }

    editServiceModal.classList.add('active');
}

function closeEditServiceModal() {
    editServiceModal.classList.remove('active');
}

function closeDeleteModal() {
    deleteModal.classList.remove('active');
    selectedServiceId = null;
}

// Helper function to close all modals
function closeAllModals() {
    addServiceModal.classList.remove('active');
    editServiceModal.classList.remove('active');
    editCapacityModal.classList.remove('active');
    deleteModal.classList.remove('active');
}

// ==========================================
// SERVICE OPERATIONS - ADD
// ==========================================

function handleAddServiceSubmit(e) {
    e.preventDefault();
    
    const isCreateNew = isCreateNewMode;
    const branchId = getCurrentBranchId();
    
    let formData;
    let url;
    let method;
    
    if (isCreateNew) {
        // Create new default service AND branch override
        const categorySelect = document.getElementById('serviceCategoryAdd');
        const serviceNameInput = document.getElementById('serviceNameAdd');
        const serviceDescInput = document.getElementById('serviceDescriptionAdd');
        const servicePriceInput = document.getElementById('servicePriceAdd');
        const serviceDurationInput = document.getElementById('serviceDurationAdd');
        const serviceAvailableInput = document.getElementById('serviceAvailableAdd');
        
        // Validate required fields
        if (!categorySelect.value) {
            Toast.error('Please select a category');
            return;
        }
        if (!serviceNameInput.value.trim()) {
            Toast.error('Please enter a service name');
            return;
        }
        if (!serviceDescInput.value.trim()) {
            Toast.error('Please enter a description');
            return;
        }
        if (!servicePriceInput.value) {
            Toast.error('Please enter a price');
            return;
        }
        if (!serviceDurationInput.value) {
            Toast.error('Please enter duration');
            return;
        }
        
        formData = {
            category_id: parseInt(categorySelect.value),
            service_name: serviceNameInput.value.trim(),
            description: serviceDescInput.value.trim(),
            duration_minutes: parseInt(serviceDurationInput.value),
            price: parseFloat(servicePriceInput.value),
            is_available: serviceAvailableInput.checked ? 1 : 0,
            branch_id: branchId
        };
        
        url = `${API_BASE_URL}=services/storeWithBranch`;
        method = 'POST';
    } else {
        // Create branch service override for existing service
        const defaultServiceId = document.getElementById('serviceSelectAdd').value;
        const serviceNameInput = document.getElementById('serviceNameAdd');
        const serviceDescInput = document.getElementById('serviceDescriptionAdd');
        const servicePriceInput = document.getElementById('servicePriceAdd');
        const serviceDurationInput = document.getElementById('serviceDurationAdd');
        const serviceAvailableInput = document.getElementById('serviceAvailableAdd');
        
        if (!defaultServiceId) {
            Toast.error('Please select a service');
            return;
        }
        
        // Service name is optional for overrides - use default if not provided
        formData = {
            branch_id: branchId,
            default_service_id: parseInt(defaultServiceId),
            display_name: serviceNameInput.value.trim() || null,
            description_override: serviceDescInput.value.trim() || null,
            price_override: parseFloat(servicePriceInput.value) || null,
            duration_minutes_override: parseInt(serviceDurationInput.value) || null,
            is_available_override: serviceAvailableInput.checked ? 1 : 0
        };
        
        url = `${API_BASE_URL}=services/branchServiceStore`;
        method = 'POST';
    }
    
    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            // Show success notification (no browser alert to avoid "localhost says" prefix)
            Toast.success('Service created successfully');
            closeAddServiceModal();
            loadDefaultServices();
            loadServices();
        } else {
            // Check for duplicate service error
            const errorMsg = result.message || result.error || 'Operation failed';
            if (errorMsg.includes('already exists') || errorMsg.includes('duplicate')) {
                Toast.error('This service already exists for this branch. Please select a different service or edit the existing one.');
            } else {
                Toast.error(errorMsg);
            }
        }
    })
    .catch(error => {
        console.error('Error adding service:', error);
        Toast.error('Service already exists, please try other services');
    });
}

// ==========================================
// SERVICE OPERATIONS - EDIT
// ==========================================

function handleEditServiceSubmit(e) {
    e.preventDefault();
    
    const branchServiceOverrideId = document.getElementById('editBranchServiceId').value;
    const branchId = getCurrentBranchId();
    
    if (!branchServiceOverrideId) {
        Toast.error('Invalid service ID');
        return;
    }
    
    const serviceNameInput     = document.getElementById('serviceNameEdit');
    const serviceDescInput     = document.getElementById('serviceDescriptionEdit');
    const servicePriceInput    = document.getElementById('servicePriceEdit');
    const serviceDurationInput = document.getElementById('serviceDurationEdit');
    const serviceAvailableInput = document.getElementById('serviceAvailableEdit');

    if (!serviceNameInput.value.trim()) {
        Toast.error('Please enter a service name');
        return;
    }

    // ✅ Use FormData instead of JSON so the image file is included
    const formData = new FormData();
    formData.append('branch_id',                  branchId);
    formData.append('display_name',               serviceNameInput.value.trim());
    formData.append('description_override',       serviceDescInput.value.trim() || '');
    formData.append('price_override',             parseFloat(servicePriceInput.value) || '');
    formData.append('duration_minutes_override',  parseInt(serviceDurationInput.value) || '');
    formData.append('is_available_override',      serviceAvailableInput.checked ? 1 : 0);
    formData.append('_method',                    'PUT'); // ✅ tells PHP this is actually a PUT

    // ✅ Append image file if one was selected
    const imageFileInput = document.getElementById('adminImageFileInput');
    if (imageFileInput && imageFileInput.files[0]) {
        formData.append('service_image', imageFileInput.files[0]);
    }

    // ✅ Append remove flag if user clicked the remove button
    const removeImage = document.getElementById('adminRemoveImage');
    if (removeImage) {
        formData.append('remove_image', removeImage.value);
    }

    const url = `${API_BASE_URL}=services/branchServiceUpdate/${branchServiceOverrideId}`;

    // ✅ POST with FormData — do NOT set Content-Type header (browser sets it with boundary)
    fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            Toast.success('Service updated successfully');
            closeEditServiceModal();
            loadServices();
        } else {
            Toast.error(result.message || 'Operation failed');
        }
    })
    .catch(error => {
        console.error('Error updating service:', error);
        Toast.error('Failed to update service');
    });
}

// ==========================================
// DELETE SERVICE
// ==========================================

function confirmDeleteService(serviceId) {
    selectedServiceId = serviceId;
    deleteModal.classList.add('active');
}

function handleDeleteService() {
    if (!selectedServiceId) return;
    
    const url = `${API_BASE_URL}=services/branchServiceDestroy/${selectedServiceId}`;
    
    fetch(url, {
        method: 'DELETE',
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            Toast.success('Service deleted successfully');
            closeDeleteModal();
            loadDefaultServices();
            loadServices();
        } else {
            Toast.error(result.message || 'Operation failed');
        }
    })
    .catch(error => {
        console.error('Error deleting service:', error);
        Toast.error('Failed to delete service');
    });
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function getCategoryClass(categoryId) {
    const classes = {
        1: 'hair',
        2: 'skin',
        3: 'nails',
        4: 'massage',
        5: 'facial'
    };
    return classes[categoryId] || 'other';
}

function getCategoryIcon(categoryId) {
    const icons = {
        1: '<i class="fas fa-cut"></i>',
        2: '<i class="fas fa-spa"></i>',
        3: '<i class="fas fa-hand-sparkles"></i>',
        4: '<i class="fas fa-hands"></i>',
        5: '<i class="fas fa-face-smile"></i>'
    };
    return icons[categoryId] || '<i class="fas fa-tag"></i>';
}

// ── Branch Service Image Override Handling (Admin) ──

function adminHandleImageSelect(event) {
    var file = event.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        Toast.error('Image too large. Maximum size is 5MB.');
        event.target.value = '';
        return;
    }

    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('adminImagePreview').src              = e.target.result;
        document.getElementById('adminImageBase64').value             = e.target.result;
        document.getElementById('adminImagePreviewBox').style.display = 'block';
        document.getElementById('adminImageUploadLabel').style.display= 'none';
        document.getElementById('adminGlobalImageRef').style.display  = 'none';
        document.getElementById('adminRemoveImage').value             = '0';
    };
    reader.readAsDataURL(file);
}

function adminRemoveImage() {
    document.getElementById('adminImagePreview').src               = '';
    document.getElementById('adminImageBase64').value              = '';
    document.getElementById('adminImagePreviewBox').style.display  = 'none';
    document.getElementById('adminImageUploadLabel').style.display = 'flex';
    document.getElementById('adminImageFileInput').value           = '';
    document.getElementById('adminRemoveImage').value              = '1';

    // Show global fallback again
    var globalRef = document.getElementById('adminGlobalImageRef');
    if (globalRef) globalRef.style.display = 'block';
}

function adminSetImagePreview(overrideUrl, globalUrl) {
    var previewBox   = document.getElementById('adminImagePreviewBox');
    var uploadLabel  = document.getElementById('adminImageUploadLabel');
    var globalRef    = document.getElementById('adminGlobalImageRef');
    var globalThumb  = document.getElementById('adminGlobalImageThumb');

    // Reset
    document.getElementById('adminImageBase64').value = '';
    document.getElementById('adminRemoveImage').value = '0';

    if (overrideUrl) {
        document.getElementById('adminImagePreview').src = overrideUrl;
        previewBox.style.display   = 'block';
        uploadLabel.style.display  = 'none';
        if (globalRef) globalRef.style.display = 'none';
    } else {
        previewBox.style.display   = 'none';
        uploadLabel.style.display  = 'flex';
        if (globalRef && globalUrl) {
            globalThumb.src            = globalUrl;
            globalRef.style.display    = 'block';
        } else if (globalRef) {
            globalRef.style.display    = 'none';
        }
    }
}