<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../public/img/HFABS-LOGO.png" alt="Happy Face & Body Spa" class="brand-logo" />
        <h1 class="brand-name">Happy Face & Body Spa</h1>
    </div>

    <nav class="sidebar-nav">
        <a href="superadmin-home.php" class="nav-item">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        <a href="manage-admins.php" class="nav-item">
            <i class="fas fa-users-cog"></i>
            <span>Manage Admins</span>
        </a>
        <a href="manage-branches.php" class="nav-item">
            <i class="fas fa-store"></i>
            <span>Manage Branches</span>
        </a>
        <a href="view-reports.php" class="nav-item">
            <i class="fas fa-chart-pie"></i>
            <span>Reports</span>
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>

    <script>
        // Dynamically set active sidebar item based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
            
            navItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href !== '#' && currentPage.includes(href.split('/').pop())) {
                    item.classList.add('active');
                }
            });
        });
    </script>
    <a href="../../backend/public/index.php?url=auth/logout" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</aside>
