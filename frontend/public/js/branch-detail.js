const API_BASE = '/HFABS/backend/public/index.php?url=';
const params   = new URLSearchParams(window.location.search);
const branchId = params.get('id');

if (!branchId) {
  window.location.href = '/HFABS/frontend/index.html#branches';
}

// ── Helpers ──────────────────────────────────────────
function formatTime(t) {
  if (!t) return 'N/A';
  const [h, m] = t.split(':');
  const hr = parseInt(h);
  return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
}

function generateStars(rating) {
  const r = Math.round(rating);
  return Array.from({ length: 5 }, (_, i) =>
    `<span class="star ${i < r ? '' : 'empty'}">★</span>`
  ).join('');
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000;
  if (diff < 60)   return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  if (diff < 2592000) return `${Math.floor(diff / 86400)}d ago`;
  return new Date(dateStr).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

// ── Load Branch Info ──────────────────────────────────
async function loadBranchInfo() {
  try {
    const res    = await fetch(`${API_BASE}branch/${branchId}`);
    const branch = await res.json();

    // Hero
    document.title = `${branch.branchname} | Happy Face & Body Spa`;
    document.getElementById('branchHeroInfo').innerHTML = `
      <span class="branch-hero-eyebrow">
        <i class="fas fa-map-marker-alt"></i> ${branch.location}
      </span>
      <h1 class="branch-hero-title">${branch.branchname}</h1>
    `;

    // Info cards
    document.getElementById('branchInfoGrid').innerHTML = `
      <div class="info-card">
        <div class="info-card-icon"><i class="fas fa-map-pin"></i></div>
        <div>
          <span class="info-card-label">Location</span>
          <span class="info-card-value">${branch.location}</span>
        </div>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="fas fa-phone"></i></div>
        <div>
          <span class="info-card-label">Contact</span>
          <span class="info-card-value">${branch.contact_number}</span>
        </div>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="fas fa-clock"></i></div>
        <div>
          <span class="info-card-label">Opening Time</span>
          <span class="info-card-value">${formatTime(branch.opening_time)}</span>
        </div>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="fas fa-moon"></i></div>
        <div>
          <span class="info-card-label">Closing Time</span>
          <span class="info-card-value">${formatTime(branch.closing_time)}</span>
        </div>
      </div>
    `;
  } catch (e) {
    console.error('Branch info error:', e);
  }
}

// ── Load Services ─────────────────────────────────────
let allServices = [];

async function loadBranchServices() {
  const grid   = document.getElementById('branchServicesGrid');
  const filter = document.getElementById('servicesFilter');

  try {
    const res = await fetch(`${API_BASE}branch/${branchId}/services`);
    allServices = await res.json();

    if (!allServices.length) {
      grid.innerHTML = '<p class="no-data">No services listed for this branch.</p>';
      return;
    }

    // Build category tabs
    const categories = [...new Set(allServices.map(s => s.category))];
    filter.innerHTML = `
      <button class="filter-tab active" data-cat="all">All</button>
      ${categories.map(c => `<button class="filter-tab" data-cat="${c}">${c}</button>`).join('')}
    `;

    filter.querySelectorAll('.filter-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        filter.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderServices(btn.dataset.cat);
      });
    });

    renderServices('all');

  } catch (e) {
    grid.innerHTML = '<p class="no-data">Failed to load services.</p>';
    console.error(e);
  }
}

function renderServices(cat) {
  const grid = document.getElementById('branchServicesGrid');
  const list = cat === 'all' ? allServices : allServices.filter(s => s.category === cat);

  grid.innerHTML = list.map(s => `
    <div class="service-item ${s.isavailable == 0 ? 'unavailable' : ''}">
      <div class="service-item-header">
        <span class="service-item-name">${s.servicename}</span>
        <span class="service-item-price">₱${parseFloat(s.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
      </div>
      <p class="service-item-desc">${s.description || 'No description available.'}</p>
      <div class="service-item-footer">
        <span class="service-item-cat"><i class="fas fa-tag"></i> ${s.category}</span>
        <span class="service-item-duration"><i class="fas fa-hourglass-half"></i> ${s.duration}</span>
        ${s.isavailable == 0 ? '<span class="service-unavailable-badge">Unavailable</span>' : ''}
      </div>
    </div>
  `).join('');
}

// ── Load Reviews ──────────────────────────────────────
async function loadBranchReviews() {
  const grid    = document.getElementById('reviewsGrid');
  const summary = document.getElementById('reviewsSummary');

  try {
    const res  = await fetch(`${API_BASE}branch/${branchId}/reviews`);
    const data = await res.json();

    const totalReviews  = data?.summary?.total_reviews  ?? 0;
    const averageRating = parseFloat(data?.summary?.average_rating ?? 0);
    const reviews       = data?.reviews ?? [];

    // ── Rating Overview (matches admin-feedback layout) ──
    // Calculate per-star breakdown from reviews array
    const ratingCounts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    reviews.forEach(r => {
      const star = Math.round(r.rating);
      if (ratingCounts[star] !== undefined) ratingCounts[star]++;
    });

    // Build breakdown bars HTML
    let breakdownHTML = '';
    for (let i = 5; i >= 1; i--) {
      const pct = totalReviews > 0
        ? Math.round((ratingCounts[i] / totalReviews) * 100)
        : 0;
      breakdownHTML += `
        <div class="rating-bar-container">
          <div class="rating-number">${i} <i class="fas fa-star"></i></div>
          <div class="rating-bar">
            <div class="rating-progress" style="width: ${pct}%"></div>
          </div>
          <div class="rating-count-label">${ratingCounts[i]}</div>
        </div>`;
    }

    summary.innerHTML = `
      <div class="rating-overview-widget">
        <div class="overall-rating-block">
          <div class="rating-score-big">${averageRating > 0 ? averageRating.toFixed(1) : '—'}</div>
          <div class="rating-stars-row">${generateStars(averageRating)}</div>
          <div class="total-reviews-label">
            ${totalReviews} review${totalReviews !== 1 ? 's' : ''}
          </div>
        </div>
        <div class="rating-breakdown-block">
          ${breakdownHTML}
        </div>
      </div>`;

    // ── Individual Review Cards ──
    if (!reviews.length) {
      grid.innerHTML = `
        <div class="no-reviews">
          <i class="fas fa-comments"></i>
          <p>No reviews yet for this branch.</p>
          <p class="no-reviews-sub">Be the first to leave a review after your visit!</p>
        </div>`;
      return;
    }

    grid.innerHTML = reviews.map(r => `
      <div class="review-card">
        <div class="review-card-top">
          <div class="reviewer-avatar">${r.customer_name?.charAt(0).toUpperCase() ?? '?'}</div>
          <div class="reviewer-info">
            <span class="reviewer-name">${r.customer_name ?? 'Anonymous'}</span>
            <span class="review-time">${timeAgo(r.created_at)}</span>
          </div>
          <div class="review-stars">${generateStars(r.rating)}</div>
        </div>
        <p class="review-body">${r.comment || '<em>No comment provided.</em>'}</p>
      </div>
    `).join('');

  } catch (e) {
    grid.innerHTML = '<p class="no-data">Failed to load reviews.</p>';
    console.error('[Reviews] Error:', e);
  }
}

// Replaces the old renderStars() — matches admin-feedback generateStars()
function generateStars(rating) {
  const full  = Math.floor(rating);
  const half  = rating % 1 !== 0;
  const empty = 5 - full - (half ? 1 : 0);
  return (
    '<i class="fas fa-star"></i>'.repeat(full) +
    (half ? '<i class="fas fa-star-half-alt"></i>' : '') +
    '<i class="far fa-star"></i>'.repeat(empty)
  );
}


// ── Init ──────────────────────────────────────────────
loadBranchInfo();
loadBranchServices();
loadBranchReviews();
