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
let selectedCategoryId = null;
let isCreateNewMode = false;
let servicesPagination;

// API Base URL
const API_BASE_URL = '../../backend/public/index.php?url';

// DOM Elements
const servicesTableBody = document.getElementById('servicesTableBody');
const categoryTabs = document.querySelectorAll('.category-tab');
const addServiceBtn = document.getElementById('addServiceBtn');
const manageCategoriesBtn = document.getElementById('manageCategoriesBtn');

// Modal Elements
const addServiceModal = document.getElementById('addServiceModal');
const editServiceModal = document.getElementById('editServiceModal');
const categoriesModal = document.getElementById('categoriesModal');
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

    // Manage Categories Button
    manageCategoriesBtn.addEventListener('click', () => openCategoriesModal());

    // Modal Close Buttons - Add Service Modal
    document.getElementById('closeAddModal').addEventListener('click', closeAddServiceModal);
    document.getElementById('cancelAddBtn').addEventListener('click', closeAddServiceModal);

    // Modal Close Buttons - Edit Service Modal
    document.getElementById('closeEditModal').addEventListener('click', closeEditServiceModal);
    document.getElementById('cancelEditBtn').addEventListener('click', closeEditServiceModal);

    // Modal Close Buttons - Categories Modal
    document.getElementById('closeCategoriesModal').addEventListener('click', closeCategoriesModal);
    document.getElementById('closeCategoriesBtn').addEventListener('click', closeCategoriesModal);
    document.getElementById('closeEditCapacityModal').addEventListener('click', closeEditCapacityModal);
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelEditCapacity').addEventListener('click', closeEditCapacityModal);

    // Form Submits - Add Service
    document.getElementById('addServiceForm').addEventListener('submit', handleAddServiceSubmit);
    // Form Submits - Edit Service
    document.getElementById('editServiceForm').addEventListener('submit', handleEditServiceSubmit);
    document.getElementById('editCapacityForm').addEventListener('submit', handleCapacityUpdate);

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
    [addServiceModal, editServiceModal, categoriesModal, editCapacityModal, deleteModal].forEach(modal => {
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
            <td><span class="service-name">${service.display_name}</span></td>
            <td>
                <span class="category-badge ${getCategoryClass(service.category_id)}">
                    ${getCategoryIcon(service.category_id)}
                    ${service.category}
                </span>
            </td>
            <td>₱${parseFloat(service.price).toFixed(2)}</td>
            <td>${service.duration} mins</td>
            <td>
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

function renderCategoriesList() {
    const categoriesList = document.getElementById('categoriesList');
    if (categoriesList) {
        categoriesList.innerHTML = categoriesData.map(cat => {
            const branchCat = branchCategoriesData.find(bc => bc.default_category_id == cat.service_category_id);
            const capacity = branchCat ? branchCat.capacity : cat.def_capacity;
            
            return `
                <div class="category-item">
                    <div class="category-info">
                        <div class="category-name">${branchCat?.display_name || cat.category_name}</div>
                        <div class="category-details">${branchCat?.description || cat.description}</div>
                    </div>
                    <div class="category-actions">
                        <div class="category-capacity">
                            <i class="fas fa-users"></i>
                            <span>${capacity} capacity</span>
                        </div>
                        <button class="btn-edit-capacity" onclick="openEditCapacityModal(${cat.service_category_id})" title="Edit Capacity">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
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
        document.getElementById('editBranchServiceId').value = service.branch_service_override_id;
        document.getElementById('editServiceSelect').value = service.default_service_id;
        document.getElementById('serviceNameEdit').value = service.display_name || '';
        document.getElementById('serviceDescriptionEdit').value = service.description || '';
        document.getElementById('servicePriceEdit').value = service.price || '';
        document.getElementById('serviceDurationEdit').value = service.duration || '';
        document.getElementById('serviceAvailableEdit').checked = service.is_available;
    }
    
    editServiceModal.classList.add('active');
}

function closeEditServiceModal() {
    editServiceModal.classList.remove('active');
}

// ==========================================
// CATEGORIES MODAL FUNCTIONS
// ==========================================

function openCategoriesModal() {
    closeAllModals();
    
    renderCategoriesList();
    categoriesModal.classList.add('active');
}

function closeCategoriesModal() {
    categoriesModal.classList.remove('active');
}

function openEditCapacityModal(categoryId) {
    closeEditCapacityModal();
    
    selectedCategoryId = categoryId;
    const category = categoriesData.find(c => c.service_category_id == categoryId);
    const branchCat = branchCategoriesData.find(bc => bc.default_category_id == categoryId);
    
    if (!category) return;
    
    // Populate modal
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('editCategoryName').textContent = category.category_name;
    document.getElementById('editCategoryDescription').textContent = category.description;
    document.getElementById('branchCapacity').value = branchCat ? branchCat.capacity : category.def_capacity;
    document.getElementById('defaultCapacityDisplay').textContent = category.def_capacity;
    
    // Set icon
    const iconContainer = document.getElementById('editCategoryIcon');
    const iconClass = getCategoryClass(categoryId);
    iconContainer.className = `category-icon-large ${iconClass}`;
    iconContainer.innerHTML = getCategoryIcon(categoryId);
    
    editCapacityModal.classList.add('active');
}

function closeEditCapacityModal() {
    editCapacityModal.classList.remove('active');
    selectedCategoryId = null;
}

function closeDeleteModal() {
    deleteModal.classList.remove('active');
    selectedServiceId = null;
}

// Helper function to close all modals
function closeAllModals() {
    addServiceModal.classList.remove('active');
    editServiceModal.classList.remove('active');
    categoriesModal.classList.remove('active');
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
    
    const serviceNameInput = document.getElementById('serviceNameEdit');
    const serviceDescInput = document.getElementById('serviceDescriptionEdit');
    const servicePriceInput = document.getElementById('servicePriceEdit');
    const serviceDurationInput = document.getElementById('serviceDurationEdit');
    const serviceAvailableInput = document.getElementById('serviceAvailableEdit');
    
    // Validate required fields
    if (!serviceNameInput.value.trim()) {
        Toast.error('Please enter a service name');
        return;
    }
    
    const formData = {
        branch_id: branchId,
        display_name: serviceNameInput.value.trim(),
        description_override: serviceDescInput.value.trim() || null,
        price_override: parseFloat(servicePriceInput.value) || null,
        duration_minutes_override: parseInt(serviceDurationInput.value) || null,
        is_available_override: serviceAvailableInput.checked ? 1 : 0
    };
    
    const url = `${API_BASE_URL}=services/branchServiceUpdate/${branchServiceOverrideId}`;
    
    fetch(url, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
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
// CAPACITY UPDATE
// ==========================================

function handleCapacityUpdate(e) {
    e.preventDefault();
    
    const categoryId = document.getElementById('editCategoryId').value;
    const capacity = document.getElementById('branchCapacity').value;
    const branchId = getCurrentBranchId();
    
    if (!categoryId || !capacity) {
        Toast.error('Please fill in all required fields');
        return;
    }
    
    const formData = {
        branch_id: branchId,
        default_category_id: parseInt(categoryId),
        capacity_override: parseInt(capacity),
        is_active_override: 1
    };
    
    const url = `${API_BASE_URL}=services/updateCategoryCapacity`;
    
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            Toast.success('Category capacity updated successfully');
            closeEditCapacityModal();
            loadBranchCategories();
        } else {
            Toast.error(result.message || 'Operation failed');
        }
    })
    .catch(error => {
        console.error('Error updating capacity:', error);
        Toast.error('Failed to update capacity');
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
