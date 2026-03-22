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
        </section>
    </main>

    <!-- Edit Category Modal -->
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

    <script src="../public/js/toast.js"></script>
    <script src="../public/js/admin-categories.js"></script>
</body>
</html>
