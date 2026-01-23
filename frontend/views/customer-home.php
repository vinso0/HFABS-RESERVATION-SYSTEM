<?php

require_once __DIR__ . '/../../backend/app/core/init.php';

if (!isset($_SESSION['user_id'])) {
  header("Location:  ../frontend/views/customer-login.html");
  exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Happy Face And Body Spa Reservation</title>

  <link rel="stylesheet" href="../public/css/landing.css" />
</head>

<body>
  <div class="bubble-background">
    <div class="bubble bubble-1"></div>
    <div class="bubble bubble-2"></div>
    <div class="bubble bubble-3"></div>
    <div class="bubble bubble-4"></div>
    <div class="bubble bubble-5"></div>
    <div class="bubble bubble-6"></div>
    <div class="bubble bubble-7"></div>
    <div class="bubble bubble-8"></div>
  </div>

  <header class="header">
    <div class="header-container">
      <div class="brand">
        <img src="../public/img/HFABS-LOGO.png" alt="Happy Face & Body Spa" class="brand-logo" />
        <span class="brand-name">Happy Face & Body Spa</span>
      </div>

      <button class="burger-menu" id="burger-menu" aria-label="Toggle menu">
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
      </button>

      <nav class="nav-dropdown" id="nav-dropdown">
        <a href="#home" class="nav-dropdown-link">Home</a>
        <a href="#services" class="nav-dropdown-link">Services</a>
        <a href="./customer-dashboard.html" class="nav-dropdown-link">My Reservations</a>
        <a href="./customer-profile.html" class="nav-dropdown-link">Profile</a>
        <a class="lf-link-small" href="/HFABS/backend/public/index.php?url=auth/logout">Logout</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-icon">
        <img src="../public/img/HFABS-LOGO.png" alt="Happy Face & Body Spa" />
      </div>

      <h1 class="hero-title">Relax. Refresh. Renew.</h1>
      <p class="hero-subtitle">Your beauty and wellness, simplified.</p>

      <a href="./customer-booking.html" class="cta-btn">
        Book an Appointment
      </a>
    </section>

    <section class="services" id="services">
      <h2 class="services-heading">Our Services</h2>

      <div class="services-grid">
        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/hair-treatment.jpg" alt="Hair Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Hair Services</h3>
            <p class="service-description">
              Expert hair styling, cutting, and coloring services to enhance your natural beauty and give you the
              perfect look.
            </p>
            <button class="service-btn">View Services</button>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/nail-services.jpg" alt="Nail Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Nail Services</h3>
            <p class="service-description">
              Professional manicure and pedicure treatments with premium products for beautifully polished nails.
            </p>
            <button class="service-btn">View Services</button>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/facial-services.webp" alt="Facial Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Facial Services</h3>
            <p class="service-description">
              Rejuvenating facial treatments designed to cleanse, refresh, and restore your skin's natural glow.
            </p>
            <button class="service-btn">View Services</button>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/massage-services.jpg" alt="Massage Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Massage Services</h3>
            <p class="service-description">
              Relaxing therapeutic massages to relieve stress, ease tension, and promote overall wellness.
            </p>
            <button class="service-btn">View Services</button>
          </div>
        </div>
      </div>
    </section>
  </main>
  <script src="../public/js/landing.js"></script>
  <script src="../public/js/burger-menu.js"></script>
  <script src="../public/js/universal-branch-modal.js"></script>
  <!-- Branch Selection Modal -->
  <div id="branchModal" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <h2 class="modal-title">Select Branch</h2>

      <div id="branchList" class="branch-list">
        <div class="branch-loading">Loading branches...</div>
      </div>
    </div>
  </div>
</body>

</html>