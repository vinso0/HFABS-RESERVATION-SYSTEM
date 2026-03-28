<?php
require_once __DIR__ . '/../../backend/app/core/init.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../views/customer-login.html");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About | Happy Face & Body Spa</title>
  <link rel="stylesheet" href="../public/css/landing.css" />
  <link rel="stylesheet" href="../public/css/customer-about.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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

  <div id="navbar-container"></div>

  <main class="about-main">

    <!-- ── About Hero ── -->
    <section class="about-hero">
      <div class="about-hero-inner">
        <span class="section-eyebrow"><i class="fas fa-spa"></i> Who We Are</span>
        <h1 class="about-hero-title" id="aboutTitle">About Happy Face &amp; Body Spa</h1>
        <p class="about-hero-subtitle" id="aboutDescription">Loading...</p>
      </div>
    </section>

    <!-- ── Vision & Mission ── -->
    <section class="vm-section">
      <div class="vm-grid">
        <div class="vm-card" id="visionCard">
          <div class="vm-icon"><i class="fas fa-eye"></i></div>
          <h3>Our Vision</h3>
          <p id="aboutVision">Loading...</p>
        </div>
        <div class="vm-card" id="missionCard">
          <div class="vm-icon"><i class="fas fa-heart"></i></div>
          <h3>Our Mission</h3>
          <p id="aboutMission">Loading...</p>
        </div>
      </div>
    </section>

    <!-- ── Policies Accordion ── -->
    <section class="policies-section">
      <div class="policies-inner">
        <span class="section-eyebrow"><i class="fas fa-file-alt"></i> Guidelines</span>
        <h2 class="section-heading">Our <span class="text-pink">Policies</span></h2>
        <p class="section-subtitle">Please read our policies to ensure a smooth and enjoyable experience.</p>

        <div class="accordion" id="policiesAccordion">
          <!-- Injected by JS -->
          <div class="accordion-loading">
            <i class="fas fa-spinner fa-spin"></i> Loading policies...
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section">
        <div class="footer-brand">
          <img src="../public/img/HFABS-LOGO.png" alt="Happy Face & Body Spa" />
          <h4>Happy Face &amp; Body Spa</h4>
        </div>
        <p>Your premier destination for beauty and wellness. Experience luxury and relaxation in every visit.</p>
      </div>
      <div class="footer-section">
        <h4>Quick Links</h4>
        <a href="/HFABS/frontend/views/customer-home.php">Home</a>
        <a href="#services">Services</a>
        <a href="/HFABS/frontend/views/customer-about.php">About</a>
        <a href="/HFABS/frontend/views/customer-dashboard.php">My Reservations</a>
        <a href="/HFABS/frontend/views/customer-profile.php">Profile</a>
      </div>
      <div class="footer-section">
        <h4>Our Branches</h4>
        <div id="branches-list" class="branches-list">
          <div class="branch-loading">Loading branches...</div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 Happy Face &amp; Body Spa. All rights reserved.</p>
    </div>
  </footer>

  <script src="../public/js/navbar-loader.js"></script>
  <script src="../public/js/load-footer-branches.js"></script>
  <script src="../public/js/customer-about.js"></script>

</body>
</html>