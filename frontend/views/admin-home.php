<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header("Location: admin-login.html");
    exit;
}

// Set timezone to Asia/Taipei (UTC+8) to match local time
date_default_timezone_set('Asia/Taipei');

$adminName = $_SESSION['user_name'] ?? 'Admin';
$displayRole = ucfirst($_SESSION['role'] ?? 'Admin'); // "Admin" or "Cashier"
$branchName = $_SESSION['branch_name'] ?? 'Your Branch';
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
                    <!-- Replace the <h2> line: -->
                    <h2>Good <?php
                        $hour = (int)date('H');
                        if ($hour < 12) {
                            echo 'Morning';
                        } elseif ($hour < 18) {
                            echo 'Afternoon';
                        } else {
                            echo 'Evening';
                        }
                    ?>, <?= htmlspecialchars($displayRole) ?> </h2>
                    <p class="welcome-text"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($branchName) ?> &nbsp;·&nbsp; <?= date('l, F j, Y') ?></p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
            </div>

            <!-- Today's Appointments -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-calendar-day"></i> Today's Appointments</h3>
                    <a href="admin-reservations.php" class="dash-link">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="todayTable">
                    <div class="dash-loading"><div class="dash-spinner"></div><span>Loading...</span></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="admin-reservations.php" class="qa-card">
                    <i class="fas fa-calendar-check"></i>
                    <span>Reservations</span>
                </a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin-customers.php" class="qa-card">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="admin-services.php" class="qa-card">
                    <i class="fas fa-spa"></i>
                    <span>Services</span>
                </a>
                <a href="admin-feedback.php" class="qa-card">
                    <i class="fas fa-star"></i>
                    <span>Feedback</span>
                </a>
                <?php endif; ?>
            </div>

        </section>
    </main>

    <script>
    async function loadDashboardStats() {
        try {
            const res = await fetch('/HFABS/backend/public/index.php?url=reservation/getAllReservations&status=all&page=1&itemsPerPage=100', {
                credentials: 'same-origin'
            });
            const data = await res.json();

            if (!data.success) throw new Error('Failed');

            const all  = data.data ?? [];
            const today = new Date().toISOString().slice(0, 10);

            const todayTotal    = all.filter(r => (r.schedule?.schedule_date ?? r.reservation_date) === today).length;
            const confirmed     = all.filter(r => r.status === 'confirmed').length;
            const completed     = all.filter(r => r.status === 'completed').length;
            const rescheduled   = all.filter(r => r.status === 'rescheduled').length;

            document.getElementById('statsGrid').innerHTML = `
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#d91a7e,#ff4da6)">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Today's Bookings</p>
                        <p class="stat-number">${todayTotal}</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399)">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Confirmed</p>
                        <p class="stat-number">${confirmed}</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8)">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Completed</p>
                        <p class="stat-number">${completed}</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Rescheduled</p>
                        <p class="stat-number">${rescheduled}</p>
                    </div>
                </div>
            `;
        } catch (e) {
            document.getElementById('statsGrid').innerHTML =
                '<p style="color:#999;font-size:13px;padding:8px 0">Could not load stats.</p>';
        }
    }

    async function loadTodayAppointments() {
        try {
            // ✅ Correct spelling: getTodaysReservations
            const res = await fetch('/HFABS/backend/public/index.php?url=reservation/getTodaysReservations', {
                credentials: 'same-origin'
            });
            const data = await res.json();
            const container = document.getElementById('todayTable');

            if (!data.success || !data.data.length) {
                container.innerHTML = `
                    <div class="dash-empty">
                        <i class="fas fa-calendar-times"></i>
                        <p>No appointments scheduled for today.</p>
                    </div>`;
                return;
            }

            const rows = data.data.map(r => `
                <tr>
                    <td>#${r.reservation_id}</td>
                    <td>${r.customer_name ?? '—'}</td>
                    <td>${r.schedule?.start_time ? formatTime(r.schedule.start_time) : '—'}</td>
                    <td>${(r.services ?? []).map(s => s.service_name).join(', ') || '—'}</td>
                    <td><span class="status-badge status-${r.status}">${capitalize(r.status)}</span></td>
                </tr>
            `).join('');

            container.innerHTML = `
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Customer</th><th>Time</th><th>Services</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>`;
        } catch (e) {
            document.getElementById('todayTable').innerHTML =
                '<p style="color:#999;font-size:13px;padding:8px 0">Could not load appointments.</p>';
        }
    }

    function formatTime(t) {
        const [h, m] = t.split(':');
        const hr = parseInt(h);
        return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
    }

    function capitalize(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    loadDashboardStats();
    loadTodayAppointments();
    </script>
</body>
</html>
