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
        <link rel="stylesheet" href="../public/css/export-modal.css">
        <link rel="stylesheet" href="../public/css/admin-notifications.css">
        <link rel="stylesheet" href="../public/css/wishlist.css" />
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
                    <div class="dash-header-right">
                        <!-- NEW: Compact Export Button replaces the old export-actions block -->
                        <button class="export-trigger-btn" onclick="openExportModal()" title="Export Analytics Report">
                            <i class="fas fa-download"></i>
                            Export Analytics Report
                        </button>
                        <div class="dash-date-badge">
                            <i class="fas fa-clock"></i>
                            <span id="liveTime"></span>
                        </div>
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

                <!-- ── Wishlist Analytics Panel ─────────────────────────────── -->
                <div class="wishlist-analytics-section" id="wishlistAnalyticsSection">

                    <div class="wishlist-analytics-header">
                        <i class="fas fa-heart"></i>
                        <h3>Wishlist Analytics</h3>
                    </div>

                    <!-- KPI Row -->
                    <div class="wishlist-kpi-row">
                        <div class="wishlist-kpi-card">
                        <div class="wishlist-kpi-icon">
                            <i class="fas fa-spa"></i>
                        </div>
                        <div class="wishlist-kpi-info">
                            <div class="kpi-value" id="wlServiceTotal">—</div>
                            <div class="kpi-label">Wishlisted Services</div>
                        </div>
                        </div>
                        <div class="wishlist-kpi-card">
                        <div class="wishlist-kpi-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="wishlist-kpi-info">
                            <div class="kpi-value" id="wlPackageTotal">—</div>
                            <div class="kpi-label">Wishlisted Packages</div>
                        </div>
                        </div>
                    </div>

                    <!-- Tables -->
                    <div class="wishlist-tables-row">

                        <!-- Services Table -->
                        <div class="wishlist-table-card">
                        <div class="wishlist-table-card-header">
                            <i class="fas fa-spa"></i>
                            Most Wishlisted Services
                        </div>
                        <div class="wishlist-table-scroll-container">
                            <table class="wishlist-table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Service Name</th>
                                    <th>Wishlist Count</th>
                                </tr>
                                </thead>
                                <tbody id="wlServicesTableBody">
                                <tr class="wl-empty-row">
                                    <td colspan="3">Loading...</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>

                        <!-- Packages Table -->
                        <div class="wishlist-table-card">
                        <div class="wishlist-table-card-header">
                            <i class="fas fa-box-open"></i>
                            Most Wishlisted Packages
                        </div>
                        <div class="wishlist-table-scroll-container">
                            <table class="wishlist-table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Package Name</th>
                                    <th>Wishlist Count</th>
                                </tr>
                                </thead>
                                <tbody id="wlPackagesTableBody">
                                <tr class="wl-empty-row">
                                    <td colspan="3">Loading...</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>

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

                <script>
                // ── Wishlist Analytics Loader for admin-home.php ──────────────
                (function () {
                // Get branch_id from PHP session (already available in admin pages)
                const branchId = <?php echo (int)($_SESSION['branch_id'] ?? 0); ?>;
                if (!branchId) return;

                async function loadWishlistAnalytics() {
                    try {
                    const res  = await fetch(`/HFABS/backend/public/index.php?url=wishlist/adminServices&branch_id=${branchId}`);
                    const data = await res.json();
                    if (!data.success) return;

                    // KPIs
                    document.getElementById('wlServiceTotal').textContent = data.summary.service || 0;
                    document.getElementById('wlPackageTotal').textContent = data.summary.package || 0;

                    // Services table
                    const sTbody = document.getElementById('wlServicesTableBody');
                    if (data.services.length === 0) {
                        sTbody.innerHTML = '<tr class="wl-empty-row"><td colspan="3">No wishlist data yet.</td></tr>';
                    } else {
                        sTbody.innerHTML = data.services.map((s, i) => `
                        <tr>
                            <td>${i + 1}</td>
                            <td>${escHtml(s.service_name)}</td>
                            <td>
                            <span class="wl-count-badge">
                                <i class="fas fa-heart"></i> ${s.wishlist_count}
                            </span>
                            </td>
                        </tr>`).join('');
                    }

                    // Packages table
                    const pTbody = document.getElementById('wlPackagesTableBody');
                    if (data.packages.length === 0) {
                        pTbody.innerHTML = '<tr class="wl-empty-row"><td colspan="3">No wishlist data yet.</td></tr>';
                    } else {
                        pTbody.innerHTML = data.packages.map((p, i) => `
                        <tr>
                            <td>${i + 1}</td>
                            <td>${escHtml(p.package_name)}</td>
                            <td>
                            <span class="wl-count-badge">
                                <i class="fas fa-heart"></i> ${p.wishlist_count}
                            </span>
                            </td>
                        </tr>`).join('');
                    }

                    } catch (err) {
                    console.error('[Admin Wishlist Analytics] Failed to load:', err);
                    }
                }

                function escHtml(str) {
                    const d = document.createElement('div');
                    d.textContent = str;
                    return d.innerHTML;
                }

                document.addEventListener('DOMContentLoaded', loadWishlistAnalytics);
                })();
                </script>
            </section>
        </main>

        <!-- ── Export Analytics Modal ── -->
        <div class="export-modal-overlay" id="exportModalOverlay" role="dialog" aria-modal="true" aria-labelledby="exportModalTitle">
            <div class="export-modal" id="exportModal">

                <!-- Modal Header -->
                <div class="export-modal-header">
                    <div class="export-modal-title-group">
                        <div class="export-modal-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 id="exportModalTitle">Analytics Report Preview</h3>
                    </div>
                    <button class="export-modal-close" onclick="closeExportModal()" aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="export-modal-body">

                    <!-- Report meta info -->
                    <div class="export-report-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($branchName) ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?= date('F j, Y') ?></span>
                        <span class="export-report-badge">Branch Report</span>
                    </div>

                    <!-- Reservations Overview -->
                    <div class="export-preview-section">
                        <h4 class="export-preview-heading">
                            <i class="fas fa-calendar-check"></i> Reservations Overview
                        </h4>
                        <div class="export-stat-grid" id="modalStatGrid">
                            <div class="export-stat-item export-stat-skeleton"></div>
                            <div class="export-stat-item export-stat-skeleton"></div>
                            <div class="export-stat-item export-stat-skeleton"></div>
                            <div class="export-stat-item export-stat-skeleton"></div>
                            <div class="export-stat-item export-stat-skeleton"></div>
                            <div class="export-stat-item export-stat-skeleton"></div>
                        </div>
                    </div>

                    <!-- Revenue Summary -->
                    <div class="export-preview-section">
                        <h4 class="export-preview-heading">
                            <i class="fas fa-coins"></i> Revenue Summary
                        </h4>
                        <div class="export-revenue-grid" id="modalRevenueGrid">
                            <div class="export-revenue-skeleton"></div>
                            <div class="export-revenue-skeleton"></div>
                        </div>
                    </div>

                    <!-- Status Distribution -->
                    <div class="export-preview-section">
                        <h4 class="export-preview-heading">
                            <i class="fas fa-chart-pie"></i> Status Distribution
                        </h4>
                        <div id="modalStatusBreakdown">
                            <div class="dash-loading"><div class="dash-spinner"></div><span>Loading…</span></div>
                        </div>
                    </div>

                </div><!-- /.export-modal-body -->

                <!-- Modal Footer -->
                <div class="export-modal-footer">
                    <p class="export-footer-note">
                        <i class="fas fa-info-circle"></i>
                        Full report includes all reservations, transactions, and service details.
                    </p>
                    <div class="export-footer-actions">
                        <button class="export-dl-btn export-dl-excel" onclick="triggerExportExcel()">
                            <i class="fas fa-file-excel"></i>
                            Download as Excel
                        </button>
                        <button class="export-dl-btn export-dl-pdf" onclick="triggerExportPDF()">
                            <i class="fas fa-file-pdf"></i>
                            Download as PDF
                        </button>
                    </div>
                </div>

            </div><!-- /.export-modal -->
        </div><!-- /.export-modal-overlay -->
        <script src="../public/js/admin-dashboard.js"></script>
        <script src="../public/js/admin-notifications.js"></script>
    </body>
</html>