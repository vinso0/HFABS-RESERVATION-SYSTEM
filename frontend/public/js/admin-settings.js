// frontend/public/js/admin-settings.js

const API = '/HFABS/backend/public/index.php?url=branch';

// ── Calendar state ────────────────────────────────────────────────
let currentYear  = new Date().getFullYear();
let currentMonth = new Date().getMonth(); // 0-indexed
let closedDates  = []; // array of { id, closed_date, reason }
let selectedDate = null;

// ════════════════════════════════════════════════════════════════
//  INIT
// ════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  loadSettings();
  loadClosedDates();
  loadBlockedDays();
  renderCalendar();

  document.getElementById('openingTime')
    .addEventListener('change', e => updateTimeDisplay('openingTimeDisplay', e.target.value));
  document.getElementById('closingTime')
    .addEventListener('change', e => updateTimeDisplay('closingTimeDisplay', e.target.value));
});


// ════════════════════════════════════════════════════════════════
//  TABS
// ════════════════════════════════════════════════════════════════
function initTabs() {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });
}

// ════════════════════════════════════════════════════════════════
//  LOAD SETTINGS
// ════════════════════════════════════════════════════════════════
async function loadSettings() {
  try {
    const res  = await fetch(`${API}/settings`, { credentials: 'same-origin' });
    const data = await res.json();

    if (!data.success) { showToast(data.message || 'Failed to load settings.', 'error'); return; }

    const d = data.data;

    // General tab
    document.getElementById('branchLocation').value = d.branch_location  || '';
    updateLocationPreview(d.branch_location || '');
    document.getElementById('contactNumber').value  = d.contact_number   || '';
    document.getElementById('branchEmail').value    = d.email            || '';

    // Schedule tab
    document.getElementById('openingTime').value    = d.opening_time     || '';
    document.getElementById('closingTime').value    = d.closing_time     || '';
    document.getElementById('downpaymentRate').value = d.down_payment_rate ?? 30;

    updateTimeDisplay('openingTimeDisplay', d.opening_time);
    updateTimeDisplay('closingTimeDisplay', d.closing_time);

  } catch (err) {
    console.error('loadSettings:', err);
    showToast('Could not load branch settings.', 'error');
  }
}

// ════════════════════════════════════════════════════════════════
//  SAVE — GENERAL
// ════════════════════════════════════════════════════════════════
async function saveGeneralSettings(e) {
  e.preventDefault();
  const btn = document.getElementById('generalSaveBtn');
  setLoading(btn, true, 'Saving...');

  const payload = {
    branch_location: document.getElementById('branchLocation').value.trim(),
    contact_number:  document.getElementById('contactNumber').value.trim(),
    email:           document.getElementById('branchEmail').value.trim(),
    // pass current schedule values unchanged
    opening_time:    document.getElementById('openingTime').value,
    closing_time:    document.getElementById('closingTime').value,
    down_payment_rate: parseFloat(document.getElementById('downpaymentRate').value || 30),
  };

  await submitSettings(payload, btn, 'Saving...');
}

// ════════════════════════════════════════════════════════════════
//  SAVE — SCHEDULE
// ════════════════════════════════════════════════════════════════
async function saveScheduleSettings(e) {
  e.preventDefault();
  const btn = document.getElementById('scheduleSaveBtn');

  const opening = document.getElementById('openingTime').value;
  const closing = document.getElementById('closingTime').value;

  if (!opening || !closing) {
    showToast('Opening and closing time are required.', 'error'); return;
  }

  if (opening >= closing) {
    showToast('Opening time must be before closing time.', 'error'); return;
  }

  const rate = parseFloat(document.getElementById('downpaymentRate').value);
  if (isNaN(rate) || rate < 0 || rate > 100) {
    showToast('Downpayment rate must be between 0 and 100.', 'error'); return;
  }

  const payload = {
    branch_location:  document.getElementById('branchLocation').value.trim(),
    contact_number:   document.getElementById('contactNumber').value.trim(),
    email:            document.getElementById('branchEmail').value.trim(),
    opening_time:     opening,
    closing_time:     closing,
    down_payment_rate: rate,
  };

  await submitSettings(payload, btn, 'Saving...');
}

// ── Shared submit helper ──────────────────────────────────────
async function submitSettings(payload, btn, loadingText) {
  setLoading(btn, true, loadingText);
  try {
    const res  = await fetch(`${API}/updateSettings`, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      showToast('Settings saved successfully!', 'success');
      flashSaveIndicator();
    } else {
      showToast(data.message || 'Failed to save.', 'error');
    }
  } catch (err) {
    console.error('submitSettings:', err);
    showToast('Could not connect to server.', 'error');
  } finally {
    setLoading(btn, false, '<i class="fas fa-save"></i> Save Changes');
  }
}

// ════════════════════════════════════════════════════════════════
//  CLOSED DATES — Load
// ════════════════════════════════════════════════════════════════
async function loadClosedDates() {
  try {
    const res  = await fetch(`${API}/getClosedDates`, { credentials: 'same-origin' });
    const data = await res.json();

    if (!data.success) { showToast(data.message || 'Failed to load closed dates.', 'error'); return; }

    closedDates = data.data;
    renderClosedDatesList();
    renderCalendar();

  } catch (err) {
    console.error('loadClosedDates:', err);
  }
}

// ── Render the list ───────────────────────────────────────────
function renderClosedDatesList() {
  const container = document.getElementById('closedDatesList');
  const badge     = document.getElementById('datesBadge');
  badge.textContent = closedDates.length;

  if (!closedDates.length) {
    container.innerHTML = `
      <div class="dates-empty">
        <i class="fas fa-calendar-check"></i>
        No closed dates set. The branch is open every day.
      </div>`;
    return;
  }

  container.innerHTML = closedDates.map(item => `
    <div class="date-item" id="date-item-${item.id}">
      <div class="date-item-info">
        <div class="date-item-date">
          <i class="fas fa-calendar-day" style="color:#D91A7E;margin-right:6px;font-size:12px;"></i>
          ${formatDateDisplay(item.closed_date)}
        </div>
        ${item.reason
          ? `<div class="date-item-reason"><i class="fas fa-tag" style="margin-right:4px;"></i>${escapeHtml(item.reason)}</div>`
          : `<div class="date-item-reason" style="color:#d1d5db;">No reason specified</div>`
        }
      </div>
      <button class="btn-remove-date" onclick="removeClosedDate(${item.id})" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>
  `).join('');
}

// ════════════════════════════════════════════════════════════════
//  CLOSED DATES — Add
// ════════════════════════════════════════════════════════════════
async function addClosedDate() {
  const date   = document.getElementById('selectedDateValue').value;
  const reason = document.getElementById('closedReason').value.trim();

  if (!date) { showToast('Please select a date first.', 'error'); return; }

  const btn = document.getElementById('addDateBtn');
  setLoading(btn, true, 'Adding...');

  try {
    const res  = await fetch(`${API}/addClosedDate`, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify({ closed_date: date, reason }),
    });
    const data = await res.json();

    if (data.success) {
      showToast('Date marked as closed.', 'success');
      document.getElementById('selectedDateValue').value   = '';
      document.getElementById('selectedDateDisplay').value = '';
      document.getElementById('closedReason').value        = '';
      document.getElementById('addDateBtn').disabled       = true;
      selectedDate = null;
      await loadClosedDates();
    } else {
      showToast(data.message || 'Could not add date.', 'error');
    }
  } catch (err) {
    console.error('addClosedDate:', err);
    showToast('Could not connect to server.', 'error');
  } finally {
    setLoading(btn, false, '<i class="fas fa-plus"></i> Mark as Closed');
  }
}

// ════════════════════════════════════════════════════════════════
//  CLOSED DATES — Remove
// ════════════════════════════════════════════════════════════════
async function removeClosedDate(id) {
  if (!confirm('Remove this closed date?')) return;

  try {
    const res  = await fetch(`${API}/removeClosedDate`, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify({ id }),
    });
    const data = await res.json();

    if (data.success) {
      showToast('Closed date removed.', 'success');
      await loadClosedDates();
    } else {
      showToast(data.message || 'Failed to remove.', 'error');
    }
  } catch (err) {
    showToast('Could not connect to server.', 'error');
  }
}

// ════════════════════════════════════════════════════════════════
//  CALENDAR
// ════════════════════════════════════════════════════════════════
function renderCalendar() {
  const label    = document.getElementById('calMonthLabel');
  const grid     = document.getElementById('calGrid');
  const today    = new Date();
  today.setHours(0,0,0,0);

  const monthNames = ['January','February','March','April','May','June',
                      'July','August','September','October','November','December'];
  label.textContent = `${monthNames[currentMonth]} ${currentYear}`;

  const firstDay  = new Date(currentYear, currentMonth, 1).getDay();
  const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
  const closedSet = new Set(closedDates.map(d => d.closed_date));

  let html = '';

  // Empty cells for offset
  for (let i = 0; i < firstDay; i++) {
    html += `<div class="cal-day cal-empty"></div>`;
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const cellDate = new Date(currentYear, currentMonth, d);
    cellDate.setHours(0,0,0,0);

    let cls = 'cal-day';
    let onclick = '';

    if (cellDate < today) {
      cls += ' cal-past';
    } else if (closedSet.has(dateStr)) {
      cls += ' cal-closed';
    } else if (selectedDate === dateStr) {
      cls += ' cal-selected';
      onclick = `onclick="selectDate('${dateStr}')"`;
    } else {
      if (cellDate.getTime() === today.getTime()) cls += ' cal-today';
      onclick = `onclick="selectDate('${dateStr}')"`;
    }

    html += `<div class="${cls}" ${onclick}>${d}</div>`;
  }

  grid.innerHTML = html;
}

function changeMonth(dir) {
  currentMonth += dir;
  if (currentMonth < 0)  { currentMonth = 11; currentYear--; }
  if (currentMonth > 11) { currentMonth = 0;  currentYear++; }
  renderCalendar();
}

function selectDate(dateStr) {
  const closedSet = new Set(closedDates.map(d => d.closed_date));
  if (closedSet.has(dateStr)) return; // already closed, can't re-select

  selectedDate = dateStr;
  document.getElementById('selectedDateValue').value   = dateStr;
  document.getElementById('selectedDateDisplay').value = formatDateDisplay(dateStr);
  document.getElementById('addDateBtn').disabled       = false;
  renderCalendar();
}

// ════════════════════════════════════════════════════════════════
//  UTILITIES
// ════════════════════════════════════════════════════════════════
function updateTimeDisplay(elId, val) {
  if (!val) { document.getElementById(elId).textContent = '—'; return; }
  const [h, m] = val.split(':');
  const hr = parseInt(h);
  document.getElementById(elId).textContent =
    `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
}

function formatDateDisplay(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'long', day:'numeric' });
}

function escapeHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function setLoading(btn, loading, text) {
  btn.disabled   = loading;
  btn.innerHTML  = loading
    ? `<i class="fas fa-spinner fa-spin"></i> ${text}`
    : text;
}

function flashSaveIndicator() {
  const el = document.getElementById('saveIndicator');
  el.style.display = 'inline-flex';
  el.style.animation = 'none';
  setTimeout(() => {
    el.style.animation = 'fadeInOut 2.5s forwards';
    setTimeout(() => { el.style.display = 'none'; }, 2500);
  }, 10);
}

function showToast(msg, type = 'info') {
  const container = document.getElementById('toastContainer');
  const toast     = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
    <span>${msg}</span>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

// ════════════════════════════════════════════════════════════════
//  BLOCKED DAYS — Load
// ════════════════════════════════════════════════════════════════
async function loadBlockedDays() {
  try {
    const res  = await fetch(`${API}/getBlockedDays`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) return;

    const blockedSet = new Set(data.data.map(d => d.day_of_week));

    document.querySelectorAll('.blocked-day-cb').forEach(cb => {
      const checked = blockedSet.has(parseInt(cb.value));
      cb.checked = checked;
      cb.closest('.blocked-day-label').classList.toggle('is-blocked', checked);
    });

    // Toggle class on checkbox change for visual feedback
    document.querySelectorAll('.blocked-day-cb').forEach(cb => {
      cb.addEventListener('change', function () {
        this.closest('.blocked-day-label').classList.toggle('is-blocked', this.checked);
      });
    });

  } catch (err) {
    console.error('loadBlockedDays:', err);
  }
}

// ════════════════════════════════════════════════════════════════
//  BLOCKED DAYS — Save
// ════════════════════════════════════════════════════════════════
async function saveBlockedDays() {
  const btn = document.getElementById('saveBlockedDaysBtn');
  setLoading(btn, true, 'Saving...');

  const selected = [];
  document.querySelectorAll('.blocked-day-cb:checked').forEach(cb => {
    selected.push(parseInt(cb.value));
  });

  try {
    const res  = await fetch(`${API}/saveBlockedDays`, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json', Accept: 'application/json' },
      credentials: 'same-origin',
      body:        JSON.stringify({ blocked_days: selected }),
    });
    const data = await res.json();

    if (data.success) {
      showToast('Blocked days saved successfully!', 'success');
      flashSaveIndicator();
      renderCalendar(); // refresh calendar to reflect blocked days
    } else {
      showToast(data.message || 'Failed to save blocked days.', 'error');
    }
  } catch (err) {
    console.error('saveBlockedDays:', err);
    showToast('Could not connect to server.', 'error');
  } finally {
    setLoading(btn, false, '<i class="fas fa-save"></i> Save Blocked Days');
  }
}
