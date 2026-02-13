// ==========================================
// ADMIN SERVICES MANAGEMENT
// ==========================================

// Global Variables
let currentCategory = 'all';
let currentPage = 1;
let servicesData = [];
let categoriesData = [];
let branchCategoriesData = [];
let selectedServiceId = null;
let selectedCategoryId = null;

// ==========================================
// DUMMY DATA (Remove when backend is ready)
// ==========================================

const DUMMY_CATEGORIES = [
    {
        service_category_id: 1,
        category_name: 'Hair',
        description: 'Hair care and styling services',
        def_capacity: 3
    },
    {
        service_category_id: 2,
        category_name: 'Massage',
        description: 'Relaxation and therapeutic massage',
        def_capacity: 5
    },
    {
        service_category_id: 3,
        category_name: 'Nail',
        description: 'Manicure and pedicure services',
        def_capacity: 4
    },
    {
        service_category_id: 4,
        category_name: 'Facial',
        description: 'Facial treatments and skincare',
        def_capacity: 2
    }
];

// Branch-specific category overrides
let DUMMY_BRANCH_CATEGORIES = [
    {
        branch_category_override_id: 1,
        branch_id: 1,
        default_category_id: 1,
        display_name: null,
        description_override: null,
        capacity_override: 3,
        is_active_override: null
    },
    {
        branch_category_override_id: 2,
        branch_id: 1,
        default_category_id: 2,
        display_name: null,
        description_override: null,
        capacity_override: 5,
        is_active_override: null
    },
    {
        branch_category_override_id: 3,
        branch_id: 1,
        default_category_id: 3,
        display_name: null,
        description_override: null,
        capacity_override: 4,
        is_active_override: null
    },
    {
        branch_category_override_id: 4,
        branch_id: 1,
        default_category_id: 4,
        display_name: null,
        description_override: null,
        capacity_override: 2,
        is_active_override: null
    }
];

const DUMMY_SERVICES = [
    {
        branch_service_override_id: 1,
        default_service_id: 1,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 2,
        default_service_id: 2,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 3,
        default_service_id: 3,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 4,
        default_service_id: 4,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 5,
        default_service_id: 5,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 6,
        default_service_id: 6,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 0
    },
    {
        branch_service_override_id: 7,
        default_service_id: 7,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 8,
        default_service_id: 8,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 9,
        default_service_id: 9,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 10,
        default_service_id: 10,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 11,
        default_service_id: 11,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 1
    },
    {
        branch_service_override_id: 12,
        default_service_id: 12,
        branch_id: 1,
        display_name: null,
        description_override: null,
        duration_minutes_override: null,
        price_override: null,
        is_available_override: 0
    }
];

// DOM Elements
const servicesTableBody = document.getElementById('servicesTableBody');
const categoryTabs = document.querySelectorAll('.category-tab');
const addServiceBtn = document.getElementById('addServiceBtn');
const manageCategoriesBtn = document.getElementById('manageCategoriesBtn');

// Modal Elements
const serviceModal = document.getElementById('serviceModal');
const categoriesModal = document.getElementById('categoriesModal');
const editCapacityModal = document.getElementById('editCapacityModal');
const deleteModal = document.getElementById('deleteModal');

// ==========================================
// INITIALIZATION
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
    loadCategories();
    loadBranchCategories();
    loadServices();
    // We'll need to load default services from the backend to populate the service select
});

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
    addServiceBtn.addEventListener('click', () => openServiceModal());

    // Manage Categories Button
    manageCategoriesBtn.addEventListener('click', () => openCategoriesModal());

    // Modal Close Buttons
    document.getElementById('closeModal').addEventListener('click', closeServiceModal);
    document.getElementById('closeCategoriesModal').addEventListener('click', closeCategoriesModal);
    document.getElementById('closeCategoriesBtn').addEventListener('click', closeCategoriesModal);
    document.getElementById('closeEditCapacityModal').addEventListener('click', closeEditCapacityModal);
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelBtn').addEventListener('click', closeServiceModal);
    document.getElementById('cancelEditCapacity').addEventListener('click', closeEditCapacityModal);
    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);

    // Form Submits
    document.getElementById('serviceForm').addEventListener('submit', handleServiceSubmit);
    document.getElementById('editCapacityForm').addEventListener('submit', handleCapacityUpdate);

    // Delete Confirm
    document.getElementById('confirmDelete').addEventListener('click', handleDeleteService);

    // Click outside modal to close
    [serviceModal, categoriesModal, editCapacityModal, deleteModal].forEach(modal => {
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

function loadCategories() {
    // Using dummy data
    categoriesData = DUMMY_CATEGORIES;
    populateCategorySelect();
    
    // When backend is ready, use this:
    /*
    fetch('../../backend/public/index.php?url=services/getCategories')
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                categoriesData = result.data;
                populateCategorySelect();
            }
        })
        .catch(error => console.error('Error loading categories:', error));
    */
}

function loadBranchCategories() {
    // Using dummy data
    branchCategoriesData = DUMMY_BRANCH_CATEGORIES;
    
    // When backend is ready, use this to fetch branch-specific category overrides:
    /*
    const branchId = getCurrentBranchId(); // Assuming this function exists
    fetch(`../../backend/public/index.php/branches/${branchId}/categories`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                branchCategoriesData = result.data;
            }
        })
        .catch(error => console.error('Error loading branch categories:', error));
    */
}

function loadServices() {
    // Using dummy data
    servicesData = DUMMY_SERVICES;
    renderServices();
    
    // When backend is ready, use this to fetch branch-specific service overrides:
    /*
    const branchId = getCurrentBranchId(); // Assuming this function exists
    fetch(`../../backend/public/index.php/branches/${branchId}/services`)
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
        });
    */
}

// ==========================================
// RENDERING FUNCTIONS
// ==========================================

function renderServices() {
    // Note: We need to join with default_services to get service details
    // For now, let's assume servicesData includes default service information
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
            <td><span class="service-name">${service.display_name || service.service_name}</span></td>
            <td>
                <span class="category-badge ${getCategoryClass(service.category_id)}">
                    ${getCategoryIcon(service.category_id)}
                    ${getCategoryName(service.category_id)}
                </span>
            </td>
            <td>₱${parseFloat(service.price_override || service.price).toFixed(2)}</td>
            <td>${service.duration_minutes_override || service.duration_minutes} mins</td>
            <td>
                <span class="status-badge ${(service.is_available_override || service.is_available) ? 'available' : 'unavailable'}">
                    ${(service.is_available_override || service.is_available) ? 'Available' : 'Unavailable'}
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

function populateServiceSelect() {
    const serviceSelect = document.getElementById('serviceSelect');
    serviceSelect.innerHTML = '<option value="">Select a service</option>' +
        servicesData.map(service => `
            <option value="${service.default_service_id}">${service.display_name || service.service_name}</option>
        `).join('');
}

function populateCategorySelect() {
    const categorySelect = document.getElementById('categorySelect');
    categorySelect.innerHTML = '<option value="">Select a category</option>' +
        categoriesData.map(cat => `
            <option value="${cat.service_category_id}">${cat.category_name}</option>
        `).join('');
}

function renderCategoriesList() {
    const categoriesList = document.getElementById('categoriesList');
    categoriesList.innerHTML = categoriesData.map(cat => {
        const branchCat = branchCategoriesData.find(bc => bc.default_category_id == cat.service_category_id);
        const capacity = branchCat ? branchCat.capacity_override : cat.def_capacity;
        
        return `
            <div class="category-item">
                <div class="category-info">
                    <div class="category-name">${branchCat?.display_name || cat.category_name}</div>
                    <div class="category-details">${branchCat?.description_override || cat.description}</div>
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

// ==========================================
// MODAL FUNCTIONS
// ==========================================

function openServiceModal(serviceId = null) {
    // Close other modals first
    closeAllModals();
    
    const modalTitle = document.getElementById('modalTitle');
    const submitBtnText = document.getElementById('submitBtnText');
    const form = document.getElementById('serviceForm');
    
    form.reset();
    
    if (serviceId) {
        modalTitle.textContent = 'Edit Service';
        submitBtnText.textContent = 'Update Service';
        loadServiceData(serviceId);
    } else {
        modalTitle.textContent = 'Add New Service';
        submitBtnText.textContent = 'Save Service';
        document.getElementById('branchServiceId').value = '';
        document.getElementById('serviceAvailable').checked = true;
    }
    
    serviceModal.classList.add('active');
}

function closeServiceModal() {
    serviceModal.classList.remove('active');
}

function openCategoriesModal() {
    // Close other modals first
    closeAllModals();
    
    renderCategoriesList();
    categoriesModal.classList.add('active');
}

function closeCategoriesModal() {
    categoriesModal.classList.remove('active');
}

function openEditCapacityModal(categoryId) {
    // Don't close categories modal - we want to return to it
    // Just close the capacity modal if it's open
    closeEditCapacityModal();
    
    selectedCategoryId = categoryId;
    const category = categoriesData.find(c => c.service_category_id == categoryId);
    const branchCat = branchCategoriesData.find(bc => bc.service_category_id == categoryId);
    
    if (!category) return;
    
    // Populate modal
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('editCategoryName').textContent = category.category_name;
    document.getElementById('editCategoryDescription').textContent = category.description;
    document.getElementById('branchCapacity').value = branchCat ? branchCat.branch_capacity : category.def_capacity;
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
    serviceModal.classList.remove('active');
    categoriesModal.classList.remove('active');
    editCapacityModal.classList.remove('active');
    deleteModal.classList.remove('active');
}

// ==========================================
// SERVICE OPERATIONS
// ==========================================

function editService(serviceId) {
    openServiceModal(serviceId);
}

function loadServiceData(serviceId) {
    const service = servicesData.find(s => s.branch_service_override_id == serviceId);
    
    if (service) {
        document.getElementById('branchServiceId').value = service.branch_service_override_id;
        document.getElementById('serviceSelect').value = service.default_service_id;
        document.getElementById('serviceName').value = service.display_name || '';
        document.getElementById('serviceDescription').value = service.description_override || '';
        document.getElementById('servicePrice').value = service.price_override || '';
        document.getElementById('serviceDuration').value = service.duration_minutes_override || '';
        document.getElementById('serviceAvailable').checked = (service.is_available_override == 1);
    }
}

function handleServiceSubmit(e) {
    e.preventDefault();
    
    const branchServiceOverrideId = document.getElementById('branchServiceId').value;
    const formData = {
        default_service_id: document.getElementById('serviceSelect').value, // We'll need to add a service select dropdown
        display_name: document.getElementById('serviceName').value.trim(),
        description_override: document.getElementById('serviceDescription').value.trim(),
        price_override: parseFloat(document.getElementById('servicePrice').value),
        duration_minutes_override: parseInt(document.getElementById('serviceDuration').value),
        is_available_override: document.getElementById('serviceAvailable').checked ? 1 : 0
    };

    // Simulate saving
    if (branchServiceOverrideId) {
        // Update existing service override
        const index = servicesData.findIndex(s => s.branch_service_override_id == branchServiceOverrideId);
        if (index !== -1) {
            servicesData[index] = {
                ...servicesData[index],
                ...formData
            };
        }
        showNotification('Service override updated successfully!', 'success');
    } else {
        // Add new service override
        const newService = {
            branch_service_override_id: servicesData.length + 1,
            branch_id: 1,
            ...formData
        };
        servicesData.push(newService);
        showNotification('Service override created successfully!', 'success');
    }
    
    closeServiceModal();
    renderServices();

    // When backend is ready, use this:
    /*
    const url = branchServiceOverrideId
        ? `../../backend/public/index.php?url=services/updateBranchService/${branchServiceOverrideId}`
        : '../../backend/public/index.php?url=services/createBranchService';
    
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification(
                branchServiceOverrideId ? 'Service updated successfully' : 'Service created successfully',
                'success'
            );
            closeServiceModal();
            loadServices();
        } else {
            showNotification(result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving service:', error);
        showNotification('Failed to save service', 'error');
    });
    */
}

function handleCapacityUpdate(e) {
    e.preventDefault();
    
    const categoryId = parseInt(document.getElementById('editCategoryId').value);
    const newCapacity = parseInt(document.getElementById('branchCapacity').value);
    
    // Simulate updating
    const index = branchCategoriesData.findIndex(bc => bc.default_category_id == categoryId);
    if (index !== -1) {
        branchCategoriesData[index].capacity_override = newCapacity;
    } else {
        // Create new branch category override
        branchCategoriesData.push({
            branch_category_override_id: branchCategoriesData.length + 1,
            branch_id: 1,
            default_category_id: categoryId,
            display_name: null,
            description_override: null,
            capacity_override: newCapacity,
            is_active_override: null
        });
    }
    
    showNotification('Category capacity updated successfully!', 'success');
    closeEditCapacityModal();
    renderCategoriesList();
    
    // When backend is ready, use this:
    /*
    fetch('../../backend/public/index.php?url=services/updateBranchCategoryCapacity', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            default_category_id: categoryId,
            capacity_override: newCapacity
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification('Category capacity updated successfully!', 'success');
            closeEditCapacityModal();
            loadBranchCategories();
            renderCategoriesList();
        } else {
            showNotification(result.message || 'Failed to update capacity', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating capacity:', error);
        showNotification('Failed to update capacity', 'error');
    });
    */
}

function confirmDeleteService(serviceId) {
    selectedServiceId = serviceId;
    deleteModal.classList.add('active');
}

function handleDeleteService() {
    if (!selectedServiceId) return;

    // Simulate deletion
    const index = servicesData.findIndex(s => s.branch_service_override_id == selectedServiceId);
    if (index !== -1) {
        servicesData.splice(index, 1);
        showNotification('Service override deleted successfully!', 'success');
        closeDeleteModal();
        renderServices();
    }

    // When backend is ready, use this:
    /*
    fetch(`../../backend/public/index.php?url=services/deleteBranchService/${selectedServiceId}`, {
        method: 'DELETE'
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification('Service deleted successfully', 'success');
            closeDeleteModal();
            loadServices();
        } else {
            showNotification(result.message || 'Failed to delete service', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting service:', error);
        showNotification('Failed to delete service', 'error');
    });
    */
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function getCategoryName(categoryId) {
    const category = categoriesData.find(c => c.service_category_id == categoryId);
    return category ? category.category_name : 'Unknown';
}

function getCategoryClass(categoryId) {
    const names = {
        1: 'hair',
        2: 'massage',
        3: 'nail',
        4: 'facial'
    };
    return names[categoryId] || 'default';
}

function getCategoryIcon(categoryId) {
    const icons = {
        1: '<i class="fas fa-cut"></i>',
        2: '<i class="fas fa-spa"></i>',
        3: '<i class="fas fa-hand-sparkles"></i>',
        4: '<i class="fas fa-smile"></i>'
    };
    return icons[categoryId] || '<i class="fas fa-tag"></i>';
}

function showNotification(message, type = 'info') {
    // Simple alert for now - you can enhance this with toast notifications
    alert(message);
}
