let categoriesData = [];
let currentEditId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
});

function loadCategories() {
    fetch('../../backend/public/index.php?url=superadmin/categories')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                categoriesData = result.data;
                displayCategories(categoriesData);
                updateCategoryCount(categoriesData.length);
            } else {
                showToast('Failed to load categories: ' + result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            showToast('Error loading categories', 'error');
        });
}

function displayCategories(categories) {
    const tbody = document.getElementById('categories-tbody');
    
    if (categories.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center">
                    <div class="empty-state">
                        <i class="fas fa-th-large"></i>
                        <p>No categories found</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = categories.map((category, index) => `
        <tr>
            <td>${category.service_category_id}</td>
            <td>
                <strong>${category.category_name}</strong>
            </td>
            <td>${category.description}</td>
            <td>
                <span class="badge badge-info">${category.def_capacity} people</span>
            </td>
            <td>
                ${category.branch_names ? category.branch_names : 'All branches'}
            </td>
            <td>
                <span class="badge ${category.is_active ? 'badge-success' : 'badge-danger'}">
                    ${category.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="editCategory(${category.service_category_id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${!category.is_active ? `
                        <button class="btn btn-sm btn-success" onclick="reactivateCategory(${category.service_category_id})" title="Reactivate">
                            <i class="fas fa-check-circle"></i> Reactivate
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function updateCategoryCount(count) {
    const countElement = document.getElementById('category-count');
    if (countElement) {
        countElement.textContent = `${count} categor${count !== 1 ? 'ies' : 'y'}`;
    }
}

function openAddModal() {
    console.log('openAddModal called!');
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('fieldStatus').value = '1';
    document.getElementById('fieldDefaultCapacity').value = '5';
    
    // Load branches for selection
    loadBranches();
    
    openModal('categoryModal');
}

function editCategory(id) {
    const category = categoriesData.find(c => c.service_category_id === id);
    if (!category) return;

    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'Edit Category';
    
    document.getElementById('fieldCategoryName').value = category.category_name;
    document.getElementById('fieldDescription').value = category.description;
    document.getElementById('fieldDefaultCapacity').value = category.def_capacity;
    document.getElementById('fieldStatus').value = category.is_active ? '1' : '0';
    
    // Load branches and populate checkboxes
    loadBranches();
    
    openModal('categoryModal');
}

function loadBranches() {
    fetch('../../backend/public/index.php?url=superadmin/branches')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const branchCheckboxes = document.getElementById('branchCheckboxes');
                const currentBranchIds = getCurrentBranchIds();
                
                branchCheckboxes.innerHTML = result.data.map(branch => `
                    <label class="checkbox-option">
                        <input type="checkbox" name="branch_ids[]" value="${branch.branch_id}" 
                               ${currentBranchIds.includes(branch.branch_id.toString()) ? 'checked' : ''}>
                        ${branch.branch_name}
                    </label>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error loading branches:', error);
        });
}

function reactivateCategory(id) {
    const category = categoriesData.find(c => c.service_category_id === id);
    if (!category) return;

    // Update status to active
    const formData = {
        category_name: category.category_name,
        description: category.description,
        def_capacity: category.def_capacity,
        is_active: 1,
        branch_ids: category.branch_ids ? category.branch_ids.split(',') : []
    };

    fetch(`../../backend/public/index.php?url=superadmin/categories/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            loadCategories();
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error reactivating category:', error);
        showToast('Error reactivating category', 'error');
    });
}

function getCurrentBranchIds() {
    const category = categoriesData.find(c => c.service_category_id === currentEditId);
    return category ? (category.branch_ids || '').split(',').map(id => id.toString()) : [];
}

function saveCategory(event) {
    event.preventDefault();
    
    // Get selected branch IDs
    const branchCheckboxes = document.querySelectorAll('input[name="branch_ids[]"]:checked');
    const branchIds = Array.from(branchCheckboxes).map(cb => cb.value);
    
    const formData = {
        category_name: document.getElementById('fieldCategoryName').value.trim(),
        description: document.getElementById('fieldDescription').value.trim(),
        def_capacity: parseInt(document.getElementById('fieldDefaultCapacity').value),
        is_active: parseInt(document.getElementById('fieldStatus').value),
        branch_ids: branchIds
    };

    const url = currentEditId 
        ? `../../backend/public/index.php?url=superadmin/categories/${currentEditId}`
        : '../../backend/public/index.php?url=superadmin/categories';
    
    const method = currentEditId ? 'PUT' : 'POST';

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
            closeModal('categoryModal');
            loadCategories();
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving category:', error);
        showToast('Error saving category', 'error');
    });
}

function filterCategories() {
    const searchTerm = document.getElementById('categorySearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    
    let filtered = categoriesData;
    
    if (searchTerm) {
        filtered = filtered.filter(category => 
            category.category_name.toLowerCase().includes(searchTerm) ||
            category.description.toLowerCase().includes(searchTerm)
        );
    }
    
    if (statusFilter !== '') {
        filtered = filtered.filter(category => 
            category.is_active.toString() === statusFilter
        );
    }
    
    displayCategories(filtered);
    updateCategoryCount(filtered.length);
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('open');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('show');
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(modal => {
            modal.classList.remove('show');
        });
    }
});