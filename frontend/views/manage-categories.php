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
    <title>Manage Categories | HFABS Superadmin</title>
    <link rel="stylesheet" href="../public/css/superadmin.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="toast-container"></div>
    <?php include 'components/superadmin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/superadmin-navbar.php'; ?>

        <div class="page-content">
            <div class="section-heading">
                <h2>Manage Categories</h2>
                <p>Create, update, and manage service categories across all branches.</p>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="categorySearch" placeholder="Search by category name, description…" oninput="filterCategories()">
                    </div>
                    <select class="form-control" id="statusFilter" onchange="filterCategories()" style="width:auto;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-th-large"></i> Service Categories</span>
                    <span id="category-count" style="font-size:12px;color:#aaa;"></span>
                </div>
                <div class="panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Default Capacity</th>
                                <th>Branches</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categories-tbody"></tbody>
                    </table>

                    <?php include 'components/pagination.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add / Edit Modal -->
    <div class="modal-overlay" id="categoryModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add Category</h3>
                <button type="button" class="close-btn" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="editCategoryId">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="fieldCategoryName" placeholder="e.g., Hair, Massage, Nail" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default Capacity</label>
                            <input type="number" class="form-control" id="fieldDefaultCapacity" placeholder="5" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="fieldDescription" placeholder="Category description..." rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign to Branches</label>
                        <div class="branch-checkboxes" id="branchCheckboxes">
                            <!-- Branch checkboxes will be populated here -->
                        </div>
                        <small class="form-text text-muted">Select which branches should offer this category.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="fieldStatus">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('categoryModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/superadmin-sidebar.js"></script>
    <script src="../public/js/superadmin-toast.js"></script>
    <script src="../public/js/manage-categories.js"></script>
</body>
</html><?php
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
    <title>Manage Categories | HFABS Superadmin</title>
    <link rel="stylesheet" href="../public/css/superadmin.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="toast-container"></div>
    <?php include 'components/superadmin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/superadmin-navbar.php'; ?>

        <div class="page-content">
            <div class="section-heading">
                <h2>Manage Categories</h2>
                <p>Create, update, and manage service categories across all branches.</p>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="categorySearch" placeholder="Search by category name, description…" oninput="filterCategories()">
                    </div>
                    <select class="form-control" id="statusFilter" onchange="filterCategories()" style="width:auto;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-th-large"></i> Service Categories</span>
                    <span id="category-count" style="font-size:12px;color:#aaa;"></span>
                </div>
                <div class="panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Default Capacity</th>
                                <th>Branches</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categories-tbody"></tbody>
                    </table>

                    <?php include 'components/pagination.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add / Edit Modal -->
    <div class="modal-overlay" id="categoryModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add Category</h3>
                <button type="button" class="close-btn" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="editCategoryId">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="fieldCategoryName" placeholder="e.g., Hair, Massage, Nail" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default Capacity</label>
                            <input type="number" class="form-control" id="fieldDefaultCapacity" placeholder="5" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="fieldDescription" placeholder="Category description..." rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign to Branches</label>
                        <div class="branch-checkboxes" id="branchCheckboxes">
                            <!-- Branch checkboxes will be populated here -->
                        </div>
                        <small class="form-text text-muted">Select which branches should offer this category.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="fieldStatus">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('categoryModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/superadmin-sidebar.js"></script>
    <script src="../public/js/superadmin-toast.js"></script>
    <script src="../public/js/manage-categories.js"></script>
</body>
</html>