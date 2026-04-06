// Superadmin Sidebar Navigation
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
});

function initializeSidebar() {
    // Set active menu item based on current page
    setActiveMenuItem();
    
    // Handle mobile menu toggle
    setupMobileMenu();
    
    // Handle sidebar collapse
    setupSidebarCollapse();
}

function setActiveMenuItem() {
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.sidebar-nav a');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href)) {
            item.classList.add('active');
        }
    });
}

function setupMobileMenu() {
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            document.body.classList.toggle('sidebar-open');
        });
    }
}

function setupSidebarCollapse() {
    const collapseBtn = document.getElementById('sidebar-collapse');
    const sidebar = document.querySelector('.sidebar');
    
    if (collapseBtn && sidebar) {
        collapseBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        });
        
        // Restore collapsed state
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }
}

// Handle navigation clicks
function navigateToPage(url) {
    window.location.href = url;
}

// Logout functionality
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../backend/public/index.php?url=auth/logout';
    }
}
