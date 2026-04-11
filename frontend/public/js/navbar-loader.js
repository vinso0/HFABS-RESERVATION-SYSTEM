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

    if (currentPath.includes('admin') || currentPath.includes('superadmin')) {
      return null;
    }

    return this.isLoggedIn() ? 'loggedIn' : 'guest';
  }

  getComponentPath() {
    return window.location.pathname.includes('views/')
      ? './components/'
      : './views/components/';
  }

  isHomePage() {
    const p = window.location.pathname.toLowerCase();
    return (
      p === '/' ||
      p.endsWith('/index.html') ||
      p.endsWith('/customer-home.php') ||
      p.endsWith('/frontend/') ||
      /\/hfabs\/?$/.test(p) ||
      /\/frontend\/?$/.test(p)
    );
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

    container.innerHTML = '<div class="navbar-loading"></div>';

    try {
      const componentPath = this.getComponentPath();
      const navbarFile = this.navbarTypes[navbarType];
      const response = await fetch(componentPath + navbarFile);

      if (!response.ok) throw new Error(`Failed to load navbar: ${response.status}`);

      const navbarHtml = await response.text();
      container.innerHTML = this.adjustPaths(navbarHtml);

      this.initializeBurgerMenu();
      this.initializeLogout();
      this.initializeNavLinks();   // single unified handler

      console.debug(`Successfully loaded ${navbarType} navbar`);
    } catch (error) {
      console.error('Error loading navbar:', error);
      container.innerHTML = '<div class="navbar-error">Error loading navbar</div>';
    }
  }

  adjustPaths(html) {
    return window.location.pathname.includes('views/')
      ? html.replace(/\.\/public\//g, '../public/')
      : html;
  }

  initializeLogout() {
    document.querySelectorAll('#logout-link, #logout-link-mobile').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.removeItem('token');
        localStorage.removeItem('userData');
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

  /**
   * Single unified nav link handler.
   *
   * Rules per link type:
   *  - [data-nav="services"]  → services.html OR #services scroll on home
   *  - [data-nav="branches"]  → always home page #branches (scroll or redirect)
   *  - [data-scroll-target]   → same smart scroll/redirect logic
   */
  initializeNavLinks() {
    const onHome    = this.isHomePage();
    const isLoggedIn = this.isLoggedIn();

    const homeUrl = isLoggedIn
      ? '/HFABS/frontend/views/customer-home.php'
      : '/HFABS/frontend/index.html';

    const closeMobileMenu = () => {
      document.getElementById('nav-dropdown')?.classList.remove('active');
      document.getElementById('burger-menu')?.classList.remove('active');
    };

    const makeScrollHandler = (sectionId) => (e) => {
      e.preventDefault();
      document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      closeMobileMenu();
    };

    // ── Services links ──────────────────────────────
    document.querySelectorAll('[data-nav="services"]').forEach(link => {
      if (onHome) {
        link.setAttribute('href', '#services');
        link.addEventListener('click', makeScrollHandler('services'));
      } else {
        link.setAttribute('href', `${homeUrl}#services`); // ← always go home#services
      }
    });

    // ── Branches links — ALWAYS go home#branches ───
    document.querySelectorAll('[data-nav="branches"]').forEach(link => {
      if (onHome) {
        link.setAttribute('href', '#branches');
        link.addEventListener('click', makeScrollHandler('branches'));
      } else {
        // Navigate to home page and land on #branches
        link.setAttribute('href', `${homeUrl}#branches`);
      }
    });

    // ── Generic data-scroll-target links ───────────
    document.querySelectorAll('[data-scroll-target]').forEach(link => {
      const target = link.getAttribute('data-scroll-target');
      if (onHome) {
        link.setAttribute('href', `#${target}`);
        link.addEventListener('click', makeScrollHandler(target));
      } else {
        link.setAttribute('href', `${homeUrl}#${target}`);
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
