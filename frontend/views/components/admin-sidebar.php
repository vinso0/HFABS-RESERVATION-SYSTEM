<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../public/img/HFABS-LOGO.png" alt="Happy Face & Body Spa" class="brand-logo" />
        <div class="brand-info">
            <h1 class="brand-name">HFABS</h1>
            <p class="brand-subtitle">
                <?php
                if (isset($_SESSION['branch_name'])) {
                    echo ucwords(strtolower($_SESSION['branch_name']));
                } else {
                    echo 'Happy Face & Body Spa';
                }
                ?>
            </p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="admin-home.php" class="nav-item">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin-todays-reservations.php" class="nav-item">
            <i class="far fa-clock"></i>
            <span>Today's Reservations</span>
        </a>
        <a href="./admin-reservations.php" class="nav-item">
            <i class="far fa-calendar"></i>
            <span>Reservations</span>
        </a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="./admin-services.php" class="nav-item">
            <i class="fas fa-cut"></i>
            <span>Services</span>
        </a>
        <a href="admin-transactions.php" class="nav-item">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Transactions</span>
        </a>
        <a href="admin-customers.php" class="nav-item">
            <i class="far fa-user"></i>
            <span>Customers</span>
        </a>
        <a href="admin-feedback.php" class="nav-item">
            <i class="far fa-comment"></i>
            <span>Feedback</span>
        </a>
        <?php endif; ?>
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
