<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header("Location: admin-login.html");
    exit;
}

date_default_timezone_set('Asia/Taipei');

$adminName   = $_SESSION['user_name']   ?? 'Admin';
$displayRole = ucfirst($_SESSION['role'] ?? 'Admin');
$branchName  = $_SESSION['branch_name'] ?? 'Your Branch';
$isAdmin     = $_SESSION['role'] === 'admin';
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

            <!-- ── Header ── -->
            <div class="dash-header">
                <div>
                    <h2>Good <?php
                        $hour = (int)date('H');
                        echo $hour < 12 ? 'Morning' : ($hour < 18 ? 'Afternoon' : 'Evening');
                    ?>, <?= htmlspecialchars($displayRole) ?></h2>
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

            <!-- ── Stat Cards ── -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
                <div class="stat-card stat-skeleton"></div>
            </div>

            <!-- ── Two-column row: Today's Reservations + Status Breakdown ── -->
            <div class="dash-row">

                <!-- Today's Reservations -->
                <div class="dash-card dash-card-lg">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-calendar-day"></i> Today's Reservations</h3>
                        <a href="admin-todays-reservations.php" class="dash-link">View all <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div id="todayTable">
                        <div class="dash-loading"><div class="dash-spinner"></div><span>Loading…</span></div>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="dash-card dash-card-sm">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-chart-pie"></i> Status Breakdown</h3>
                    </div>
                    <div id="statusBreakdown">
                        <div class="dash-loading"><div class="dash-spinner"></div><span>Loading…</span></div>
                    </div>
                </div>

            </div>

            <!-- ── Recent Transactions ── -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-receipt"></i> Recent Transactions</h3>
                    <a href="admin-transactions.php" class="dash-link">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="transactionsTable">
                    <div class="dash-loading"><div class="dash-spinner"></div><span>Loading…</span></div>
                </div>
            </div>

            <!-- ── Quick Actions ── -->
            <div class="quick-actions">
                <a href="admin-todays-reservations.php" class="qa-card">
                    <i class="fas fa-calendar-day"></i>
                    <span>Today's</span>
                </a>
                <a href="admin-reservations.php" class="qa-card">
                    <i class="fas fa-calendar-check"></i>
                    <span>Reservations</span>
                </a>
                <a href="admin-transactions.php" class="qa-card">
                    <i class="fas fa-receipt"></i>
                    <span>Transactions</span>
                </a>
                <?php if ($isAdmin): ?>
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
    // ── Live clock ──
    function updateClock() {
        var now  = new Date();
        var h    = now.getHours();
        var m    = String(now.getMinutes()).padStart(2, '0');
        var s    = String(now.getSeconds()).padStart(2, '0');
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        document.getElementById('liveTime').textContent = h + ':' + m + ':' + s + ' ' + ampm;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ── Helpers ──
    function formatTime(t) {
        if (!t) return '—';
        var parts = t.split(':');
        var hr = parseInt(parts[0]);
        var min = parts[1];
        return (hr % 12 || 12) + ':' + min + ' ' + (hr >= 12 ? 'PM' : 'AM');
    }

    function capitalize(s) {
        if (!s) return '';
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function formatCurrency(amount) {
        return '₱' + parseFloat(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDatetime(str) {
        if (!str) return '—';
        var d = new Date(str);
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
             + ' ' + d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
    }

    // ── Stat cards + status breakdown ──
    async function loadDashboardStats() {
        try {
            var res  = await fetch('/HFABS/backend/public/index.php?url=reservation/getAllReservations&status=all&page=1&itemsPerPage=1000', {
                credentials: 'same-origin'
            });
            var data = await res.json();
            if (!data.success) throw new Error('failed');

            var all     = data.data ?? [];
            var today   = new Date().toISOString().slice(0, 10);

            var pending     = all.filter(function(r) { return r.status === 'pending'; }).length;
            var confirmed   = all.filter(function(r) { return r.status === 'confirmed'; }).length;
            var completed   = all.filter(function(r) { return r.status === 'completed'; }).length;
            var cancelled   = all.filter(function(r) { return r.status === 'cancelled'; }).length;
            var rescheduled = all.filter(function(r) { return r.status === 'rescheduled'; }).length;
            var noShow      = all.filter(function(r) { return r.status === 'no_show'; }).length;

            document.getElementById('statsGrid').innerHTML =
                makeStatCard('linear-gradient(135deg,#f59e0b,#fbbf24)', 'fa-hourglass-half','Pending',     pending)     +
                makeStatCard('linear-gradient(135deg,#10b981,#34d399)', 'fa-check-circle',  'Confirmed',   confirmed)   +
                makeStatCard('linear-gradient(135deg,#6366f1,#818cf8)', 'fa-calendar-check','Completed',   completed)   +
                makeStatCard('linear-gradient(135deg,#0ea5e9,#38bdf8)', 'fa-calendar-minus','Rescheduled', rescheduled) +
                makeStatCard('linear-gradient(135deg,#ef4444,#f87171)', 'fa-times-circle',  'Cancelled',   cancelled)   +
                makeStatCard('linear-gradient(135deg,#64748b,#94a3b8)', 'fa-user-slash',    'No-Show',     noShow);

            renderStatusBreakdown(all);

        } catch (e) {
            document.getElementById('statsGrid').innerHTML =
                '<p style="color:#999;font-size:13px;padding:8px 0;grid-column:1/-1">Could not load stats.</p>';
        }
    }

    function makeStatCard(bg, icon, label, value) {
        return '<div class="stat-card">' +
            '<div class="stat-icon" style="background:' + bg + '">' +
                '<i class="fas ' + icon + '"></i>' +
            '</div>' +
            '<div class="stat-content">' +
                '<p class="stat-label">' + label + '</p>' +
                '<p class="stat-number">' + value + '</p>' +
            '</div>' +
        '</div>';
    }

    function renderStatusBreakdown(all) {
        var container = document.getElementById('statusBreakdown');
        if (!all.length) {
            container.innerHTML = '<div class="dash-empty"><i class="fas fa-chart-pie"></i><p>No data yet.</p></div>';
            return;
        }
        var statuses = [
            { key: 'pending',     label: 'Pending',     color: '#f59e0b' },
            { key: 'confirmed',   label: 'Confirmed',   color: '#10b981' },
            { key: 'completed',   label: 'Completed',   color: '#6366f1' },
            { key: 'cancelled',   label: 'Cancelled',   color: '#ef4444' },
            { key: 'rescheduled', label: 'Rescheduled', color: '#0ea5e9' },
        ];
        var total = all.length;
        var html  = '<div class="breakdown-list">';
        statuses.forEach(function(s) {
            var count = all.filter(function(r) { return r.status === s.key; }).length;
            var pct   = total > 0 ? Math.round((count / total) * 100) : 0;
            html += '<div class="breakdown-item">' +
                '<div class="breakdown-meta">' +
                    '<span class="breakdown-label">' + s.label + '</span>' +
                    '<span class="breakdown-count">' + count + ' <span class="breakdown-pct">(' + pct + '%)</span></span>' +
                '</div>' +
                '<div class="breakdown-bar-track">' +
                    '<div class="breakdown-bar-fill" style="width:' + pct + '%;background:' + s.color + '"></div>' +
                '</div>' +
            '</div>';
        });
        html += '<div class="breakdown-total">Total: <strong>' + total + '</strong> reservation' + (total !== 1 ? 's' : '') + '</div>';
        html += '</div>';
        container.innerHTML = html;
    }

    // ── Today's Reservations table ──
    async function loadTodayReservations() {
        try {
            var res  = await fetch('/HFABS/backend/public/index.php?url=reservation/getTodaysReservations', {
                credentials: 'same-origin'
            });
            var data = await res.json();
            var container = document.getElementById('todayTable');

            if (!data.success || !data.data || !data.data.length) {
                container.innerHTML =
                    '<div class="dash-empty">' +
                        '<i class="fas fa-calendar-times"></i>' +
                        '<p>No reservations scheduled for today.</p>' +
                    '</div>';
                return;
            }

            var rows = data.data.map(function(r) {
                var time     = r.schedule && r.schedule.start_time ? formatTime(r.schedule.start_time) : '—';
                var services = (r.services ?? []).map(function(s) { return s.service_name; }).join(', ') || '—';
                return '<tr>' +
                    '<td><strong>#' + r.reservation_id + '</strong></td>' +
                    '<td>' + (r.customer_name ?? '—') + '</td>' +
                    '<td>' + time + '</td>' +
                    '<td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + services + '">' + services + '</td>' +
                    '<td><span class="status-badge status-' + r.status + '">' + capitalize(r.status) + '</span></td>' +
                '</tr>';
            }).join('');

            container.innerHTML =
                '<table class="dash-table">' +
                    '<thead><tr><th>ID</th><th>Customer</th><th>Time</th><th>Services</th><th>Status</th></tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                '</table>';
        } catch (e) {
            document.getElementById('todayTable').innerHTML =
                '<p style="color:#999;font-size:13px;padding:8px 0">Could not load today\'s reservations.</p>';
        }
    }

    // ── Recent Transactions ──
    async function loadRecentTransactions() {
    var container = document.getElementById('transactionsTable');
    try {
        var res  = await fetch('/HFABS/backend/public/index.php?url=transaction/getByBranch', {
            credentials: 'include'  // ← changed from same-origin to include
        });

        // ── Show raw response if not OK ──
        if (!res.ok) {
            var text = await res.text();
            container.innerHTML = '<p style="color:#e11;font-size:12px;padding:8px">HTTP ' + res.status + ': ' + text + '</p>';
            return;
        }

        var data = await res.json();

        if (!data.success) {
            container.innerHTML = '<p style="color:#e11;font-size:12px;padding:8px">API error: ' + (data.message ?? 'unknown') + '</p>';
            return;
        }

            // Show only 5 most recent (already sorted DESC by created_at)
            var recent = data.data.slice(0, 5);

            // Compute today's revenue from paid transactions
            var todayRevenue = data.data
                .filter(function(t) { return t.status === 'paid' && (t.created_at ?? '').slice(0, 10) === today; })
                .reduce(function(sum, t) { return sum + parseFloat(t.amount_paid || 0); }, 0);

            var totalRevenue = data.data
                .filter(function(t) { return t.status === 'paid'; })
                .reduce(function(sum, t) { return sum + parseFloat(t.amount_paid || 0); }, 0);

            // Revenue summary chips above table
            var summary =
                '<div class="txn-summary">' +
                    '<div class="txn-chip">' +
                        '<span class="txn-chip-label"><i class="fas fa-sun"></i> Today\'s Revenue</span>' +
                        '<span class="txn-chip-value">' + formatCurrency(todayRevenue) + '</span>' +
                    '</div>' +
                    '<div class="txn-chip">' +
                        '<span class="txn-chip-label"><i class="fas fa-coins"></i> Total Revenue</span>' +
                        '<span class="txn-chip-value">' + formatCurrency(totalRevenue) + '</span>' +
                    '</div>' +
                '</div>';

            var rows = recent.map(function(t) {
                var payBadge = t.payment_method === 'gcash'
                    ? '<span class="pay-badge pay-gcash"><i class="fas fa-mobile-alt"></i> GCash</span>'
                    : '<span class="pay-badge pay-cash"><i class="fas fa-money-bill-wave"></i> Cash</span>';
                var statusBadge = t.status === 'paid'
                    ? '<span class="status-badge status-confirmed">Paid</span>'
                    : '<span class="status-badge status-pending">' + capitalize(t.status) + '</span>';
                return '<tr>' +
                    '<td><strong>#' + t.payment_id + '</strong></td>' +
                    '<td>' + (t.customer_name ?? '—') + '</td>' +
                    '<td>' + formatCurrency(t.amount_paid) + '</td>' +
                    '<td>' + payBadge + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' + formatDatetime(t.created_at) + '</td>' +
                '</tr>';
            }).join('');

            container.innerHTML = summary +
                '<table class="dash-table">' +
                    '<thead><tr><th>ID</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                '</table>';

        } catch (e) {
            document.getElementById('transactionsTable').innerHTML =
                '<p style="color:#999;font-size:13px;padding:8px 0">Could not load transactions.</p>';
        }
    }

    // ── Init ──
    loadDashboardStats();
    loadTodayReservations();
    loadRecentTransactions();
    </script>
</body>
</html>
