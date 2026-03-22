<?php
require_once __DIR__ . '/../../backend/app/core/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
  header("Location: customer-login.html");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile | Happy Face &amp; Body Spa</title>

  <link rel="stylesheet" href="../public/css/landing.css" />
  <link rel="stylesheet" href="../public/css/customer-profile.css" />
  <link rel="stylesheet" href="../public/css/toast.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body>
  <!-- Bubble Background (consistent with all customer pages) -->
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

  <!-- Navbar -->
  <div id="navbar-container"></div>

  <main class="profile-main">
    <div class="profile-container">

      <!-- ── Profile Header ── -->
      <div class="profile-header-card">
        <div class="profile-avatar">
          <i class="fas fa-user-circle"></i>
        </div>
        <div class="profile-header-info">
          <h1 class="profile-name" id="displayName">Loading...</h1>
          <span class="profile-role-badge">
            <i class="fas fa-spa"></i> Customer
          </span>
          <p class="profile-member-since" id="displayMemberSince">
            <i class="fas fa-calendar-alt"></i> Member since —
          </p>
        </div>
        <a href="./customer-dashboard.php" class="btn-back">
          <i class="fas fa-calendar-check"></i> My Reservations
        </a>
      </div>

      <!-- ── Info + Edit Sections ── -->
      <div class="profile-body">

        <!-- Account Information (Read-only view) -->
        <div class="profile-card" id="viewSection">
          <div class="profile-card-header">
            <h2><i class="fas fa-id-card"></i> Account Information</h2>
            <button class="btn-edit" id="editBtn" onclick="toggleEdit()">
              <i class="fas fa-pen"></i> Edit Profile
            </button>
          </div>
          <div class="profile-card-divider"></div>

          <div class="info-grid">
            <div class="info-item">
              <span class="info-label"><i class="fas fa-user"></i> Full Name</span>
              <span class="info-value" id="viewName">—</span>
            </div>
            <div class="info-item">
              <span class="info-label"><i class="fas fa-envelope"></i> Email Address</span>
              <span class="info-value" id="viewEmail">—</span>
            </div>
            <div class="info-item">
              <span class="info-label"><i class="fas fa-phone"></i> Contact Number</span>
              <span class="info-value" id="viewContact">—</span>
            </div>
            <div class="info-item">
              <span class="info-label"><i class="fas fa-shield-alt"></i> Account Status</span>
              <span class="info-value" id="viewStatus">—</span>
            </div>
          </div>
        </div>

        <!-- Edit Profile Form (hidden by default) -->
        <div class="profile-card" id="editSection" style="display:none;">
          <div class="profile-card-header">
            <h2><i class="fas fa-pen-to-square"></i> Edit Profile</h2>
            <button class="btn-cancel-edit" onclick="toggleEdit()">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
          <div class="profile-card-divider"></div>

          <form id="editProfileForm" onsubmit="submitEditProfile(event)">
            <div class="form-grid">
              <div class="form-group">
                <label for="editName"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" id="editName" name="username" placeholder="Enter your full name" required />
              </div>
              <div class="form-group">
                <label for="editEmail"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="editEmail" name="email" placeholder="Enter your email" required />
              </div>
              <div class="form-group">
                <label for="editContact"><i class="fas fa-phone"></i> Contact Number</label>
                <input type="text" id="editContact" name="contact_number" placeholder="e.g. 09123456789" maxlength="15" />
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-save">
                <i class="fas fa-check"></i> Save Changes
              </button>
            </div>
          </form>
        </div>

        <!-- Change Password Card -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h2><i class="fas fa-lock"></i> Change Password</h2>
          </div>
          <div class="profile-card-divider"></div>

          <form id="changePasswordForm" onsubmit="submitChangePassword(event)">
            <div class="form-grid">
              <div class="form-group">
                <label for="currentPassword"><i class="fas fa-key"></i> Current Password</label>
                <div class="password-input-wrapper">
                  <input type="password" id="currentPassword" name="current_password" placeholder="Enter current password" required />
                  <button type="button" class="toggle-password" onclick="togglePasswordVisibility('currentPassword', this)">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label for="newPassword"><i class="fas fa-lock"></i> New Password</label>
                <div class="password-input-wrapper">
                  <input type="password" id="newPassword" name="new_password" placeholder="Enter new password" required />
                  <button type="button" class="toggle-password" onclick="togglePasswordVisibility('newPassword', this)">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm New Password</label>
                <div class="password-input-wrapper">
                  <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter new password" required />
                  <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirmPassword', this)">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-save">
                <i class="fas fa-check"></i> Update Password
              </button>
            </div>
          </form>
        </div>

      </div>
      <!-- /.profile-body -->

    </div>
    <!-- /.profile-container -->
  </main>

  <script src="../public/js/navbar-loader.js"></script>
  <script src="../public/js/toast.js"></script>
  <script src="../public/js/customer-profile.js"></script>
</body>

</html>
