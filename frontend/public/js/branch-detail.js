const API_BASE = '/HFABS/backend/public/index.php?url=';
const params   = new URLSearchParams(window.location.search);
const branchId = params.get('id');

if (!branchId) {
  window.location.href = '/HFABS/frontend/index.html#branches';
}

// ── Helpers ───────────────────────────────────────────
function formatTime(t) {
  if (!t) return 'N/A';
  const [h, m] = t.split(':');
  const hr = parseInt(h);
  return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
}

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

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000;
  if (diff < 60)      return 'just now';
  if (diff < 3600)    return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400)   return `${Math.floor(diff / 3600)}h ago`;
  if (diff < 2592000) return `${Math.floor(diff / 86400)}d ago`;
  return new Date(dateStr).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

// ── Load Branch Info ──────────────────────────────────
async function loadBranchInfo() {
  try {
    const res    = await fetch(`${API_BASE}branch/${branchId}`);
    const branch = await res.json();

    document.getElementById('inquiryBranchId').value = branch.branchid;

    document.title = `${branch.branchname} | Happy Face & Body Spa`;
    document.getElementById('branchHeroInfo').innerHTML = `
      <span class="branch-hero-eyebrow">
        <i class="fas fa-map-marker-alt"></i> ${branch.location}
      </span>
      <h1 class="branch-hero-title">${branch.branchname}</h1>
    `;

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
  const list   = cat === 'all'
      ? allServices
      : allServices.filter(s => String(s.category_id) === String(cat));
  const grid   = document.getElementById('branchServicesGrid');
  const unavailable = list.filter(s => !parseInt(s.is_available));
  const available   = list.filter(s =>  parseInt(s.is_available));
  const sorted = [...available, ...unavailable];

  if (!sorted.length) {
      grid.innerHTML = '<p style="color:#aaa;padding:1rem;">No services in this category.</p>';
      return;
  }

  grid.innerHTML = sorted.map(s => {
      const isUnavailable = !parseInt(s.is_available);
      const imageHtml = s.image_url
        ? `<img class="service-card-img" src="${s.image_url}" alt="${s.display_name || s.servicename}" loading="lazy"
              onerror="this.outerHTML='<div class=\\'service-card-img-placeholder\\'><i class=\\'fas fa-spa\\'></i></div>'">`
        : `<div class="service-card-img-placeholder"><i class="fas fa-spa"></i></div>`;

      return `
          <div class="branch-service-card ${isUnavailable ? 'unavailable' : ''}">
              ${imageHtml}
              <div class="branch-service-card-body">
                  <div class="service-card-top">
                      <h4 class="branch-service-name">${s.display_name}</h4>
                      ${isUnavailable ? '<span class="badge-unavailable">Unavailable</span>' : ''}
                  </div>
                  ${s.description ? `<p class="branch-service-desc">${s.description}</p>` : ''}
                  <div class="branch-service-meta">
                      <span class="branch-service-price">₱${parseFloat(s.price).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                      <span class="branch-service-duration"><i class="fas fa-clock"></i> ${s.duration} min</span>
                  </div>
              </div>
          </div>
      `;
  }).join('');
}

// ── Load Packages ─────────────────────────────────────
async function loadBranchPackages() {
  const grid = document.getElementById('branchPackagesGrid');

  try {
    const res    = await fetch(`${API_BASE}packages/getPublicPackages/${branchId}`);
    const result = await res.json();

    if (!result.success || !result.data.length) {
      grid.innerHTML = '<p class="no-data">No packages available at this branch.</p>';
      return;
    }

    grid.innerHTML = result.data.map(pkg => {
      const serviceTags = (pkg.included_services || '')
        .split(',')
        .map(s => s.trim())
        .filter(Boolean)
        .map(s => `<span class="package-service-tag">${s}</span>`)
        .join('');

      const unavailable = pkg.is_available == 0;

      return `
        <div class="package-card ${unavailable ? 'unavailable' : ''}">
          <div class="package-card-header">
            <h3 class="package-card-name">${pkg.package_name}</h3>
            <span class="package-card-price">₱${parseFloat(pkg.package_price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
          </div>
          ${pkg.description ? `<p class="package-card-desc">${pkg.description}</p>` : ''}
          <div class="package-card-meta">
            <span><i class="fas fa-hourglass-half"></i>${pkg.total_duration_minutes} mins</span>
            <span><i class="fas fa-layer-group"></i>${pkg.service_count} services</span>
          </div>
          <div class="package-services-label">Included Services</div>
          <div class="package-service-tags">${serviceTags || '<span class="package-service-tag">—</span>'}</div>
          ${unavailable ? '<span class="package-unavailable-badge">Unavailable</span>' : ''}
        </div>
      `;
    }).join('');

  } catch (e) {
    grid.innerHTML = '<p class="no-data">Failed to load packages.</p>';
    console.error('[Packages]', e);
  }
}

// ── Load Reviews ──────────────────────────────────────
let allReviews     = [];
let lightboxPhotos = [];

async function loadBranchReviews() {
  const grid    = document.getElementById('reviewsGrid');
  const summary = document.getElementById('reviewsSummary');
  const filter  = document.getElementById('reviewsFilter');

  grid.innerHTML = `
    <div class="reviews-loading">
      <i class="fas fa-spinner fa-spin"></i>
      <span>Loading reviews...</span>
    </div>`;

  try {
    const res  = await fetch(`${API_BASE}branch/${branchId}/reviews`);
    const data = await res.json();

    const totalReviews  = data?.summary?.total_reviews  ?? 0;
    const averageRating = parseFloat(data?.summary?.average_rating ?? 0);
    allReviews          = data?.reviews ?? [];

    const ratingCounts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    allReviews.forEach(r => {
      const star = Math.round(r.rating);
      if (ratingCounts[star] !== undefined) ratingCounts[star]++;
    });

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
          <div class="total-reviews-label">${totalReviews} review${totalReviews !== 1 ? 's' : ''}</div>
        </div>
        <div class="rating-breakdown-block">${breakdownHTML}</div>
      </div>`;

    filter.querySelectorAll('.star-filter').forEach(btn => {
      btn.addEventListener('click', () => {
        filter.querySelectorAll('.star-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderReviews(btn.dataset.stars);
      });
    });

    renderReviews('all');

  } catch (e) {
    grid.innerHTML = '<p class="no-data">Failed to load reviews.</p>';
    console.error('[Reviews] Error:', e);
  }
}

function renderReviews(starFilter) {
  const grid = document.getElementById('reviewsGrid');

  let list = allReviews;
  if (starFilter !== 'all') {
    const n = parseInt(starFilter);
    list = allReviews.filter(r => Math.round(r.rating) === n);
  }

  if (!list.length) {
    grid.innerHTML = `
      <div class="no-reviews">
        <i class="fas fa-comments"></i>
        <p>No reviews found${starFilter !== 'all' ? ` for ${starFilter} stars` : ''}.</p>
        <p class="no-reviews-sub">Try adjusting your filter or check back later!</p>
      </div>`;
    return;
  }

  lightboxPhotos = [];
  let photoOffset = 0;

  const cards = list.map(r => {
    const photos     = r.photos || [];
    const thisOffset = photoOffset;

    photos.forEach(p => lightboxPhotos.push(`/HFABS/backend/public/${p.photo_path}`));
    photoOffset += photos.length;

    const photosHtml = photos.length > 0
      ? `<div class="review-photos">
          ${photos.map((p, i) =>
            `<img
              src="/HFABS/backend/public/${p.photo_path}"
              alt="Review photo ${i + 1}"
              class="review-photo-thumb"
              loading="lazy"
              onclick="openReviewLightbox(${thisOffset + i})">`
          ).join('')}
         </div>`
      : '';

    return `
      <div class="review-card">
        <div class="review-card-top">
          <div class="reviewer-avatar">${r.customer_name?.charAt(0).toUpperCase() ?? '?'}</div>
          <div class="reviewer-info">
            <span class="reviewer-name">${r.customer_name ?? 'Anonymous'}</span>
            <span class="review-service"><i class="fas fa-spa"></i> ${r.service ?? ''}</span>
            <span class="review-time">${timeAgo(r.created_at)}</span>
          </div>
          <div class="review-stars">${generateStars(r.rating)}</div>
        </div>
        <p class="review-body">${r.comment || '<em>No comment provided.</em>'}</p>
        ${photosHtml}
        <div class="review-card-footer">
          <button class="report-review-btn" onclick="openReportModal(${r.feedback_id})">
            <i class="fas fa-flag"></i> Report
          </button>
        </div>
      </div>`;
  });

  grid.innerHTML = cards.join('');
  grid.scrollTop = 0;
  updateScrollFade();
}

// ── Review Lightbox ───────────────────────────────────
let lightboxIndex = 0;

function openReviewLightbox(index) {
  lightboxIndex = index;
  let lb = document.getElementById('reviewLightbox');
  if (!lb) {
    lb = document.createElement('div');
    lb.id        = 'reviewLightbox';
    lb.className = 'review-lightbox';
    lb.innerHTML = `
      <div class="review-lightbox-inner">
        <button class="lightbox-close-btn" onclick="closeReviewLightbox()">
          <i class="fas fa-times"></i>
        </button>
        <button class="lightbox-prev-btn" onclick="shiftReviewLightbox(-1)">
          <i class="fas fa-chevron-left"></i>
        </button>
        <img id="reviewLightboxImg" src="" alt="Review photo">
        <button class="lightbox-next-btn" onclick="shiftReviewLightbox(1)">
          <i class="fas fa-chevron-right"></i>
        </button>
        <div id="reviewLightboxCounter" class="lightbox-counter"></div>
      </div>`;
    document.body.appendChild(lb);
    lb.addEventListener('click', e => { if (e.target === lb) closeReviewLightbox(); });
  }
  setLightboxPhoto();
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function setLightboxPhoto() {
  document.getElementById('reviewLightboxImg').src = lightboxPhotos[lightboxIndex];
  document.getElementById('reviewLightboxCounter').textContent =
    `${lightboxIndex + 1} / ${lightboxPhotos.length}`;
  document.querySelector('.lightbox-prev-btn').style.visibility =
    lightboxIndex > 0 ? 'visible' : 'hidden';
  document.querySelector('.lightbox-next-btn').style.visibility =
    lightboxIndex < lightboxPhotos.length - 1 ? 'visible' : 'hidden';
}

function shiftReviewLightbox(dir) {
  const next = lightboxIndex + dir;
  if (next >= 0 && next < lightboxPhotos.length) {
    lightboxIndex = next;
    setLightboxPhoto();
  }
}

function closeReviewLightbox() {
  const lb = document.getElementById('reviewLightbox');
  if (lb) lb.style.display = 'none';
  document.body.style.overflow = '';
}

// ── Scroll Fade ───────────────────────────────────────
function updateScrollFade() {
  const grid   = document.getElementById('reviewsGrid');
  const fadeEl = document.getElementById('reviewsScrollFade');
  if (!grid || !fadeEl) return;
  const atBottom = grid.scrollTop + grid.clientHeight >= grid.scrollHeight - 4;
  fadeEl.style.opacity = atBottom ? '0' : '1';
}

document.addEventListener('DOMContentLoaded', () => {
  const grid = document.getElementById('reviewsGrid');
  if (grid) grid.addEventListener('scroll', updateScrollFade);
});

// ── Inquiry Form ──────────────────────────────────────
function setupInquiryForm() {
  const form = document.getElementById('inquiryForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const submitBtn      = form.querySelector('.btn-send-inquiry');
    const originalBtnText = submitBtn.innerHTML;

    submitBtn.disabled  = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    const formData = {
      branch_id: document.getElementById('inquiryBranchId').value,
      name:      document.getElementById('inquiryName').value.trim(),
      email:     document.getElementById('inquiryEmail').value.trim(),
      subject:   document.getElementById('inquirySubject').value.trim(),
      message:   document.getElementById('inquiryMessage').value.trim()
    };

    try {
      const res  = await fetch(`${API_BASE}branch/sendInquiry`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify(formData)
      });
      const data = await res.json();

      if (data.success) {
        Toast.success(data.message || 'Your inquiry has been sent successfully!');
        form.reset();
      } else {
        Toast.error(data.message || 'Failed to send inquiry. Please try again.');
      }
    } catch (err) {
      console.error('Inquiry error:', err);
      Toast.error('Could not connect to server. Please try again later.');
    } finally {
      submitBtn.disabled  = false;
      submitBtn.innerHTML = originalBtnText;
    }
  });
}

// ── Report Modal ──────────────────────────────────────
function openReportModal(feedbackId) {
  let modal = document.getElementById('reportModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id        = 'reportModal';
    modal.className = 'report-modal-overlay';
    modal.innerHTML = `
      <div class="report-modal">
        <h3><i class="fas fa-flag"></i> Report Review</h3>
        <p class="report-modal-sub">Tell us why this review is inappropriate.</p>
        <select id="reportReason" class="report-reason-select">
          <option value="">— Select a reason —</option>
          <option value="Spam or fake review">Spam or fake review</option>
          <option value="Offensive or inappropriate content">Offensive or inappropriate content</option>
          <option value="Irrelevant to the service">Irrelevant to the service</option>
          <option value="Personal attack or harassment">Personal attack or harassment</option>
          <option value="Other">Other</option>
        </select>
        <div class="report-modal-actions">
          <button class="btn-report-cancel" onclick="closeReportModal()">Cancel</button>
          <button class="btn-report-submit" id="submitReportBtn">Submit Report</button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) closeReportModal(); });
  }
  document.getElementById('reportReason').value = '';
  document.getElementById('submitReportBtn').onclick = () => submitReport(feedbackId);
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeReportModal() {
  const modal = document.getElementById('reportModal');
  if (modal) modal.style.display = 'none';
  document.body.style.overflow = '';
}

async function submitReport(feedbackId) {
  const reason = document.getElementById('reportReason').value;
  if (!reason) { Toast.error('Please select a reason.'); return; }

  const btn     = document.getElementById('submitReportBtn');
  btn.disabled  = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

  try {
    const res  = await fetch(`${API_BASE}feedback/report`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ feedback_id: feedbackId, reason })
    });
    const data = await res.json();
    if (data.success) {
      Toast.success(data.message);
      closeReportModal();
    } else {
      Toast.error(data.message);
    }
  } catch {
    Toast.error('Could not submit report. Try again later.');
  } finally {
    btn.disabled  = false;
    btn.innerHTML = 'Submit Report';
  }
}

// ── Init ──────────────────────────────────────────────
loadBranchInfo();
loadBranchServices();
loadBranchPackages();
loadBranchReviews();
setupInquiryForm();