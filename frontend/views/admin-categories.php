<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-categories.css">
    <link rel="stylesheet" href="../public/css/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="categories-section">
            <div class="section-header">
                <div>
                    <h2>Categories Management</h2>
                    <p class="subtitle-text">Manage spa service categories and capacities</p>
                </div>
            </div>

            <!-- Categories List -->
            <div class="categories-container">
                <div class="categories-list" id="categoriesList">
                    <div class="loading-row">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Loading categories...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Date Capacity Overrides Section ── -->
            <div class="section-header" style="margin-top:32px;">
                <div>
                    <h2><i class="fas fa-calendar-alt" style="color:#D91A7E;margin-right:8px;"></i>Date-Specific Capacity</h2>
                    <p class="subtitle-text">Temporarily reduce a category's capacity for a specific date (e.g. staff absences)</p>
                </div>
                <button class="btn-primary" onclick="openDateCapacityModal()">
                    <i class="fas fa-plus"></i> Set Date Capacity
                </button>
            </div>

            <div class="categories-container">
                <div class="categories-list" id="dateCapacityList">
                    <div class="loading-row">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ── Edit Category Modal (unchanged) ── -->
    <div class="modal" id="editCategoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Category</h3>
                <button class="btn-close" id="closeEditCategoryModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editCategoryForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name</label>
                        <div class="category-info-display">
                            <span id="editCategoryIcon" class="category-icon-large"></span>
                            <span id="editCategoryName"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="branchDisplayName">Branch Display Name</label>
                        <input type="text" id="branchDisplayName" placeholder="Enter branch-specific name (optional)">
                    </div>
                    <div class="form-group">
                        <label>Default Description</label>
                        <p id="defaultDescriptionDisplay" class="default-description-text"></p>
                    </div>
                    <div class="form-group">
                        <label for="branchDescription">Branch Description</label>
                        <textarea id="branchDescription" rows="3" placeholder="Enter branch-specific description (optional)"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="branchCapacity">Branch Capacity</label>
                        <div class="capacity-input-group">
                            <input type="number" id="branchCapacity" min="1" required>
                            <span class="unit">people</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Default Capacity</label>
                        <p id="defaultCapacityDisplay" class="default-capacity-text"></p>
                    </div>
                    <div class="form-group">
                        <label for="branchActive">Active</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="branchActive" checked>
                            <label for="branchActive">Make this category available at this branch</label>
                        </div>
                    </div>
                    <input type="hidden" id="editCategoryId">
                    <input type="hidden" id="editBranchCategoryId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancelEditCategory">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── NEW: Date Capacity Override Modal ── -->
    <div class="modal" id="dateCapacityModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-alt" style="margin-right:8px;"></i>Set Date Capacity</h3>
                <button class="btn-close" id="closeDateCapacityModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="dateCapacityForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="dcCategory">Category</label>
                        <select id="dcCategory" required>
                            <option value="">— Select a category —</option>
                        </select>
                        <span class="input-hint" id="dcCurrentCapacityHint"
                            style="margin-top:4px;display:block;"></span>
                    </div>

                    <div class="form-group">
                        <label for="dcDate">Date</label>
                        <input type="date" id="dcDate" required
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>" />
                        <span class="input-hint">Must be a future date</span>
                    </div>

                    <!-- Fully Unavailable toggle -->
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="dcUnavailable" />
                            <label for="dcUnavailable" style="font-weight:600;color:#dc2626;">
                                <i class="fas fa-ban" style="margin-right:4px;"></i>
                                Mark as Fully Unavailable for this date
                            </label>
                        </div>
                        <span class="input-hint" style="margin-top:4px;display:block;">
                            Check this if <strong>no staff</strong> will be available for this category on that day.
                            Customers will not be able to book at all.
                        </span>
                    </div>

                    <!-- Reduced capacity (hidden when Fully Unavailable is checked) -->
                    <div class="form-group" id="dcCapacityGroup">
                        <label for="dcCapacity">Reduced Capacity</label>
                        <div class="capacity-input-group">
                            <input type="number" id="dcCapacity" min="1" placeholder="e.g. 2">
                            <span class="unit">people</span>
                        </div>
                        <span class="input-hint">
                            Set lower than the default to limit bookings for that day
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="dcReason">
                            Reason <span class="optional-label">(optional)</span>
                        </label>
                        <input type="text" id="dcReason" maxlength="255"
                            placeholder="e.g. 2 stylists absent, all staff on leave..." />
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancelDateCapacity">Cancel</button>
                    <button type="submit" class="btn-primary" id="saveDateCapacityBtn">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../public/js/toast.js"></script>
    <script src="../public/js/admin-categories.js"></script>
</body>
</html>
