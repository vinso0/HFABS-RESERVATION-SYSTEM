<?php
session_start();
// Temporary - disable login check for testing
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header("Location: admin-login.html");
//     exit;
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-services.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="../public/css/admin-services-modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="services-section">
            <div class="section-header">
                <div>
                    <h2>Services Management</h2>
                    <p class="subtitle-text">Manage spa services and categories</p>
                </div>
                <button class="btn-add-service" id="addServiceBtn">
                    <i class="fas fa-plus"></i>
                    <span>Add Service</span>
                </button>
            </div>

            <!-- Category Filter Tabs -->
            <div class="filter-section">
                <div class="category-tabs">
                    <button class="category-tab active" data-category="all">
                        <i class="fas fa-th-large"></i>
                        <span>All Services</span>
                    </button>
                    <button class="category-tab" data-category="1">
                        <i class="fas fa-cut"></i>
                        <span>Hair</span>
                    </button>
                    <button class="category-tab" data-category="2">
                        <i class="fas fa-spa"></i>
                        <span>Massage</span>
                    </button>
                    <button class="category-tab" data-category="3">
                        <i class="fas fa-hand-sparkles"></i>
                        <span>Nail</span>
                    </button>
                    <button class="category-tab" data-category="4">
                        <i class="fas fa-smile"></i>
                        <span>Facial</span>
                    </button>
                </div>
                <button class="btn-manage-categories" id="manageCategoriesBtn">
                    <i class="fas fa-cog"></i>
                    <span>Manage Categories</span>
                </button>
            </div>

            <!-- Services Table -->
            <div class="table-container">
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody">
                        <tr class="loading-row">
                            <td colspan="7">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading services...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php include 'components/pagination.php'; ?>
            </div>
        </section>
    </main>

    <?php include 'components/admin-services-modal.php'; ?>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/admin-services.js"></script>
</body>
</html>
