<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.html");
    exit;
}
$branchName  = $_SESSION['branch_name'] ?? 'Your Branch';
$displayRole = ucfirst($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Branch Settings | Happy Face &amp; Body Spa</title>
  <link rel="stylesheet" href="../public/css/admin-sidebar.css" />
  <link rel="stylesheet" href="../public/css/admin-navbar.css" />
  <link rel="stylesheet" href="../public/css/admin-settings.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
  <?php include 'components/admin-sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/admin-navbar.php'; ?>

    <section class="settings-section">

      <!-- Page Header -->
      <div class="settings-header">
        <div>
          <h2><i class="fas fa-sliders-h"></i> Branch Settings</h2>
          <p class="settings-subtitle">
            <i class="fas fa-map-marker-alt"></i>
            <?= htmlspecialchars($branchName) ?> &nbsp;·&nbsp; Manage your branch configuration
          </p>
        </div>
        <div id="saveIndicator" class="save-indicator" style="display:none;">
          <i class="fas fa-check-circle"></i> Saved
        </div>
      </div>

      <!-- ── Tab Navigation ── -->
      <div class="settings-tabs">
        <button class="tab-btn active" data-tab="general">
          <i class="fas fa-store"></i> General Info
        </button>
        <button class="tab-btn" data-tab="schedule">
          <i class="fas fa-clock"></i> Schedule & Rates
        </button>
        <button class="tab-btn" data-tab="closedDates">
          <i class="fas fa-calendar-times"></i> Closed Dates
        </button>
      </div>

      <!-- ══════════════════════════════════════════
           TAB 1 — General Info
      ══════════════════════════════════════════ -->
      <div class="tab-panel active" id="tab-general">
        <form id="generalForm" class="settings-card" onsubmit="saveGeneralSettings(event)">
          <div class="settings-card-header">
            <h3><i class="fas fa-store"></i> Branch Information</h3>
          </div>
          <div class="settings-card-divider"></div>

          <div class="form-grid-2">
            <div class="form-group full-width">
              <label><i class="fas fa-map-marker-alt"></i> Branch Address</label>
              <input type="text" id="branchLocation" name="branch_location"
                     placeholder="e.g. 123 Main St, Quezon City" />
            </div>
            <div class="form-group">
              <label><i class="fas fa-phone"></i> Contact Number</label>
              <input type="text" id="contactNumber" name="contact_number"
                     placeholder="e.g. 09123456789" maxlength="15" />
            </div>
            <div class="form-group">
              <label><i class="fas fa-envelope"></i> Email Address</label>
              <input type="email" id="branchEmail" name="email"
                     placeholder="e.g. branch@hfabs.com" />
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-save" id="generalSaveBtn">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </div>
        </form>
      </div>

      <!-- ══════════════════════════════════════════
           TAB 2 — Schedule & Rates
      ══════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-schedule">
        <form id="scheduleForm" class="settings-card" onsubmit="saveScheduleSettings(event)">
          <div class="settings-card-header">
            <h3><i class="fas fa-clock"></i> Operating Hours &amp; Rates</h3>
          </div>
          <div class="settings-card-divider"></div>

          <div class="form-grid-2">
            <div class="form-group">
              <label><i class="fas fa-door-open"></i> Opening Time</label>
              <input type="time" id="openingTime" name="opening_time" />
              <span class="input-hint" id="openingTimeDisplay">—</span>
            </div>
            <div class="form-group">
              <label><i class="fas fa-door-closed"></i> Closing Time</label>
              <input type="time" id="closingTime" name="closing_time" />
              <span class="input-hint" id="closingTimeDisplay">—</span>
            </div>
            <div class="form-group">
              <label><i class="fas fa-percent"></i> Downpayment Rate (%)</label>
              <div class="input-with-suffix">
                <input type="number" id="downpaymentRate" name="down_payment_rate"
                       min="0" max="100" step="0.01" placeholder="e.g. 30" />
                <span class="input-suffix">%</span>
              </div>
              <span class="input-hint">Percentage of total charged as downpayment</span>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-save" id="scheduleSaveBtn">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </div>
        </form>
      </div>

      <!-- ══════════════════════════════════════════
          TAB 3 — Closed Dates
      ══════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-closedDates">
        <div class="settings-two-col">

          <!-- Calendar Picker Panel -->
          <div class="settings-card">
            <div class="settings-card-header">
              <h3><i class="fas fa-calendar-plus"></i> Mark Date as Closed</h3>
            </div>
            <div class="settings-card-divider"></div>

            <div class="calendar-wrapper">
              <div class="calendar-nav">
                <button type="button" class="cal-nav-btn" onclick="changeMonth(-1)">
                  <i class="fas fa-chevron-left"></i>
                </button>
                <span id="calMonthLabel" class="cal-month-label">—</span>
                <button type="button" class="cal-nav-btn" onclick="changeMonth(1)">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </div>

              <div class="calendar-grid-header">
                <span>Sun</span><span>Mon</span><span>Tue</span>
                <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
              </div>
              <div class="calendar-grid" id="calGrid"></div>
            </div>

            <!-- Add date form -->
            <div class="closed-date-form">
              <div class="form-group">
                <label><i class="fas fa-calendar-day"></i> Selected Date</label>
                <input type="text" id="selectedDateDisplay" readonly
                      placeholder="Click a date above to select" />
                <input type="hidden" id="selectedDateValue" />
              </div>
              <div class="form-group">
                <label><i class="fas fa-tag"></i> Reason <span class="optional-label">(optional)</span></label>
                <input type="text" id="closedReason" placeholder="e.g. Holiday, Maintenance..." maxlength="255" />
              </div>
              <button type="button" class="btn-add-date" id="addDateBtn"
                      onclick="addClosedDate()" disabled>
                <i class="fas fa-plus"></i> Mark as Closed
              </button>
            </div>
          </div>

          <!-- Right column: stacked cards -->
          <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- ── NEW: Recurring Blocked Days Card ── -->
            <div class="settings-card">
              <div class="settings-card-header">
                <h3><i class="fas fa-calendar-week"></i> Recurring Blocked Days</h3>
              </div>
              <div class="settings-card-divider"></div>
              <p style="font-size:13px;color:#6b7280;margin-bottom:14px;">
                Select days of the week that are <strong>always closed</strong>. Customers will not be able to book on these days.
              </p>

              <div class="blocked-days-grid" id="blockedDaysGrid">
                <?php
                $dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                foreach ($dayNames as $i => $name):
                ?>
                <label class="blocked-day-label" id="day-label-<?= $i ?>">
                  <input type="checkbox" class="blocked-day-cb" value="<?= $i ?>" id="day-<?= $i ?>" />
                  <span class="blocked-day-name"><?= $name ?></span>
                </label>
                <?php endforeach; ?>
              </div>

              <div class="form-actions" style="margin-top:16px;">
                <button type="button" class="btn-save" id="saveBlockedDaysBtn" onclick="saveBlockedDays()">
                  <i class="fas fa-save"></i> Save Blocked Days
                </button>
              </div>
            </div>

            <!-- Closed Dates List Panel -->
            <div class="settings-card">
              <div class="settings-card-header">
                <h3><i class="fas fa-calendar-times"></i> Closed Dates</h3>
                <span class="dates-count-badge" id="datesBadge">0</span>
              </div>
              <div class="settings-card-divider"></div>

              <div id="closedDatesList" class="closed-dates-list">
                <div class="dates-loading">
                  <div class="dash-spinner"></div> Loading...
                </div>
              </div>
            </div>

          </div><!-- /.right column -->

        </div>
      </div>
      <!-- /.tab-panel -->


    </section>
  </main>

  <!-- Toast container (reuse admin toast pattern) -->
  <div id="toastContainer" class="toast-container"></div>

  <script src="../public/js/admin-settings.js"></script>
</body>
</html>
