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
    <title>Packages | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-packages.css">
    <link rel="stylesheet" href="../public/css/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="packages-section">
            <div class="packages-header">
                <div>
                    <h2>Packages</h2>
                    <p class="subtitle-text">Create and manage package offers for your branch.</p>
                </div>
                <button class="add-package-btn" id="openAddPackageBtn">
                    <i class="fas fa-plus"></i>
                    <span>Add Package</span>
                </button>
            </div>

            <div class="packages-toolbar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchPackage" placeholder="Search package name or service...">
                </div>

                <div class="filter-box">
                    <i class="fas fa-filter"></i>
                    <select id="availabilityFilter">
                        <option value="all">All Packages</option>
                        <option value="1">Available</option>
                        <option value="0">Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="packages-table">
                    <thead>
                        <tr>
                            <th>Package ID</th>
                            <th>Package Name</th>
                            <th>Included Services</th>
                            <th>Price</th>
                            <th>Total Duration</th>
                            <th>Availability</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="packagesTableBody">
                        <tr class="loading-row">
                            <td colspan="7">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading packages...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Modal -->
    <div class="modal-overlay" id="packageModal" style="display:none;">
        <div class="package-modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add Package</h3>
                <button type="button" class="modal-close" id="closePackageModal">&times;</button>
            </div>

            <form id="packageForm">
                <input type="hidden" id="packageId" name="package_id">

                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="packageName">Package Name</label>
                            <input type="text" id="packageName" name="package_name" maxlength="100" required>
                        </div>

                        <div class="form-group">
                            <label for="packagePrice">Package Price</label>
                            <input type="number" id="packagePrice" name="package_price" min="0" step="0.01" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="packageDescription">Description</label>
                            <textarea id="packageDescription" name="description" rows="3" placeholder="Short package description"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="packageAvailability">Availability</label>
                            <select id="packageAvailability" name="is_available">
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Total Duration</label>
                            <div class="readonly-box" id="totalDurationPreview">0 minutes</div>
                        </div>
                    </div>

                    <div class="services-picker">
                        <div class="services-picker-header">
                            <h4>Select Services</h4>
                            <span id="selectedCount">0 selected</span>
                        </div>

                        <p class="helper-text">Select at least 2 services. Only branch services are shown.</p>

                        <div class="service-list" id="serviceList">
                            <div class="service-loading">
                                <i class="fas fa-spinner fa-spin"></i> Loading services...
                            </div>
                        </div>
                    </div>

                    <div class="form-error" id="packageFormError" style="display:none;"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancelPackageBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="savePackageBtn">Save Package</button>
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
                    <p>Are you sure you want to delete this package?</p>
                    <p class="warning-text">This action cannot be undone.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelDelete">Cancel</button>
                    <button type="button" class="btn-save btn-delete-confirm" id="confirmDelete">
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete Package</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../public/js/toast.js"></script>
    <script src="../public/js/packages-data.js"></script>
</body>
</html>
