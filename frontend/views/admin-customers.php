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
    <title>Customers | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-customers.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="customers-section">
            <h2>Customers</h2>
            <p class="subtitle-text">Manage all spa customers and view their reservation history</p>

            <div class="filter-section">
                <div class="filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search customers by name, email, or contact...">
                </div>
                
                <div class="filter-dropdown">
                    <i class="fas fa-filter"></i>
                    <select id="filterReservations">
                        <option value="all">All Customers</option>
                        <option value="1-5">1-5 Reservations</option>
                        <option value="6-10">6-10 Reservations</option>
                        <option value="11-20">11-20 Reservations</option>
                        <option value="20+">20+ Reservations</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>No. of Reservations</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <tr class="loading-row">
                            <td colspan="6">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading customers...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php include 'components/pagination.php'; ?>
            </div>
        </section>
    </main>

    <?php include 'components/admin-customer-reservations-modal.php'; ?>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/admin-customers.js"></script>
</body>
</html>
