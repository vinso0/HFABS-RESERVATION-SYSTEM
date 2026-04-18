<!-- Add New Service Modal -->
<div class="modal-overlay" id="addServiceModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Add New Service</h3>
            <button class="modal-close" id="closeAddModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="addServiceForm" class="modal-body">
            <!-- Service Type Toggle -->
            <div class="form-group">
                <label class="toggle-label">
                    <input type="checkbox" id="createNewServiceAdd" onchange="toggleAddServiceMode()">
                    <span>Create new service (not in the list)</span>
                </label>
            </div>

            <!-- Existing Service Selection (shown when not creating new) -->
            <div class="form-row" id="existingServiceRowAdd">
                <div class="form-group">
                    <label for="serviceSelectAdd">
                        <i class="fas fa-tag"></i>
                        Select Existing Service *
                    </label>
                    <select id="serviceSelectAdd" name="serviceSelectAdd">
                        <option value="">Select a service</option>
                        <!-- Will be populated by JavaScript -->
                    </select>
                </div>
            </div>

            <!-- New Service Fields (shown when creating new) -->
            <div id="newServiceFieldsAdd" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="serviceCategoryAdd">
                            <i class="fas fa-list"></i>
                            Category *
                        </label>
                        <select id="serviceCategoryAdd" name="serviceCategoryAdd">
                            <option value="">Select a category</option>
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="serviceNameAdd">
                            <i class="fas fa-signature"></i>
                            Service Name *
                        </label>
                        <input type="text" id="serviceNameAdd" name="serviceNameAdd" placeholder="e.g., Hair Spa Treatment">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="serviceDescriptionAdd">
                    <i class="fas fa-align-left"></i>
                    Description *
                </label>
                <textarea id="serviceDescriptionAdd" name="serviceDescriptionAdd" placeholder="Describe the service..." rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="servicePriceAdd">
                        <i class="fas fa-peso-sign"></i>
                        Price (₱) *
                    </label>
                    <input type="number" id="servicePriceAdd" name="servicePriceAdd" placeholder="0.00" step="0.01" min="0">
                </div>

                <div class="form-group">
                    <label for="serviceDurationAdd">
                        <i class="fas fa-clock"></i>
                        Duration (minutes) *
                    </label>
                    <input type="number" id="serviceDurationAdd" name="serviceDurationAdd" placeholder="60" min="15" step="15">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="serviceAvailableAdd" name="serviceAvailableAdd" checked>
                    <span>Service Available</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelAddBtn">Cancel</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <span>Save Service</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal-overlay" id="editServiceModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Edit Service</h3>
            <button class="modal-close" id="closeEditModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="editServiceForm" class="modal-body">
            <input type="hidden" id="editBranchServiceId">

            <div class="form-group">
                <label for="editServiceSelect">
                    <i class="fas fa-tag"></i>
                    Service *
                </label>
                <select id="editServiceSelect" name="editServiceSelect" disabled>
                    <option value="">Select a service</option>
                    <!-- Will be populated by JavaScript -->
                </select>
            </div>

            <div class="form-group">
                <label for="serviceNameEdit">
                    <i class="fas fa-signature"></i>
                    Display Name *
                </label>
                <input type="text" id="serviceNameEdit" name="serviceNameEdit" placeholder="e.g., Hair Spa Treatment">
                <small class="help-text">Customize the service name for this branch</small>
            </div>

            <div class="form-group">
                <label for="serviceDescriptionEdit">
                    <i class="fas fa-align-left"></i>
                    Description *
                </label>
                <textarea id="serviceDescriptionEdit" name="serviceDescriptionEdit" placeholder="Describe the service..." rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>
                    <i class="fas fa-image"></i>
                    Branch Image Override <small style="color:#aaa;font-weight:400;">(optional)</small>
                </label>
                <div id="adminImageWrapper">
                    <!-- Global image reference (shown when no override) -->
                    <div id="adminGlobalImageRef" style="display:none;margin-bottom:8px;">
                        <small style="color:#888;">Global image (fallback):</small><br>
                        <img id="adminGlobalImageThumb" src="" alt=""
                            style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #ddd;margin-top:4px;">
                    </div>
                    <!-- Override preview -->
                    <div id="adminImagePreviewBox" style="display:none;position:relative;margin-bottom:8px;">
                        <img id="adminImagePreview" src="" alt="Preview"
                            style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid #ddd;display:block;">
                        <button type="button" onclick="adminRemoveImage()"
                                style="position:absolute;top:-8px;right:-8px;width:24px;height:24px;border-radius:50%;
                                    background:#e53935;color:#fff;border:2px solid #fff;cursor:pointer;
                                    font-size:11px;display:flex;align-items:center;justify-content:center;padding:0;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <!-- Upload trigger -->
                    <label id="adminImageUploadLabel" for="adminImageFileInput"
                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                                gap:5px;padding:16px;border:2px dashed #d0d5dd;border-radius:8px;
                                background:#fafafa;color:#9aa5b4;cursor:pointer;text-align:center;transition:border-color .2s;">
                        <i class="fas fa-image" style="font-size:20px;"></i>
                        <span style="font-size:12px;font-weight:500;">Click to upload branch image</span>
                        <small style="font-size:11px;color:#b0bec5;">JPEG, PNG, WebP or GIF · Max 5MB</small>
                    </label>
                    <input type="file" id="adminImageFileInput" accept="image/jpeg,image/png,image/webp,image/gif"
                        style="display:none;" onchange="adminHandleImageSelect(event)">
                    <input type="hidden" id="adminImageBase64" value="">
                    <input type="hidden" id="adminRemoveFlag" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="servicePriceEdit">
                        <i class="fas fa-peso-sign"></i>
                        Price (₱) *
                    </label>
                    <input type="number" id="servicePriceEdit" name="servicePriceEdit" placeholder="0.00" step="0.01" min="0">
                </div>

                <div class="form-group">
                    <label for="serviceDurationEdit">
                        <i class="fas fa-clock"></i>
                        Duration (minutes) *
                    </label>
                    <input type="number" id="serviceDurationEdit" name="serviceDurationEdit" placeholder="60" min="15" step="15">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="serviceAvailableEdit" name="serviceAvailableEdit" checked>
                    <span>Service Available</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelEditBtn">Cancel</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <span>Update Service</span>
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
            <input type="hidden" id="editCategoryId" name="editCategoryId">

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
                    name="branchCapacity"
                    placeholder="Enter capacity" 
                    min="1" 
                    max="50"
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

<style>
.toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 500;
}

.toggle-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>