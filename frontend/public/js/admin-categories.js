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
    loadDateCapacities(); 
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

    // Date Capacity Modal
    document.getElementById('closeDateCapacityModal').addEventListener('click', closeDateCapacityModal);
    document.getElementById('cancelDateCapacity').addEventListener('click', closeDateCapacityModal);
    document.getElementById('dateCapacityModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('dateCapacityModal')) closeDateCapacityModal();
    });
    document.getElementById('dateCapacityForm').addEventListener('submit', handleSaveDateCapacity);
    document.getElementById('dcCategory').addEventListener('change', updateCurrentCapacityHint);

    // Toggle capacity input visibility when "Fully Unavailable" is checked
    document.getElementById('dcUnavailable').addEventListener('change', function () {
        document.getElementById('dcCapacityGroup').style.display = this.checked ? 'none' : '';
        if (this.checked) document.getElementById('dcCapacity').value = '';
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

// ==========================================
// DATE CAPACITY OVERRIDES
// ==========================================

let dateCapacitiesData = [];

async function loadDateCapacities() {
    const list = document.getElementById('dateCapacityList');
    try {
        const res  = await fetch(`${API_BASE_URL}=services/dateCapacities`, { credentials: 'same-origin' });
        const data = await res.json();

        if (!data.success) { Toast.error('Failed to load date capacities'); return; }

        dateCapacitiesData = data.data;
        renderDateCapacityList();
    } catch (err) {
        console.error('loadDateCapacities:', err);
        list.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load</p></div>`;
    }
}

function renderDateCapacityList() {
    const list = document.getElementById('dateCapacityList');

    if (!dateCapacitiesData.length) {
        list.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <p>No date-specific overrides set.</p>
                <p class="subtitle">All categories will use their default branch capacity.</p>
            </div>`;
        return;
    }

    list.innerHTML = dateCapacitiesData.map(item => {
        const isUnavailable = parseInt(item.capacity_override) === 0;

        return `
        <div class="category-item" id="dc-item-${item.id}">
            <div class="category-info">
                <div class="category-name">
                    <i class="fas fa-calendar-day" style="color:${isUnavailable ? '#dc2626' : '#D91A7E'};margin-right:6px;font-size:12px;"></i>
                    ${escapeHtml(item.category_name)}
                    &nbsp;—&nbsp;
                    <strong>${formatDateDisplay(item.override_date)}</strong>
                    ${isUnavailable
                        ? `<span style="margin-left:8px;background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;">FULLY UNAVAILABLE</span>`
                        : ''}
                </div>
                <div class="category-details">
                    ${item.reason
                        ? `<i class="fas fa-tag" style="margin-right:4px;"></i>${escapeHtml(item.reason)}`
                        : '<span style="color:#d1d5db;">No reason specified</span>'}
                </div>
            </div>
            <div class="category-actions">
                <div class="category-capacity" style="color:${isUnavailable ? '#dc2626' : '#D91A7E'};">
                    <i class="fas fa-users"></i>
                    <span>${isUnavailable ? 'Unavailable' : item.capacity_override + ' capacity'}</span>
                </div>
                <button class="btn-edit-category" style="background:#fee2e2;color:#dc2626;"
                        onclick="removeDateCapacity(${item.id})" title="Remove override">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

function openDateCapacityModal() {
    const select = document.getElementById('dcCategory');
    select.innerHTML = '<option value="">— Select a category —</option>';

    branchCategoriesData.forEach(bc => {
        const cat  = categoriesData.find(c => c.service_category_id == bc.default_category_id);
        const name = bc.display_name || cat?.category_name || `Category ${bc.default_category_id}`;
        const opt  = document.createElement('option');
        opt.value           = bc.branch_category_override_id;
        opt.textContent     = name;
        opt.dataset.capacity = bc.capacity;
        select.appendChild(opt);
    });

    document.getElementById('dcDate').value          = '';
    document.getElementById('dcCapacity').value      = '';
    document.getElementById('dcReason').value        = '';
    document.getElementById('dcCurrentCapacityHint').textContent = '';
    document.getElementById('dcUnavailable').checked = false;
    document.getElementById('dcCapacityGroup').style.display = '';
    document.getElementById('dateCapacityModal').classList.add('active');
}

function closeDateCapacityModal() {
    document.getElementById('dateCapacityModal').classList.remove('active');
}

function updateCurrentCapacityHint() {
    const select   = document.getElementById('dcCategory');
    const selected = select.options[select.selectedIndex];
    const hint     = document.getElementById('dcCurrentCapacityHint');
    if (selected && selected.dataset.capacity) {
        hint.textContent = `Current branch capacity: ${selected.dataset.capacity}`;
        hint.style.color = '#6b7280';
    } else {
        hint.textContent = '';
    }
}

async function handleSaveDateCapacity(e) {
    e.preventDefault();

    const btn      = document.getElementById('saveDateCapacityBtn');
    const origHtml = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const branchCategoryOverrideId = parseInt(document.getElementById('dcCategory').value);
    const date        = document.getElementById('dcDate').value;
    const isUnavailable = document.getElementById('dcUnavailable').checked;
    const reason      = document.getElementById('dcReason').value.trim();

    // If "fully unavailable" is checked, force capacity to 0
    const capacity = isUnavailable
        ? 0
        : parseInt(document.getElementById('dcCapacity').value);

    // Warn if capacity >= current default (only when not marking as unavailable)
    if (!isUnavailable) {
        const select   = document.getElementById('dcCategory');
        const selected = select.options[select.selectedIndex];
        const current  = parseInt(selected?.dataset.capacity || 0);
        if (capacity >= current && current > 0) {
            if (!confirm(`The capacity you entered (${capacity}) is equal to or higher than the current branch capacity (${current}). Continue anyway?`)) {
                btn.disabled  = false;
                btn.innerHTML = origHtml;
                return;
            }
        }
    }

    try {
        const res  = await fetch(`${API_BASE_URL}=services/saveDateCapacity`, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body:        JSON.stringify({
                branch_category_override_id: branchCategoryOverrideId,
                override_date:    date,
                capacity_override: capacity,
                reason: reason || null
            })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success(isUnavailable ? 'Category marked as unavailable for that date!' : 'Date capacity saved!');
            closeDateCapacityModal();
            await loadDateCapacities();
        } else {
            Toast.error(data.message || 'Failed to save.');
        }
    } catch (err) {
        console.error('handleSaveDateCapacity:', err);
        Toast.error('Could not connect to server.');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = origHtml;
    }
}

async function removeDateCapacity(id) {
    if (!confirm('Remove this date capacity override? The category will revert to its default capacity for that day.')) return;

    try {
        const res  = await fetch(`${API_BASE_URL}=services/removeDateCapacity`, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body:        JSON.stringify({ id })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success('Override removed.');
            await loadDateCapacities();
        } else {
            Toast.error(data.message || 'Failed to remove.');
        }
    } catch (err) {
        Toast.error('Could not connect to server.');
    }
}

// ── Utilities (local to categories page) ──
function formatDateDisplay(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' });
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}