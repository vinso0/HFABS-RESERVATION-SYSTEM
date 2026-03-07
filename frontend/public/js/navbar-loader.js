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

  isLoggedIn() {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('userData');
    return !!(token && userData);
  }

  getNavbarType() {
    const currentPath = window.location.pathname;
    const isLoggedIn = this.isLoggedIn();

    if (currentPath.includes('admin') || currentPath.includes('superadmin')) {
      return null;
    }

    if (isLoggedIn) {
      return 'loggedIn';
    }

    return 'guest';
  }

  getComponentPath() {
    const currentPath = window.location.pathname;

    if (currentPath.includes('views/')) {
      return './components/';
    }

    return './views/components/';
  }

  async loadNavbar() {
    const navbarType = this.getNavbarType();

    if (!navbarType) {
      console.debug('Navbar loading skipped for admin page');
      return;
    }

    const container = document.getElementById(this.navbarContainerId);
    if (!container) {
      console.error(`Navbar container #${this.navbarContainerId} not found`);
      return;
    }

    container.innerHTML = '<div class="navbar-loading">Loading...</div>';

    try {
      const componentPath = this.getComponentPath();
      const navbarFile = this.navbarTypes[navbarType];
      const response = await fetch(componentPath + navbarFile);

      if (!response.ok) {
        throw new Error(`Failed to load navbar: ${response.status}`);
      }

      const navbarHtml = await response.text();
      const adjustedHtml = this.adjustPaths(navbarHtml);

      container.innerHTML = adjustedHtml;

      this.initializeBurgerMenu();
      this.initializeLogout();
      this.initializeServicesLink();   // ← called here, inside loadNavbar
      this.initializeSmartNavLinks();

      console.debug(`Successfully loaded ${navbarType} navbar`);

    } catch (error) {
      console.error('Error loading navbar:', error);
      container.innerHTML = '<div class="navbar-error">Error loading navbar</div>';
    }
  }

  adjustPaths(html) {
    const currentPath = window.location.pathname;
    const isInViewsDir = currentPath.includes('views/');

    if (isInViewsDir) {
      return html.replace(/\.\/public\//g, '../public/');
    } else {
      return html;
    }
  }

  initializeLogout() {
    const logoutLinks = document.querySelectorAll('#logout-link, #logout-link-mobile');

    logoutLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();

        localStorage.removeItem('token');
        localStorage.removeItem('userData');

        console.log('User logged out, data cleared from localStorage');

        window.location.href = link.getAttribute('href');
      });
    });
  }

  initializeBurgerMenu() {
    const burgerMenu = document.getElementById('burger-menu');
    const navDropdown = document.getElementById('nav-dropdown');

    if (burgerMenu && navDropdown) {
      burgerMenu.addEventListener('click', () => {
        navDropdown.classList.toggle('active');
        burgerMenu.classList.toggle('active');
      });

      document.addEventListener('click', (event) => {
        if (!event.target.closest('.header-container')) {
          navDropdown.classList.remove('active');
          burgerMenu.classList.remove('active');
        }
      });
    }
  }

  initializeServicesLink() {
    const currentPath = window.location.pathname;
    const pathLower = currentPath.toLowerCase();

    const isHomePage =
      pathLower === '/' ||
      pathLower.endsWith('/index.html') ||
      pathLower.endsWith('/customer-home.php') ||
      pathLower.endsWith('/frontend/') ||
      pathLower.endsWith('/hfabs/') ||
      /\/hfabs\/?$/.test(pathLower) ||
      /\/frontend\/?$/.test(pathLower);

    console.debug('[NavbarLoader] Current path:', currentPath);
    console.debug('[NavbarLoader] isHomePage:', isHomePage);

    const servicesLinks = document.querySelectorAll(
      '.nav-link[href*="services"], .nav-dropdown-link[href*="services"]'
    );

    console.debug('[NavbarLoader] Services links found:', servicesLinks.length);

    servicesLinks.forEach(link => {
      if (isHomePage) {
        link.setAttribute('href', '#services');
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const target = document.getElementById('services');
          if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
          }
        });
      } else {
        link.setAttribute('href', '/HFABS/frontend/views/services.html');
      }
    });

    const branchLinks = document.querySelectorAll(
      '.nav-link[href*="branches"], .nav-dropdown-link[href*="branches"]'
    );
    branchLinks.forEach(link => {
      if (isHomePage) {
        link.setAttribute('href', '#branches');
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const target = document.getElementById('branches');
          if (target) target.scrollIntoView({ behavior: 'smooth' });
        });
      } else {
        link.setAttribute('href', '/HFABS/frontend/views/branches.html');
      }
    });
  }

  /**
   * Handle smart nav links with data-scroll-target
   * - On home/customer-home: smooth scroll to section
   * - On any other page: navigate to home page with hash
   */
  initializeSmartNavLinks() {
    const isHomePage =
      window.location.pathname.endsWith('index.html') ||
      window.location.pathname.endsWith('customer-home.php') ||
      window.location.pathname === '/' ||
      window.location.pathname.endsWith('/HFABS/frontend/');

    const links = document.querySelectorAll('[data-scroll-target]');

    links.forEach(link => {
      const target = link.getAttribute('data-scroll-target');

      if (isHomePage) {
        // On home page — smooth scroll to section
        link.setAttribute('href', `#${target}`);
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const section = document.getElementById(target);
          if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
          // Close mobile dropdown if open
          document.getElementById('nav-dropdown')?.classList.remove('active');
          document.getElementById('burger-menu')?.classList.remove('active');
        });
      } else {
        // On any other page — go to correct home with hash
        const isLoggedIn = this.isLoggedIn();
        const homeUrl = isLoggedIn
          ? `/HFABS/frontend/views/customer-home.php#${target}`
          : `/HFABS/frontend/index.html#${target}`;
        link.setAttribute('href', homeUrl);
      }
    });
  }

  initialize() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.loadNavbar());
    } else {
      this.loadNavbar();
    }
  }
}

const navbarLoader = new NavbarLoader();
window.navbarLoader = navbarLoader;
navbarLoader.initialize();
