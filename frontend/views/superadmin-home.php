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
    <title>Superadmin Dashboard | Happy Face & Body Spa</title>
    <link rel="stylesheet" href="../public/css/admin-sidebar.css">
    <link rel="stylesheet" href="../public/css/admin-navbar.css">
    <link rel="stylesheet" href="../public/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/superadmin-sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/superadmin-navbar.php'; ?>

        <section class="dashboard-section">
            <h2>Dashboard</h2>
            <p class="welcome-text">Welcome back! Here's your overview</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Total Admin Users</h3>
                        <p class="stat-number">5</p>
                        <p class="stat-detail">All active admins</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Active Admin Users</h3>
                        <p class="stat-number">4</p>
                        <p class="stat-detail">Currently active</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Total Branches</h3>
                        <p class="stat-number">3</p>
                        <p class="stat-detail">All branches</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>

            <div class="overview-section">
                <h3>Quick Overview</h3>
                <div class="overview-grid">
                    <div class="overview-card">
                        <p class="overview-label">Active Branches</p>
                        <p class="overview-number">3</p>
                    </div>
                    <div class="overview-card">
                        <p class="overview-label">Pending Admin Requests</p>
                        <p class="overview-number">1</p>
                    </div>
                    <div class="overview-card">
                        <p class="overview-label">Total System Users</p>
                        <p class="overview-number">124</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
