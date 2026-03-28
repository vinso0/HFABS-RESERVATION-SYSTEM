<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../public/img/HFABS-LOGO.png" alt="Happy Face & Body Spa" class="brand-logo" />
        <div class="brand-info">
            <h1 class="brand-name">HFABS</h1>
            <p class="brand-subtitle">Superadmin Panel</p>
        </div>
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

        <a href="superadmin-about.php" class="nav-item">
            <i class="fas fa-info-circle"></i>
            <span>Manage About</span>
        </a>
    </nav>

    <a href="../../backend/public/index.php?url=auth/logout" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</aside>

<script>
    // Dynamically sets the active nav item based on the current page URL
document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop();
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');

    navItems.forEach(function (item) {
        const href = item.getAttribute('href');
        if (href && href !== '#' && currentPage === href.split('/').pop()) {
            item.classList.add('active');
        }
    });
});
</script>
