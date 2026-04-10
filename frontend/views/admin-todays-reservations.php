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
            <div class="tr-page-header">
                <div>
                    <h2>Today's Reservations</h2>
                    <p class="subtitle-text">Manage all confirmed reservations for today</p>
                </div>
                <div class="stats-bar" id="statsBar">
                    <div class="stat-chip"><span id="statTotal">0</span> Total</div>
                    <div class="stat-chip stat-confirmed"><span id="statConfirmed">0</span> Confirmed</div>
                    <div class="stat-chip stat-rescheduled"><span id="statRescheduled">0</span> Rescheduled</div>
                    <div class="stat-chip stat-completed"><span id="statCompleted">0</span> Completed</div>
                    <div class="stat-chip stat-noshow"><span id="statNoShow">0</span> No-Show</div>
                </div>
            </div>

            <div class="calendar-wrapper" id="calendarWrapper">
                <div class="time-grid" id="timeGrid"></div>
            </div>

            <div id="reservationsContainer" style="display:none;"></div>

            <div class="no-reservations" id="noReservations" style="display: none;">
                <i class="fas fa-calendar-times"></i>
                <h3>No Reservations Today</h3>
                <p>There are no confirmed reservations for today at your branch.</p>
            </div>
        </section>
    </main>

    <?php include 'components/admin-todays-reservation-details-modal.php'; ?>
    <?php include 'components/admin-status-confirmation-modal.php'; ?>

    <script src="../public/js/admin-todays-reservations.js"></script>
</body>
</html>