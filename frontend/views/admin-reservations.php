<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header("Location: admin-login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-reservations.css">
    <link rel="stylesheet" href="../public/css/pagination.css">
    <link rel="stylesheet" href="../public/css/admin-reservation-modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="reservations-section">
            <h2>Reservations</h2>
            <p class="subtitle-text">Manage all customer reservations</p>

            <div class="filter-section">
                <div class="filter-dropdown">
                    <i class="fas fa-filter"></i>
                    <select id="filterStatus">
                        <option value="all">All Reservations</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="rescheduled">Rescheduled</option>
                        <option value="no-show">No-Show</option>
                    </select>
                </div>
                
                <div class="date-filter">
                    <label for="startDate">From:</label>
                    <input type="date" id="startDate">
                    <label for="endDate">To:</label>
                    <input type="date" id="endDate">
                    <button id="applyDateFilter">Apply Filter</button>
                    <button id="clearDateFilter">Clear</button>
                </div>
            </div>

            <div class="table-container">
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reservationsTableBody">
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>

                <?php include 'components/pagination.php'; ?>
            </div>
        </section>
    </main>

    <?php include 'components/admin-reservation-modal.php'; ?>

    <script src="../public/js/pagination.js"></script>
    <script src="../public/js/reservations-data.js"></script>
</body>
</html>