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
    <title>Manage Branches | HFABS Superadmin</title>
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
                <h2>Manage Branches</h2>
                <p>Add, edit, and configure spa branch locations and their operating details.</p>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="branchSearch" placeholder="Search by name or location…" oninput="filterBranches()">
                    </div>
                    <select class="form-control" id="statusFilter" onchange="filterBranches()" style="width:auto;padding:8px 12px;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-primary" onclick="openAddBranchModal()">
                        <i class="fas fa-plus"></i> Add Branch
                    </button>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-store"></i> Branch List</span>
                    <span id="branch-count" style="font-size:12px;color:#aaa;"></span>
                </div>
                <div class="panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Branch Name</th>
                                <th>Location</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Hours</th>
                                <th>Downpayment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="branches-tbody"></tbody>
                    </table>

                    <?php include 'components/pagination.php'; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add / Edit Branch Modal -->
    <div class="modal-overlay" id="branchModal">
        <div class="modal" style="max-width:560px;">
            <div class="modal-header">
                <h3 class="modal-title" id="branchModalTitle">Add Branch</h3>
                <button class="modal-close" onclick="closeModal('branchModal')"><i class="fas fa-times"></i></button>
            </div>
            <form id="branchForm" onsubmit="saveBranch(event)">
                <input type="hidden" id="editBranchId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Branch Name</label>
                        <input type="text" class="form-control" id="bName" placeholder="e.g. Makati Branch" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="bContact" placeholder="09XXXXXXXXX">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Location / Address</label>
                    <input type="text" class="form-control" id="bLocation" placeholder="Full address"
                        oninput="updateLocationPreview(this.value)" required>
                    <div id="locationPreview" class="location-preview" style="display:none;">
                        <span class="preview-label">📍 Preview: </span>
                        <a id="locationPreviewLink" href="#" target="_blank" rel="noopener noreferrer"
                        class="maps-link" title="Test this link">View on Google Maps</a>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="bEmail" placeholder="branch@email.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Opening Time</label>
                        <input type="time" class="form-control" id="bOpenTime" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Closing Time</label>
                        <input type="time" class="form-control" id="bCloseTime" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Downpayment Rate (0–1)</label>
                        <input type="number" class="form-control" id="bDownRate" placeholder="e.g. 0.5" step="0.01" min="0" max="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="bStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('branchModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Branch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal-overlay" id="deleteBranchModal">
        <div class="modal confirm-modal">
            <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
            <h3 class="modal-title">Delete Branch</h3>
            <p>Are you sure you want to delete <strong id="deleteBranchName"></strong>? This will remove all admin assignments linked to this branch.</p>
            <div class="modal-footer" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('deleteBranchModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDeleteBranch()"><i class="fas fa-trash-alt"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/superadmin-sidebar.js"></script>
    <script src="../public/js/superadmin-toast.js"></script>
    <script src="../public/js/maps-link.js"></script>
    <script src="../public/js/manage-branches.js"></script>
</body>
</html>
