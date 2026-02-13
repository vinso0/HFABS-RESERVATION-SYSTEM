<!-- Add/Edit Service Modal -->
<div class="modal-overlay" id="serviceModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Service</h3>
            <button class="modal-close" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="serviceForm" class="modal-body">
            <input type="hidden" id="serviceId">
            <input type="hidden" id="branchServiceId">

            <div class="form-row">
                <div class="form-group">
                    <label for="serviceSelect">
                        <i class="fas fa-tag"></i>
                        Default Service *
                    </label>
                    <select id="serviceSelect" required>
                        <option value="">Select a service</option>
                        <!-- Will be populated by JavaScript -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="serviceName">
                        <i class="fas fa-signature"></i>
                        Display Name
                    </label>
                    <input type="text" id="serviceName" placeholder="e.g., Hair Spa Treatment">
                </div>
            </div>

            <div class="form-group">
                <label for="serviceDescription">
                    <i class="fas fa-align-left"></i>
                    Description Override
                </label>
                <textarea id="serviceDescription" placeholder="Describe the service..." rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="servicePrice">
                        <i class="fas fa-peso-sign"></i>
                        Price Override (₱)
                    </label>
                    <input type="number" id="servicePrice" placeholder="0.00" step="0.01" min="0">
                </div>

                <div class="form-group">
                    <label for="serviceDuration">
                        <i class="fas fa-clock"></i>
                        Duration Override (minutes)
                    </label>
                    <input type="number" id="serviceDuration" placeholder="60" min="15" step="15">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="serviceAvailable" checked>
                    <span>Service Available (Override)</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <span id="submitBtnText">Save Service</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Categories Modal -->
<div class="modal-overlay" id="categoriesModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Manage Service Categories</h3>
            <button class="modal-close" id="closeCategoriesModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <p>Set the maximum number of concurrent customers for each category at this branch.</p>
            </div>
            <div class="categories-list" id="categoriesList">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-cancel" id="closeCategoriesBtn">Close</button>
        </div>
    </div>
</div>

<!-- Edit Category Capacity Modal -->
<div class="modal-overlay" id="editCapacityModal">
    <div class="modal-container small">
        <div class="modal-header">
            <h3>Edit Category Capacity</h3>
            <button class="modal-close" id="closeEditCapacityModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="editCapacityForm" class="modal-body">
            <input type="hidden" id="editCategoryId">

            <div class="category-info-display">
                <div class="category-icon-large" id="editCategoryIcon">
                    <i class="fas fa-tag"></i>
                </div>
                <h4 id="editCategoryName">Category Name</h4>
                <p id="editCategoryDescription">Category description</p>
            </div>

            <div class="form-group">
                <label for="branchCapacity">
                    <i class="fas fa-users"></i>
                    Branch Capacity *
                </label>
                <input 
                    type="number" 
                    id="branchCapacity" 
                    placeholder="Enter capacity" 
                    min="1" 
                    max="50" 
                    required
                >
                <small class="help-text">Maximum number of customers that can be served simultaneously</small>
            </div>

            <div class="capacity-info">
                <div class="info-item">
                    <span class="info-label">Default Capacity:</span>
                    <span class="info-value" id="defaultCapacityDisplay">-</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelEditCapacity">Cancel</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <span>Update Capacity</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-container small">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
            <button class="modal-close" id="closeDeleteModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="delete-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Are you sure you want to delete this service?</p>
                <p class="warning-text">This action cannot be undone.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelDelete">Cancel</button>
                <button type="button" class="btn-save btn-delete-confirm" id="confirmDelete">
                    <i class="fas fa-trash-alt"></i>
                    <span>Delete Service</span>
                </button>
            </div>
        </div>
    </div>
</div>
