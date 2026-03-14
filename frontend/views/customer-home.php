<?php
require_once __DIR__ . '/../../backend/app/core/init.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../views/customer-login.html");
  exit;
}

// Pass first name to the page for personalized greeting
$rawUsername  = $_SESSION['user_name'] ?? 'Guest';
$firstName    = ucfirst(strtok($rawUsername, ' _-.'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Home | Happy Face & Body Spa</title>
  <link rel="stylesheet" href="../public/css/landing.css" />
  <link rel="stylesheet" href="../public/css/branch-detail.css" />
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

  <main>

    <!-- ── Hero ── -->
    <section class="hero-enhanced" id="home">
      <div class="hero-content-wrapper">
        <div class="hero-text-section">
          <span class="hero-eyebrow">
            Welcome back, <?= htmlspecialchars($firstName) ?> 👋
          </span>
          <h1 class="hero-title">Your Luxury Spa<br>Awaits You</h1>
          <p class="hero-subtitle">
            Relax. Refresh. Renew.<br>
            Book your next appointment in seconds.
          </p>
          <button class="cta-btn" onclick="document.getElementById('branchModal').classList.add('active')">
            <i class="fas fa-calendar-plus"></i> Book an Appointment
          </button>
        </div>

        <div class="hero-image-section">
          <img src="../public/img/hero-pic.jpg" alt="Spa Experience" />
        </div>
      </div>
    </section>

    <!-- ── Quick Actions ── -->
    <section class="quick-actions-section">
      <div class="quick-actions-inner">
        <a href="./customer-dashboard.php" class="quick-action-card">
          <div class="qa-icon"><i class="fas fa-calendar-check"></i></div>
          <span class="qa-label">My Reservations</span>
          <span class="qa-sub">View & manage bookings</span>
        </a>
        <a href="#services" class="quick-action-card" id="servicesQuickLink">
          <div class="qa-icon"><i class="fas fa-spa"></i></div>
          <span class="qa-label">Our Services</span>
          <span class="qa-sub">Browse treatments</span>
        </a>
        <a href="#branches" class="quick-action-card" id="branchesQuickLink">
          <div class="qa-icon"><i class="fas fa-map-marker-alt"></i></div>
          <span class="qa-label">Branches</span>
          <span class="qa-sub">Find a location</span>
        </a>
        <a href="./customer-profile.php" class="quick-action-card">
          <div class="qa-icon"><i class="fas fa-user-circle"></i></div>
          <span class="qa-label">My Profile</span>
          <span class="qa-sub">Account settings</span>
        </a>
      </div>
    </section>

    <!-- ── Services ── -->
    <section class="services services-gallery" id="services">
      <h2 class="services-heading">Our Services</h2>
      <p class="section-subtitle">Discover our comprehensive range of beauty and wellness treatments</p>

      <div class="services-grid">
        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/hair-treatment.jpg" alt="Hair Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Hair Services</h3>
            <p class="service-description">
              Expert hair styling, cutting, and coloring services to enhance your natural beauty.
            </p>
            <button class="service-btn" onclick="document.getElementById('branchModal').classList.add('active')">
              Book Now
            </button>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/nail-services.jpg" alt="Nail Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Nail Services</h3>
            <p class="service-description">
              Professional manicure and pedicure treatments with premium products.
            </p>
            <button class="service-btn" onclick="document.getElementById('branchModal').classList.add('active')">
              Book Now
            </button>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/facial-services.webp" alt="Facial Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Facial Services</h3>
            <p class="service-description">
              Rejuvenating facial treatments to cleanse, refresh, and restore your skin's glow.
            </p>
            <button class="service-btn" onclick="document.getElementById('branchModal').classList.add('active')">
              Book Now
            </button>
          </div>
        </div>

        <div class="service-card">
          <div class="service-image">
            <img src="../public/img/massage-services.jpg" alt="Massage Services" />
          </div>
          <div class="service-content">
            <h3 class="service-title">Massage Services</h3>
            <p class="service-description">
              Relaxing therapeutic massages to relieve stress, ease tension, and promote wellness.
            </p>
            <button class="service-btn" onclick="document.getElementById('branchModal').classList.add('active')">
              Book Now
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Branches ── -->
    <section class="branches-section" id="branches">
      <div class="branches-section-inner">
        <span class="section-eyebrow">Find Us</span>
        <h2 class="section-heading">Our <span class="text-pink">Branches</span></h2>
        <p class="section-subtitle">Visit any of our locations for a premium spa experience</p>

        <div id="branchesGrid" class="branches-grid">
          <div class="branch-card-skeleton"></div>
          <div class="branch-card-skeleton"></div>
          <div class="branch-card-skeleton"></div>
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
          <h4>Happy Face & Body Spa</h4>
        </div>
        <p>Your premier destination for beauty and wellness. Experience luxury and relaxation in every visit.</p>
      </div>

      <div class="footer-section">
        <h4>Quick Links</h4>
        <a href="#home">Home</a>
        <a href="#services">Services</a>
        <a href="#branches">Branches</a>
        <a href="./customer-dashboard.php">My Reservations</a>
        <a href="./customer-profile.php">Profile</a>
      </div>

      <div class="footer-section">
        <h4>Our Branches</h4>
        <div id="branches-list" class="branches-list">
          <div class="branch-loading">Loading branches...</div>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2026 Happy Face & Body Spa. All rights reserved.</p>
    </div>
  </footer>

  <!-- Branch Selection Modal (for booking) -->
  <div id="branchModal" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <h2 class="modal-title">Select Branch</h2>
      <div id="branchList" class="branch-list">
        <div class="branch-loading">Loading branches...</div>
      </div>
    </div>
  </div>

  <script src="../public/js/landing.js"></script>
  <script src="../public/js/universal-branch-modal.js"></script>
  <script src="../public/js/load-footer-branches.js"></script>
  <script src="../public/js/navbar-loader.js"></script>

  <script>
    // Wait for full DOM before attaching scroll listeners
    window.addEventListener('load', () => {

      ['servicesQuickLink', 'branchesQuickLink'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const targetId = el.getAttribute('href').replace('#', '');
          const target = document.getElementById(targetId);
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      });

    });
  </script>

</body>
</html>
