/**
 * customer-wishlist-tab.js
 * Handles the My Wishlist tab inside customer-dashboard.php
 */

const API_BASE = '/HFABS/backend/public/index.php?url=';

let allWishlistItems = [];   // raw data from API
let currentFilter    = 'all';

// ── Tab switching ─────────────────────────────────────────────
document.querySelectorAll('.dash-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.dash-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.dash-panel').forEach(p => p.classList.remove('active'));

    tab.classList.add('active');
    const panelId = 'panel-' + tab.dataset.tab;
    document.getElementById(panelId).classList.add('active');

    // Load wishlist when tab is first opened
    if (tab.dataset.tab === 'wishlist' && allWishlistItems.length === 0) {
      loadWishlist();
    }
  });
});

// ── Filter buttons ────────────────────────────────────────────
document.querySelectorAll('.wl-filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.wl-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    renderWishlist();
  });
});

// ── Load wishlist from API ────────────────────────────────────
async function loadWishlist() {
  const grid = document.getElementById('wlGrid');
  grid.innerHTML = `
    <div class="wl-loading">
      <div class="loading-spinner"></div>
      <p>Loading wishlist...</p>
    </div>`;

  try {
    const res  = await fetch(`${API_BASE}wishlist/mine`);
    const data = await res.json();

    if (!data.success) throw new Error(data.error || 'Failed to load');

    allWishlistItems = data.data;
    updateBadge(allWishlistItems.length);
    renderWishlist();

  } catch (err) {
    console.error('[Wishlist Tab]', err);
    grid.innerHTML = `
      <div class="wl-empty">
        <i class="fas fa-exclamation-circle"></i>
        <h3>Could not load wishlist</h3>
        <p>Please refresh the page and try again.</p>
      </div>`;
  }
}

// ── Render cards ──────────────────────────────────────────────
function renderWishlist() {
  const grid = document.getElementById('wlGrid');

  const filtered = currentFilter === 'all'
    ? allWishlistItems
    : allWishlistItems.filter(item => item.wishlist_type === currentFilter);

  if (filtered.length === 0) {
    grid.innerHTML = `
      <div class="wl-empty">
        <i class="fas fa-heart"></i>
        <h3>${currentFilter === 'all' ? 'No saved items yet' : 'No ' + currentFilter + 's saved'}</h3>
        <p>Browse our branches and tap the heart icon to save items here.</p>
        <a href="/HFABS/frontend/index.html#branches">
          <i class="fas fa-search"></i> Explore Branches
        </a>
      </div>`;
    return;
  }

  grid.innerHTML = filtered.map(item => buildCard(item)).join('');

  // Attach remove listeners
  grid.querySelectorAll('.wl-remove-btn').forEach(btn => {
    btn.addEventListener('click', () => handleRemove(btn));
  });
}

// ── Build a single wishlist card ──────────────────────────────
function buildCard(item) {
  const isService  = item.wishlist_type === 'service';
  const name       = isService ? (item.service_name || 'Service') : (item.package_name || 'Package');
  const branchName = item.branch_name || 'Branch';
  const branchId   = item.branch_id;
  const bookUrl    = `/HFABS/frontend/views/branch-detail.html?id=${branchId}`;

  const badgeClass = isService ? 'service' : 'package';
  const badgeIcon  = isService ? 'fa-spa' : 'fa-box-open';
  const badgeLabel = isService ? 'Service' : 'Package';

  const savedDate  = item.created_at
    ? new Date(item.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
    : '';

  // data attributes for remove handler
  const dataAttrs = isService
    ? `data-type="service" data-id="${item.branch_service_override_id}" data-branch="${branchId}"`
    : `data-type="package" data-id="${item.package_id}" data-branch="${branchId}"`;

  return `
    <div class="wl-card" id="wl-card-${isService ? 'svc-' + item.branch_service_override_id : 'pkg-' + item.package_id}">
      <div class="wl-card-body">
        <div class="wl-card-top">
          <span class="wl-card-badge ${badgeClass}">
            <i class="fas ${badgeIcon}"></i> ${badgeLabel}
          </span>
        </div>
        <h4 class="wl-card-name">${escHtml(name)}</h4>
        <div class="wl-card-branch">
          <i class="fas fa-map-marker-alt"></i>
          ${escHtml(branchName)}
        </div>
        ${savedDate ? `<div class="wl-card-date"><i class="far fa-clock"></i> Saved ${savedDate}</div>` : ''}
      </div>
      <div class="wl-card-footer">
        <a href="${bookUrl}" class="wl-book-btn" target="_self">
          <i class="fas fa-calendar-plus"></i> Book Now
        </a>
        <button class="wl-remove-btn" title="Remove from wishlist" ${dataAttrs}>
          <i class="fas fa-heart-broken"></i>
        </button>
      </div>
    </div>`;
}

// ── Handle remove ─────────────────────────────────────────────
async function handleRemove(btn) {
  const type     = btn.dataset.type;
  const id       = parseInt(btn.dataset.id, 10);
  const branchId = parseInt(btn.dataset.branch, 10);

  // Optimistic: fade card out
  const card = btn.closest('.wl-card');
  card.style.opacity    = '0.4';
  card.style.pointerEvents = 'none';

  const payload = {
    branch_id:     branchId,
    wishlist_type: type,
  };
  if (type === 'service') payload.branch_service_override_id = id;
  if (type === 'package') payload.package_id = id;

  try {
    const res  = await fetch(`${API_BASE}wishlist/toggle`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success && data.action === 'removed') {
      // Remove from local array
      if (type === 'service') {
        allWishlistItems = allWishlistItems.filter(
          i => !(i.wishlist_type === 'service' && parseInt(i.branch_service_override_id) === id)
        );
      } else {
        allWishlistItems = allWishlistItems.filter(
          i => !(i.wishlist_type === 'package' && parseInt(i.package_id) === id)
        );
      }
      updateBadge(allWishlistItems.length);
      renderWishlist();
      if (typeof showToast === 'function') showToast('Removed from wishlist.', 'info');
    } else {
      // Revert
      card.style.opacity    = '1';
      card.style.pointerEvents = 'auto';
      if (typeof showToast === 'function') showToast('Could not remove. Try again.', 'error');
    }
  } catch (err) {
    card.style.opacity    = '1';
    card.style.pointerEvents = 'auto';
    console.error('[Wishlist Remove]', err);
    if (typeof showToast === 'function') showToast('Network error. Try again.', 'error');
  }
}

// ── Badge count on tab ────────────────────────────────────────
function updateBadge(count) {
  const badge = document.getElementById('wlTabBadge');
  if (!badge) return;
  if (count > 0) {
    badge.textContent = count;
    badge.style.display = 'inline-block';
  } else {
    badge.style.display = 'none';
  }
}

// ── HTML escape helper ────────────────────────────────────────
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

// ── Auto-load if wishlist tab is default open ─────────────────
// (Not needed now — loads on tab click. But preload badge count.)
(async function preloadBadge() {
  try {
    const res  = await fetch(`${API_BASE}wishlist/mine`);
    const data = await res.json();
    if (data.success) {
      allWishlistItems = data.data;
      updateBadge(data.data.length);
    }
  } catch (_) { /* silent */ }
})();