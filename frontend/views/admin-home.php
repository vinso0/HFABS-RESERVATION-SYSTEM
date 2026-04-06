<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header("Location: admin-login.html");
    exit;
}

date_default_timezone_set('Asia/Taipei');

$displayRole = ucfirst($_SESSION['role'] ?? 'Admin');
$branchName  = $_SESSION['branch_name'] ?? 'Your Branch';
$isAdmin     = $_SESSION['role'] === 'admin';

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Morning' : ($hour < 18 ? 'Afternoon' : 'Evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Happy Face & Body Spa</title>
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

            <!-- Header -->
            <div class="dash-header">
                <div>
                    <h2>Good <?= $greeting ?>, <?= htmlspecialchars($displayRole) ?></h2>
                    <p class="welcome-text">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($branchName) ?>
                        &nbsp;·&nbsp;
                        <?= date('l, F j, Y') ?>
                    </p>
                </div>
                <div class="dash-date-badge">
                    <i class="fas fa-clock"></i>
                    <span id="liveTime"></span>
                </div>
            </div>
            <!-- ── Export Analytics Buttons ── -->
            <div class="export-actions" id="exportActions">
            <div class="export-actions-header">
                <i class="fas fa-download"></i>
                <span>Export Analytics Report</span>
            </div>
            <div class="export-btn-group">
                <a href="/HFABS/backend/public/index.php?url=analytics/exportExcel"
                class="export-btn export-btn-excel"
                title="Download full analytics report as Excel">
                <i class="fas fa-file-excel"></i>
                Download as Excel
                </a>
                <a href="/HFABS/backend/public/index.php?url=analytics/exportPDF"
                class="export-btn export-btn-pdf"
                title="Download full analytics report as PDF">
                <i class="fas fa-file-pdf"></i>
                Download as PDF
                </a>
            </div>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
            </div>

            <!-- Two-column row -->
            <div class="dash-row">
                <div class="dash-card dash-card-lg">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-calendar-day"></i> Today's Reservations</h3>
                        <a href="admin-todays-reservations.php" class="dash-link">View all <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div id="todayTable">
                        <div class="dash-loading"><div class="dash-spinner"></div><span>Loading…</span></div>
                    </div>
                </div>

                <div class="dash-card dash-card-sm">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-chart-pie"></i> Status Breakdown</h3>
                    </div>
                    <div id="statusBreakdown">
                        <div class="dash-loading"><div class="dash-spinner"></div><span>Loading…</span></div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-receipt"></i> Recent Transactions</h3>
                    <a href="admin-transactions.php" class="dash-link">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="transactionsTable">
                    <div class="dash-loading"><div class="dash-spinner"></div><span>Loading…</span></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="admin-todays-reservations.php" class="qa-card">
                    <i class="fas fa-calendar-day"></i><span>Today's</span>
                </a>
                <a href="admin-reservations.php" class="qa-card">
                    <i class="fas fa-calendar-check"></i><span>Reservations</span>
                </a>
                <a href="admin-transactions.php" class="qa-card">
                    <i class="fas fa-receipt"></i><span>Transactions</span>
                </a>
                <?php if ($isAdmin): ?>
                <a href="admin-customers.php" class="qa-card">
                    <i class="fas fa-users"></i><span>Customers</span>
                </a>
                <a href="admin-services.php" class="qa-card">
                    <i class="fas fa-spa"></i><span>Services</span>
                </a>
                <a href="admin-feedback.php" class="qa-card">
                    <i class="fas fa-star"></i><span>Feedback</span>
                </a>
                <?php endif; ?>
            </div>

        </section>
    </main>

    <script src="../public/js/admin-dashboard.js"></script>
</body>
</html>
