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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>

  <?php include 'components/superadmin-sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/superadmin-navbar.php'; ?>

    <div class="page-content">

      <!-- ── Page Heading ── -->
      <div class="section-heading">
        <h2><i class="fas fa-info-circle" style="color:#d91a7e;margin-right:8px;"></i>Manage About Page</h2>
        <p>Update the About content and manage policies displayed to customers.</p>
      </div>

      <!-- ── About Content Editor ── -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title"><i class="fas fa-edit"></i> About Content</span>
        </div>
        <div class="panel-body">
          <form id="aboutForm">
            <div class="form-group">
              <label class="form-label">Section Title</label>
              <input type="text" id="aboutTitle" class="form-control" placeholder="About Happy Face &amp; Body Spa" />
            </div>
            <div class="form-group">
              <label class="form-label">Description <span style="color:#d91a7e;">*</span></label>
              <textarea id="aboutDescription" class="form-control" rows="5" placeholder="Enter the main description..."></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Vision</label>
                <textarea id="aboutVision" class="form-control" rows="3" placeholder="Our vision..."></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Mission</label>
                <textarea id="aboutMission" class="form-control" rows="3" placeholder="Our mission..."></textarea>
              </div>
            </div>
            <div class="modal-footer" style="padding:0;margin-top:6px;border-top:none;">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save About Content
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Policies Manager ── -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title"><i class="fas fa-list-alt"></i> Manage Policies</span>
          <button class="btn btn-primary btn-sm" id="addPolicyBtn">
            <i class="fas fa-plus"></i> Add Policy
          </button>
        </div>
        <div class="panel-body" style="padding:0;">
          <div id="policiesTable">
            <div class="empty-state">
              <i class="fas fa-spinner fa-spin"></i>
              <p>Loading policies...</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- ── Add/Edit Policy Modal ── -->
  <div id="policyModal" class="modal-overlay">
    <div class="modal" style="max-width:560px;">
      <div class="modal-header">
        <h3 class="modal-title" id="modalTitle">Add Policy</h3>
        <button class="modal-close" id="closePolicyModal"><i class="fas fa-times"></i></button>
      </div>
      <input type="hidden" id="editPolicyId" value="" />
      <div class="form-group">
        <label class="form-label">Policy Title <span style="color:#d91a7e;">*</span></label>
        <input type="text" id="policyTitle" class="form-control" placeholder="e.g., Cancellation Policy" />
      </div>
      <div class="form-group">
        <label class="form-label">Policy Content <span style="color:#d91a7e;">*</span></label>
        <textarea id="policyContent" class="form-control" rows="5" placeholder="Describe the policy..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Sort Order</label>
        <input type="number" id="policySortOrder" class="form-control" value="0" min="0" />
      </div>
      <div class="form-group" id="activeToggleGroup" style="display:none;">
        <label class="form-label" style="flex-direction:row;align-items:center;gap:8px;display:flex;font-weight:500;">
          <input type="checkbox" id="policyIsActive" checked />
          Active (visible to customers)
        </label>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" id="cancelPolicyModal">Cancel</button>
        <button class="btn btn-primary" id="savePolicyBtn"><i class="fas fa-save"></i> Save Policy</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="toastContainer" class="toast-container"></div>

  <script src="../public/js/superadmin-about.js"></script>

</body>
</html>