<?php
require_once __DIR__ . '/../../backend/app/core/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
  header("Location: superadmin-login.html");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage About | HFABS Superadmin</title>
  <link rel="stylesheet" href="../public/css/superadmin.css" />
  <link rel="stylesheet" href="../public/css/superadmin-about.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>

  <?php include 'components/superadmin-sidebar.php'; ?>

  <div class="main-content">
    <?php include 'components/superadmin-navbar.php'; ?>

    <div class="page-content">

      <!-- ── Page Header ── -->
      <div class="page-header">
        <div>
          <h1 class="page-title"><i class="fas fa-info-circle"></i> Manage About Page</h1>
          <p class="page-subtitle">Update the About content and manage policies displayed to customers.</p>
        </div>
      </div>

      <!-- ── About Content Editor ── -->
      <div class="sa-card">
        <div class="sa-card-header">
          <h2><i class="fas fa-edit"></i> About Content</h2>
        </div>
        <div class="sa-card-body">
          <form id="aboutForm">
            <div class="form-group">
              <label for="aboutTitle">Section Title</label>
              <input type="text" id="aboutTitle" class="form-control" placeholder="About Happy Face & Body Spa" />
            </div>
            <div class="form-group">
              <label for="aboutDescription">Description <span class="required">*</span></label>
              <textarea id="aboutDescription" class="form-control" rows="5" placeholder="Enter the main description..."></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="aboutVision">Vision</label>
                <textarea id="aboutVision" class="form-control" rows="3" placeholder="Our vision..."></textarea>
              </div>
              <div class="form-group">
                <label for="aboutMission">Mission</label>
                <textarea id="aboutMission" class="form-control" rows="3" placeholder="Our mission..."></textarea>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Save About Content
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Policies Manager ── -->
      <div class="sa-card">
        <div class="sa-card-header">
          <h2><i class="fas fa-list-alt"></i> Manage Policies</h2>
          <button class="btn-primary btn-sm" id="addPolicyBtn">
            <i class="fas fa-plus"></i> Add Policy
          </button>
        </div>
        <div class="sa-card-body">
          <div id="policiesTable">
            <div class="loading-text"><i class="fas fa-spinner fa-spin"></i> Loading policies...</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Add/Edit Policy Modal ── -->
  <div id="policyModal" class="sa-modal-overlay" style="display:none;">
    <div class="sa-modal">
      <div class="sa-modal-header">
        <h3 id="modalTitle">Add Policy</h3>
        <button class="modal-close-btn" id="closePolicyModal">&times;</button>
      </div>
      <div class="sa-modal-body">
        <input type="hidden" id="editPolicyId" value="" />
        <div class="form-group">
          <label for="policyTitle">Policy Title <span class="required">*</span></label>
          <input type="text" id="policyTitle" class="form-control" placeholder="e.g., Cancellation Policy" />
        </div>
        <div class="form-group">
          <label for="policyContent">Policy Content <span class="required">*</span></label>
          <textarea id="policyContent" class="form-control" rows="5" placeholder="Describe the policy..."></textarea>
        </div>
        <div class="form-group">
          <label for="policySortOrder">Sort Order</label>
          <input type="number" id="policySortOrder" class="form-control" value="0" min="0" />
        </div>
        <div class="form-group" id="activeToggleGroup" style="display:none;">
          <label>
            <input type="checkbox" id="policyIsActive" checked />
            Active (visible to customers)
          </label>
        </div>
      </div>
      <div class="sa-modal-footer">
        <button class="btn-secondary" id="cancelPolicyModal">Cancel</button>
        <button class="btn-primary" id="savePolicyBtn"><i class="fas fa-save"></i> Save Policy</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="toastContainer" class="toast-container"></div>

  <script src="../public/js/superadmin-about.js"></script>

</body>
</html>