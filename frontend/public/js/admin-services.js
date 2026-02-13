// ==========================================
// ADMIN SERVICES MANAGEMENT
// ==========================================

// Global Variables
let currentCategory = 'all';
let currentPage = 1;
let defaultServicesData = [];
let servicesData = [];
let categoriesData = [];
let branchCategoriesData = [];
let selectedServiceId = null;
let selectedCategoryId = null;
let isCreateNewMode = false;

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
    fetch(`${API_BASE_URL}=services/index`)
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
    fetch(`${API_BASE_URL}=services/categoriesList`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                categoriesData = result.data;
                populateCategorySelect();
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            showNotification('Failed to load categories', 'error');
        });
}

function loadBranchCategories() {
    const branchId = getCurrentBranchId();
    fetch(`${API_BASE_URL}=services/branchCategories/${branchId}`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                branchCategoriesData = result.data;
            }
        })
        .catch(error => {
            console.error('Error loading branch categories:', error);
            showNotification('Failed to load branch categories', 'error');
        });
}

function loadServices() {
    const branchId = getCurrentBranchId();
    fetch(`${API_BASE_URL}=services/branchServices/${branchId}`)
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
            showNotification('Failed to load services', 'error');
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

    const tableHTML = filteredServices.map(service => `
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
        existingServiceRow.style.display = 'none';
        newServiceFields.style.display = 'block';
    } else {
        existingServiceRow.style.display = 'flex';
        newServiceFields.style.display = 'none';
    }
}

function openAddServiceModal() {
    closeAllModals();
    
    const form = document.getElementById('addServiceForm');
    const createNewCheckbox = document.getElementById('createNewServiceAdd');
    
    form.reset();
    
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
            showNotification('Please select a category', 'error');
            return;
        }
        if (!serviceNameInput.value.trim()) {
            showNotification('Please enter a service name', 'error');
            return;
        }
        if (!serviceDescInput.value.trim()) {
            showNotification('Please enter a description', 'error');
            return;
        }
        if (!servicePriceInput.value) {
            showNotification('Please enter a price', 'error');
            return;
        }
        if (!serviceDurationInput.value) {
            showNotification('Please enter duration', 'error');
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
            showNotification('Please select a service', 'error');
            return;
        }
        
        if (!serviceNameInput.value.trim()) {
            showNotification('Please enter a service name', 'error');
            return;
        }
        
        formData = {
            branch_id: branchId,
            default_service_id: parseInt(defaultServiceId),
            display_name: serviceNameInput.value.trim(),
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
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification('Service created successfully', 'success');
            closeAddServiceModal();
            loadDefaultServices();
            loadServices();
        } else {
            showNotification(result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding service:', error);
        showNotification('Failed to add service', 'error');
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
        showNotification('Invalid service ID', 'error');
        return;
    }
    
    const serviceNameInput = document.getElementById('serviceNameEdit');
    const serviceDescInput = document.getElementById('serviceDescriptionEdit');
    const servicePriceInput = document.getElementById('servicePriceEdit');
    const serviceDurationInput = document.getElementById('serviceDurationEdit');
    const serviceAvailableInput = document.getElementById('serviceAvailableEdit');
    
    // Validate required fields
    if (!serviceNameInput.value.trim()) {
        showNotification('Please enter a service name', 'error');
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
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification('Service updated successfully', 'success');
            closeEditServiceModal();
            loadServices();
        } else {
            showNotification(result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating service:', error);
        showNotification('Failed to update service', 'error');
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
        method: 'DELETE'
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification('Service deleted successfully', 'success');
            closeDeleteModal();
            loadDefaultServices();
            loadServices();
        } else {
            showNotification(result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting service:', error);
        showNotification('Failed to delete service', 'error');
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
        showNotification('Please fill in all required fields', 'error');
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
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification('Category capacity updated successfully', 'success');
            closeEditCapacityModal();
            loadBranchCategories();
        } else {
            showNotification(result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating capacity:', error);
        showNotification('Failed to update capacity', 'error');
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

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        background-color: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
