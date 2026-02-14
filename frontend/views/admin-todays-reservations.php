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
    <title>Today's Reservations | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-todays-reservations.css">
    <link rel="stylesheet" href="../public/css/admin-reservation-modal.css">
    <link rel="stylesheet" href="../public/css/admin-status-confirmation-modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="todays-reservations-section">
            <h2>Today's Reservations</h2>
            <p class="subtitle-text">Manage all confirmed reservations for today</p>

            <div class="reservations-container" id="reservationsContainer">
                <!-- Reservation cards will be populated by JavaScript -->
            </div>

            <div class="no-reservations" id="noReservations" style="display: none;">
                <i class="fas fa-calendar-times"></i>
                <h3>No Reservations Today</h3>
                <p>There are no confirmed reservations for today at your branch.</p>
            </div>
        </section>
    </main>

    <?php include 'components/admin-reservation-modal.php'; ?>
    <?php include 'components/admin-status-confirmation-modal.php'; ?>

    <script src="../public/js/admin-todays-reservations.js"></script>
</body>
</html>
