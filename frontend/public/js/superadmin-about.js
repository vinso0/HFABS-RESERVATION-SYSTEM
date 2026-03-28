document.addEventListener('DOMContentLoaded', () => {
  loadAboutContent();
  loadAllPolicies();

  document.getElementById('aboutForm').addEventListener('submit', saveAbout);
  document.getElementById('addPolicyBtn').addEventListener('click', openAddModal);
  document.getElementById('closePolicyModal').addEventListener('click', closeModal);
  document.getElementById('cancelPolicyModal').addEventListener('click', closeModal);
  document.getElementById('savePolicyBtn').addEventListener('click', savePolicy);
});

const API = '/HFABS/backend/public/index.php?url=about/';

// ── About Content ─────────────────────────────────────────────────────────────
function loadAboutContent() {
  fetch(API + 'getAbout')
    .then(r => r.json())
    .then(({ success, data }) => {
      if (!success || !data) return;
      document.getElementById('aboutTitle').value       = data.title       || '';
      document.getElementById('aboutDescription').value = data.description || '';
      document.getElementById('aboutVision').value      = data.vision      || '';
      document.getElementById('aboutMission').value     = data.mission     || '';
    });
}

function saveAbout(e) {
  e.preventDefault();
  const payload = {
    title:       document.getElementById('aboutTitle').value.trim(),
    description: document.getElementById('aboutDescription').value.trim(),
    vision:      document.getElementById('aboutVision').value.trim(),
    mission:     document.getElementById('aboutMission').value.trim(),
  };
  if (!payload.description) { showToast('Description is required.', 'error'); return; }

  fetch(API + 'saveAbout', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
    .then(r => r.json())
    .then(({ success, message }) => showToast(message, success ? 'success' : 'error'));
}

// ── Policies ──────────────────────────────────────────────────────────────────
function loadAllPolicies() {
  const container = document.getElementById('policiesTable');
  container.innerHTML = '<div class="loading-text"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

  fetch(API + 'getAllPolicies')
    .then(r => r.json())
    .then(({ success, data }) => {
      if (!success || !data || data.length === 0) {
        container.innerHTML = '<p class="no-data">No policies found. Add one above.</p>';
        return;
      }
      let html = `
        <table class="sa-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Sort Order</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
      `;
      data.forEach((p, i) => {
        html += `
          <tr>
            <td>${i + 1}</td>
            <td>${escHtml(p.title)}</td>
            <td>${p.sort_order}</td>
            <td>
              <span class="badge ${p.is_active == 1 ? 'badge-success' : 'badge-inactive'}">
                ${p.is_active == 1 ? 'Active' : 'Hidden'}
              </span>
            </td>
            <td>
              <button class="btn-icon btn-edit" onclick="openEditModal(${p.policy_id},'${escHtml(p.title).replace(/'/g,"\\'")}','${escHtml(p.content).replace(/'/g,"\\'")}',${p.sort_order},${p.is_active})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-icon btn-delete" onclick="deletePolicy(${p.policy_id})">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      });
      html += '</tbody></table>';
      container.innerHTML = html;
    });
}

function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add Policy';
  document.getElementById('editPolicyId').value     = '';
  document.getElementById('policyTitle').value      = '';
  document.getElementById('policyContent').value    = '';
  document.getElementById('policySortOrder').value  = '0';
  document.getElementById('activeToggleGroup').style.display = 'none';
  document.getElementById('policyModal').style.display = 'flex';
}

function openEditModal(id, title, content, order, isActive) {
  document.getElementById('modalTitle').textContent      = 'Edit Policy';
  document.getElementById('editPolicyId').value          = id;
  document.getElementById('policyTitle').value           = title;
  document.getElementById('policyContent').value         = content;
  document.getElementById('policySortOrder').value       = order;
  document.getElementById('policyIsActive').checked      = isActive == 1;
  document.getElementById('activeToggleGroup').style.display = 'block';
  document.getElementById('policyModal').style.display   = 'flex';
}

function closeModal() {
  document.getElementById('policyModal').style.display = 'none';
}

function savePolicy() {
  const id      = document.getElementById('editPolicyId').value;
  const title   = document.getElementById('policyTitle').value.trim();
  const content = document.getElementById('policyContent').value.trim();
  const order   = parseInt(document.getElementById('policySortOrder').value) || 0;
  const active  = document.getElementById('policyIsActive').checked ? 1 : 0;

  if (!title || !content) { showToast('Title and content are required.', 'error'); return; }

  const endpoint = id ? 'updatePolicy' : 'addPolicy';
  const payload  = id
    ? { policy_id: id, title, content, sort_order: order, is_active: active }
    : { title, content, sort_order: order };

  fetch(API + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
    .then(r => r.json())
    .then(({ success, message }) => {
      showToast(message, success ? 'success' : 'error');
      if (success) { closeModal(); loadAllPolicies(); }
    });
}

function deletePolicy(id) {
  if (!confirm('Delete this policy? This cannot be undone.')) return;
  fetch(API + 'deletePolicy', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ policy_id: id }),
  })
    .then(r => r.json())
    .then(({ success, message }) => {
      showToast(message, success ? 'success' : 'error');
      if (success) loadAllPolicies();
    });
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function escHtml(text) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(text));
  return d.innerHTML;
}

function showToast(msg, type = 'success') {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
  container.appendChild(toast);
  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3500);
}