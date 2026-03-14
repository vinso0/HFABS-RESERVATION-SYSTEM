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
    <title>Dashboard | HFABS Superadmin</title>
    <link rel="stylesheet" href="../public/css/superadmin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/superadmin-sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/superadmin-navbar.php'; ?>

        <div class="page-content">
            <div class="section-heading">
                <h2>Dashboard</h2>
                <p>System-wide overview — admins, branches, and activity at a glance.</p>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Total Admin Users</h3>
                        <p class="stat-number" id="stat-total-admins">—</p>
                        <p class="stat-detail">All admin accounts</p>
                    </div>
                    <div class="stat-icon pink"><i class="fas fa-users-cog"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Active Admins</h3>
                        <p class="stat-number" id="stat-active-admins">—</p>
                        <p class="stat-detail">Currently active</p>
                    </div>
                    <div class="stat-icon teal"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Total Branches</h3>
                        <p class="stat-number" id="stat-total-branches">—</p>
                        <p class="stat-detail">All spa branches</p>
                    </div>
                    <div class="stat-icon violet"><i class="fas fa-store"></i></div>
                </div>
            </div>

            <!-- Quick Overview -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-bolt"></i> Quick Overview</span>
                    <div style="display:flex;gap:8px;">
                        <a href="manage-admins.php" class="btn btn-outline btn-sm"><i class="fas fa-users-cog"></i> Admins</a>
                        <a href="manage-branches.php" class="btn btn-primary btn-sm"><i class="fas fa-store"></i> Branches</a>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="overview-grid">
                        <div class="overview-card">
                            <p class="overview-label"><i class="fas fa-store" style="color:#d91a7e;margin-right:5px;"></i> Active Branches</p>
                            <p class="overview-number" id="ov-active-branches">—</p>
                        </div>
                        <div class="overview-card">
                            <p class="overview-label"><i class="fas fa-store-slash" style="color:#d91a7e;margin-right:5px;"></i> Inactive Branches</p>
                            <p class="overview-number" id="ov-inactive-branches">—</p>
                        </div>
                        <div class="overview-card">
                            <p class="overview-label"><i class="fas fa-users" style="color:#d91a7e;margin-right:5px;"></i> Total System Users</p>
                            <p class="overview-number" id="ov-total-users">—</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Admins -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-user-shield"></i> Recent Admin Accounts</span>
                    <a href="manage-admins.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody id="recent-admins-tbody">
                            <tr><td colspan="5" style="text-align:center;padding:20px;color:#aaa;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../public/js/superadmin-sidebar.js"></script>
    <script src="../public/js/superadmin-dashboard.js"></script>
</body>
</html>
