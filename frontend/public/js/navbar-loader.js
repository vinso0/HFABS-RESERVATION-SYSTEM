/**
 * Dynamic Navbar Loader
 * Loads the appropriate navbar component based on the page type and user authentication status
 */

class NavbarLoader {
  constructor() {
    this.navbarContainerId = 'navbar-container';
    this.navbarTypes = {
      guest: 'navbar-guest.html',
      loggedIn: 'navbar-logged-in.html'
    };
  }

  /**
   * Determine if user is logged in by checking for token or user data in localStorage
   */
  isLoggedIn() {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('userData');
    return !!(token && userData);
  }

  /**
   * Determine the appropriate navbar type based on current page and authentication status
   */
  getNavbarType() {
    const currentPath = window.location.pathname;
    const isLoggedIn = this.isLoggedIn();

    // Admin and superadmin pages have their own navbars
    if (currentPath.includes('admin') || currentPath.includes('superadmin')) {
      return null; // Skip navbar loading for admin pages
    }

    // Logged-in users get the logged-in navbar
    if (isLoggedIn) {
      return 'loggedIn';
    }

    // Guest users get the guest navbar
    return 'guest';
  }

  /**
   * Get the correct relative path for loading navbar components
   */
  getComponentPath() {
    const currentPath = window.location.pathname;
    
    // If we're in the views directory, components are at the same level
    if (currentPath.includes('views/')) {
      return './components/';
    }
    
    // If we're in root directory, components are in views/components
    return './views/components/';
  }

  /**
   * Load navbar component from file
   */
  async loadNavbar() {
    const navbarType = this.getNavbarType();
    
    // Skip if no navbar type (admin pages)
    if (!navbarType) {
      console.debug('Navbar loading skipped for admin page');
      return;
    }

    const container = document.getElementById(this.navbarContainerId);
    if (!container) {
      console.error(`Navbar container #${this.navbarContainerId} not found`);
      return;
    }

    // Show loading state
    container.innerHTML = '<div class="navbar-loading">Loading navbar...</div>';

    try {
      const componentPath = this.getComponentPath();
      const navbarFile = this.navbarTypes[navbarType];
      const response = await fetch(componentPath + navbarFile);

      if (!response.ok) {
        throw new Error(`Failed to load navbar: ${response.status}`);
      }

      const navbarHtml = await response.text();
      
      // Adjust paths in the loaded HTML to match current directory structure
      const adjustedHtml = this.adjustPaths(navbarHtml);
      
      container.innerHTML = adjustedHtml;
      
      // Initialize burger menu if it exists (only for logged-in navbar)
      this.initializeBurgerMenu();
      
      // Initialize logout functionality if it exists (only for logged-in navbar)
      this.initializeLogout();
      
      console.debug(`Successfully loaded ${navbarType} navbar`);

    } catch (error) {
      console.error('Error loading navbar:', error);
      container.innerHTML = '<div class="navbar-error">Error loading navbar</div>';
    }
  }

  /**
   * Adjust image and link paths based on current directory structure
   */
  adjustPaths(html) {
    const currentPath = window.location.pathname;
    const isInViewsDir = currentPath.includes('views/');

    // Adjust image paths
    if (isInViewsDir) {
      // In views directory: change ./public/ to ../public/
      return html.replace(/\.\/public\//g, '../public/');
    } else {
      // In root directory: paths are already correct (./public/)
      return html;
    }
  }

  /**
   * Initialize logout functionality
   */
  initializeLogout() {
    const logoutLinks = document.querySelectorAll('#logout-link, #logout-link-mobile');
    
    logoutLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Clear localStorage
        localStorage.removeItem('token');
        localStorage.removeItem('userData');
        
        console.log('User logged out, data cleared from localStorage');
        
        // Redirect to logout endpoint
        window.location.href = link.getAttribute('href');
      });
    });
  }

  /**
   * Initialize burger menu functionality if it exists
   */
  initializeBurgerMenu() {
    const burgerMenu = document.getElementById('burger-menu');
    const navDropdown = document.getElementById('nav-dropdown');

    if (burgerMenu && navDropdown) {
      burgerMenu.addEventListener('click', () => {
        navDropdown.classList.toggle('active');
        burgerMenu.classList.toggle('active');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', (event) => {
        if (!event.target.closest('.header-container')) {
          navDropdown.classList.remove('active');
          burgerMenu.classList.remove('active');
        }
      });
    }
  }

  /**
   * Initialize the navbar loader
   */
  initialize() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.loadNavbar());
    } else {
      this.loadNavbar();
    }
  }
}

// Create a singleton instance
const navbarLoader = new NavbarLoader();

// Make it globally accessible for manual initialization if needed
window.navbarLoader = navbarLoader;

// Auto-initialize when script is loaded
navbarLoader.initialize();
