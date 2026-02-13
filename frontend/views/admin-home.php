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
    <title>Admin Dashboard | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/admin-sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/admin-navbar.php'; ?>

        <section class="dashboard-section">
            <h2>Dashboard</h2>
            <p class="welcome-text">Welcome back! Here's your overview</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Total Reservations</h3>
                        <p class="stat-number">156</p>
                        <p class="stat-detail">This month</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Pending Reservations</h3>
                        <p class="stat-number">12</p>
                        <p class="stat-detail">Awaiting confirmation</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Confirmed Reservations</h3>
                        <p class="stat-number">87</p>
                        <p class="stat-detail">Ready to serve</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="overview-section">
                <h3>Today's Overview</h3>
                <div class="overview-grid">
                    <div class="overview-card">
                        <p class="overview-label">Appointments Today</p>
                        <p class="overview-number">8</p>
                    </div>
                    <div class="overview-card">
                        <p class="overview-label">Completed</p>
                        <p class="overview-number">5</p>
                    </div>
                    <div class="overview-card">
                        <p class="overview-label">Upcoming</p>
                        <p class="overview-number">3</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
