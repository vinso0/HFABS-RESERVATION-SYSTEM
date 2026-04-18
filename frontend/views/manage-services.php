<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: superadmin-login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services | HFABS Superadmin</title>
    <link rel="stylesheet" href="../public/css/superadmin.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/superadmin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/superadmin-navbar.php'; ?>

        <div class="page-content">
            <div class="section-heading">
                <h2>Manage Services</h2>
                <p>Create, update, and manage services across all branches.</p>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="serviceSearch" placeholder="Search by service name, category…" oninput="filterServices()">
                    </div>
                    <select class="form-control" id="statusFilter" onchange="filterServices()" style="width:auto;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Service
                    </button>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-concierge-bell"></i> Services</span>
                    <span id="service-count" style="font-size:12px;color:#aaa;"></span>
                </div>
                <div class="panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Base Price</th>
                                <th>Duration</th>
                                <th>Category</th>
                                <th>Branches</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="services-tbody"></tbody>
                    </table>

                    <?php include 'components/pagination.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add / Edit Modal -->
    <div class="modal-overlay" id="serviceModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add Service</h3>
                <button type="button" class="close-btn" onclick="closeModal('serviceModal')">&times;</button>
            </div>
            <form id="serviceForm" onsubmit="saveService(event)">
                <input type="hidden" id="editServiceId">
                <div class="modal-body">
                    <!-- Reactivation Section -->
                    <div id="reactivateSection" style="display: none; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Reactivate a Deactivated Service</label>
                            <div class="reactivate-options">
                                <label class="radio-option">
                                    <input type="radio" name="service_option" value="new" checked onchange="handleNewServiceSelection()">
                                    <span>Create New Service</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="service_option" value="reactivate" onchange="handleReactivateSelection()">
                                    <span>Reactivate Existing Service</span>
                                </label>
                            </div>
                        </div>
                        <div id="deactivatedServicesList" class="deactivated-services-list" style="display: none;">
                            <!-- Deactivated services will be populated here -->
                        </div>
                    </div>

                    <!-- New Service Fields -->
                    <div id="newServiceFields">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="fieldServiceName" placeholder="e.g., Haircut, Massage" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Base Price (₱)</label>
                                <input type="number" class="form-control" id="fieldBasePrice" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="fieldDuration" placeholder="30" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select class="form-control" id="fieldCategory">
                                    <option value="1">Hair</option>
                                    <option value="2">Massage</option>
                                    <option value="3">Nail</option>
                                    <option value="4">Facial</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign to Branches</label>
                            <div class="branch-checkboxes" id="branchCheckboxes">
                                <!-- Branch checkboxes will be populated here -->
                            </div>
                            <small class="form-text text-muted">Select which branches should offer this service. Leave empty for all branches.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="fieldDescription" placeholder="Service description..." rows="3"></textarea>
                        </div>
                        <div class="form-group">
                        <label class="form-label">Service Image <small style="color:#aaa;font-weight:400;">(optional)</small></label>
                        <div id="saImageWrapper">
                            <!-- Preview (hidden by default) -->
                            <div id="saImagePreviewBox" style="display:none;position:relative;margin-bottom:8px;">
                                <img id="saImagePreview" src="" alt="Preview"
                                    style="width:100%;max-height:180px;object-fit:cover;border-radius:8px;border:1px solid #ddd;display:block;">
                                <button type="button" onclick="saRemoveImage()"
                                        style="position:absolute;top:-8px;right:-8px;width:24px;height:24px;border-radius:50%;
                                            background:#e53935;color:#fff;border:2px solid #fff;cursor:pointer;
                                            font-size:11px;display:flex;align-items:center;justify-content:center;padding:0;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <!-- Upload trigger (shown when no image) -->
                            <label id="saImageUploadLabel" for="saImageFileInput"
                                style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                                        gap:6px;padding:20px 16px;border:2px dashed #d0d5dd;border-radius:8px;
                                        background:#fafafa;color:#9aa5b4;cursor:pointer;text-align:center;transition:border-color .2s,background .2s;">
                                <i class="fas fa-image" style="font-size:24px;"></i>
                                <span style="font-size:13px;font-weight:500;">Click to upload image</span>
                                <small style="font-size:11px;color:#b0bec5;">JPEG, PNG, WebP or GIF · Max 5MB</small>
                            </label>
                            <input type="file" id="saImageFileInput" accept="image/jpeg,image/png,image/webp,image/gif"
                                style="display:none;" onchange="saHandleImageSelect(event)">
                            <!-- Hidden field carries base64 data -->
                            <input type="hidden" id="saImageBase64" value="">
                            <input type="hidden" id="saRemoveImage" value="0">
                        </div>
                    </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="fieldStatus">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Reactivation Fields -->
                    <div id="reactivateFields" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            You are reactivating a deactivated service. Update the details below and save to reactivate it.
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign to Branches</label>
                            <div class="branch-checkboxes" id="reactivateBranchCheckboxes">
                                <!-- Branch checkboxes will be populated here -->
                            </div>
                            <small class="form-text text-muted">Select which branches should offer this reactivated service.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('serviceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal confirm-modal">
            <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
            <h3 class="modal-title">Delete Service</h3>
            <p>Are you sure you want to delete <strong id="deleteServiceName"></strong>? This action cannot be undone.</p>
            <div class="modal-footer" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash-alt"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/superadmin-sidebar.js"></script>
    <script src="../public/js/superadmin-toast.js"></script>
    <script src="../public/js/manage-services.js"></script>
</body>
</html>