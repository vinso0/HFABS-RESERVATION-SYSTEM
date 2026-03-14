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
    <title>Manage Admins | HFABS Superadmin</title>
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
                <h2>Manage Admins</h2>
                <p>Create, update, and manage admin and cashier accounts across all branches.</p>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="adminSearch" placeholder="Search by name, email, branch…" oninput="filterAdmins()">
                    </div>
                    <select class="form-control" id="roleFilter" onchange="filterAdmins()" style="width:auto;padding:8px 12px;">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="cashier">Cashier</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Admin
                    </button>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-users-cog"></i> Admin Accounts</span>
                    <span id="admin-count" style="font-size:12px;color:#aaa;"></span>
                </div>
                <div class="panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Contact No.</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admins-tbody"></tbody>
                    </table>

                    <?php include 'components/pagination.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add / Edit Modal -->
    <div class="modal-overlay" id="adminModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Admin</h3>
                <button class="modal-close" onclick="closeModal('adminModal')"><i class="fas fa-times"></i></button>
            </div>
            <form id="adminForm" onsubmit="saveAdmin(event)">
                <input type="hidden" id="editUserId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="fieldUsername" placeholder="Full name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="fieldEmail" placeholder="email@example.com" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="fieldContact" placeholder="09XXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select class="form-control" id="fieldRole" required>
                            <option value="admin">Admin</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Assign Branch</label>
                        <select class="form-control" id="fieldBranch">
                            <option value="">— Select Branch —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="fieldStatus">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" id="fieldPassword" placeholder="Set a password">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('adminModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal confirm-modal">
            <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
            <h3 class="modal-title">Delete Admin</h3>
            <p>Are you sure you want to delete <strong id="deleteAdminName"></strong>? This action cannot be undone.</p>
            <div class="modal-footer" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash-alt"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/superadmin-sidebar.js"></script>
    <script src="../public/js/manage-admins.js"></script>
</body>
</html>
