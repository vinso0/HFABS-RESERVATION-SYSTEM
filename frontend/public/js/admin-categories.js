// ==========================================
// ADMIN CATEGORIES MANAGEMENT
// ==========================================

// Global Variables
let categoriesData = [];
let branchCategoriesData = [];
let selectedCategoryId = null;
let selectedBranchCategoryId = null;

// API Base URL
const API_BASE_URL = '../../backend/public/index.php?url';

// DOM Elements
const categoriesList = document.getElementById('categoriesList');

// Modal Elements
const editCategoryModal = document.getElementById('editCategoryModal');

// ==========================================
// INITIALIZATION
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
    loadCategories();
    loadBranchCategories();
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
    // Modal Close Buttons - Edit Category Modal
    document.getElementById('closeEditCategoryModal').addEventListener('click', closeEditCategoryModal);
    document.getElementById('cancelEditCategory').addEventListener('click', closeEditCategoryModal);

    // Form Submits - Edit Category
    document.getElementById('editCategoryForm').addEventListener('submit', handleCategoryUpdate);

    // Click outside modal to close
    editCategoryModal.addEventListener('click', (e) => {
        if (e.target === editCategoryModal) {
            editCategoryModal.classList.remove('active');
        }
    });
}

// ==========================================
// DATA LOADING
// ==========================================

function loadCategories() {
    fetch(`${API_BASE_URL}=services/categoriesList`, { credentials: 'same-origin' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                categoriesData = result.data;
                renderCategoriesList();
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
                renderCategoriesList();
            }
        })
        .catch(error => {
            console.error('Error loading branch categories:', error);
            Toast.error('Failed to load branch categories');
        });
}

// ==========================================
// RENDERING FUNCTIONS
// ==========================================

function renderCategoriesList() {
    if (categoriesData.length === 0) {
        categoriesList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No categories found</p>
                <p class="subtitle">No service categories available</p>
            </div>
        `;
        return;
    }

    const categoriesHTML = categoriesData.map(cat => {
        const branchCat = branchCategoriesData.find(bc => bc.default_category_id == cat.service_category_id);
        const capacity = branchCat ? branchCat.capacity : cat.def_capacity;
        const isActive = branchCat ? branchCat.is_active : true;
        
        return `
            <div class="category-item ${!isActive ? 'inactive' : ''}">
                <div class="category-info">
                    <div class="category-name">${branchCat?.display_name || cat.category_name}</div>
                    <div class="category-details">${branchCat?.description || cat.description}</div>
                    ${!isActive ? `<div class="category-status inactive-status">Inactive</div>` : ''}
                </div>
                <div class="category-actions">
                    <div class="category-capacity">
                        <i class="fas fa-users"></i>
                        <span>${capacity} capacity</span>
                    </div>
                    <button class="btn-edit-category" onclick="openEditCategoryModal(${cat.service_category_id})" title="Edit Category">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');

    categoriesList.innerHTML = categoriesHTML;
}

// ==========================================
// EDIT CATEGORY MODAL FUNCTIONS
// ==========================================

function openEditCategoryModal(categoryId) {
    closeEditCategoryModal();
    
    selectedCategoryId = categoryId;
    const category = categoriesData.find(c => c.service_category_id == categoryId);
    const branchCat = branchCategoriesData.find(bc => bc.default_category_id == categoryId);
    
    if (!category) return;
    
    // Populate modal
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('editBranchCategoryId').value = branchCat?.branch_category_override_id || '';
    document.getElementById('editCategoryName').textContent = category.category_name;
    document.getElementById('branchDisplayName').value = branchCat?.display_name || '';
    document.getElementById('defaultDescriptionDisplay').textContent = category.description;
    document.getElementById('branchDescription').value = branchCat?.description || '';
    document.getElementById('branchCapacity').value = branchCat ? branchCat.capacity : category.def_capacity;
    document.getElementById('defaultCapacityDisplay').textContent = category.def_capacity;
    document.getElementById('branchActive').checked = branchCat ? branchCat.is_active : true;
    
    // Set icon
    const iconContainer = document.getElementById('editCategoryIcon');
    const iconClass = getCategoryClass(categoryId);
    iconContainer.className = `category-icon-large ${iconClass}`;
    iconContainer.innerHTML = getCategoryIcon(categoryId);
    
    editCategoryModal.classList.add('active');
}

function closeEditCategoryModal() {
    editCategoryModal.classList.remove('active');
    selectedCategoryId = null;
    selectedBranchCategoryId = null;
}

// ==========================================
// CATEGORY UPDATE
// ==========================================

function handleCategoryUpdate(e) {
    e.preventDefault();
    
    const categoryId = document.getElementById('editCategoryId').value;
    const branchCategoryId = document.getElementById('editBranchCategoryId').value;
    const branchId = getCurrentBranchId();
    
    const displayName = document.getElementById('branchDisplayName').value.trim();
    const description = document.getElementById('branchDescription').value.trim();
    const capacity = document.getElementById('branchCapacity').value;
    const isActive = document.getElementById('branchActive').checked ? 1 : 0;
    
    if (!categoryId || !capacity) {
        Toast.error('Please fill in all required fields');
        return;
    }
    
    const formData = {
        branch_id: branchId,
        default_category_id: parseInt(categoryId),
        display_name: displayName || null,
        description_override: description || null,
        capacity_override: parseInt(capacity),
        is_active_override: isActive
    };
    
    let url;
    let method;
    
    if (branchCategoryId) {
        // Update existing branch category override
        url = `${API_BASE_URL}=services/branchCategoryUpdate/${branchCategoryId}`;
        method = 'PUT';
    } else {
        // Create new branch category override
        url = `${API_BASE_URL}=services/branchCategoryStore`;
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
            Toast.success('Category updated successfully');
            closeEditCategoryModal();
            loadBranchCategories();
        } else {
            Toast.error(result.message || 'Operation failed');
        }
    })
    .catch(error => {
        console.error('Error updating category:', error);
        Toast.error('Failed to update category');
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
