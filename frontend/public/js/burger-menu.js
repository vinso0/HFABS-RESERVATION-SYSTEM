// Simple burger menu functionality
document.addEventListener('DOMContentLoaded', () => {
  const burgerMenu = document.getElementById('burger-menu');
  const navDropdown = document.getElementById('nav-dropdown');

  // Toggle menu
  const toggleMenu = () => {
    burgerMenu.classList.toggle('active');
    navDropdown.classList.toggle('active');
  };

  // Close menu
  const closeMenu = () => {
    burgerMenu.classList.remove('active');
    navDropdown.classList.remove('active');
  };

  // Burger menu click
  if (burgerMenu) {
    burgerMenu.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleMenu();
    });
  }

  // Close menu when clicking outside
  document.addEventListener('click', (e) => {
    if (navDropdown && !navDropdown.contains(e.target) && !burgerMenu.contains(e.target)) {
      closeMenu();
    }
  });

  // Close menu when clicking a link
  const navLinks = document.querySelectorAll('.nav-dropdown-link');
  navLinks.forEach(link => {
    link.addEventListener('click', closeMenu);
  });
});
