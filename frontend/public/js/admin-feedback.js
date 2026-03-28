const API_BASE_URL      = '../../backend/public/index.php?url=feedback';
const MODERATE_URL      = '../../backend/public/index.php?url=feedback/moderate';
const IMG_BASE_URL      = '../../backend/public/';

let feedbackData      = [];
let filteredFeedback  = [];
let pagination;
let currentStatusFilter = 'all';

// ─── Fetch ────────────────────────────────────────────────────────────
async function fetchFeedback(search = '', minRating = 0, status = 'all') {
    const url = `${API_BASE_URL}&search=${encodeURIComponent(search)}&min_rating=${encodeURIComponent(minRating)}&status=${encodeURIComponent(status)}`;

    const response = await fetch(url, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
    });

    const text = await response.text();
    if (!response.ok) throw new Error(`Server error: ${response.status}`);

    const result = JSON.parse(text);

    if (result.success) {
        feedbackData = result.data.map(f => ({
            id:           f.id,
            feedback_id:  f.feedback_id,
            customerName: f.customerName,
            rating:       f.rating,
            service:      f.service,
            feedback:     f.feedback,
            date:         f.date,
            time:         f.time,
            status:       f.status,
            is_flagged:   f.is_flagged,
            admin_note:   f.admin_note,
            photos:       f.photos || []
        }));
        return result;
    }
    throw new Error(result.message || 'Failed to fetch feedback');
}

// ─── Moderate ─────────────────────────────────────────────────────────
async function moderateFeedback(feedbackId, action, adminNote = null) {
    const response = await fetch(MODERATE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ feedback_id: feedbackId, action, admin_note: adminNote })
    });

    const result = await response.json();
    return result;
}

// ─── Action Handlers ──────────────────────────────────────────────────
async function approveFeedback(feedbackId) {
    if (!confirm('Approve this feedback? It will be visible to customers.')) return;
    const res = await moderateFeedback(feedbackId, 'approve');
    if (res.success) {
        showAdminToast('Feedback approved ✓', 'success');
        loadFeedback(getSearchTerm(), getMinRating(), currentStatusFilter);
    } else {
        showAdminToast(res.message || 'Failed', 'error');
    }
}

async function rejectFeedback(feedbackId) {
    const note = prompt('Reason for rejection (optional):');
    if (note === null) return; // Cancelled
    const res = await moderateFeedback(feedbackId, 'reject', note || null);
    if (res.success) {
        showAdminToast('Feedback rejected', 'warning');
        loadFeedback(getSearchTerm(), getMinRating(), currentStatusFilter);
    } else {
        showAdminToast(res.message || 'Failed', 'error');
    }
}

async function flagFeedback(feedbackId, currentFlagged) {
    const action = currentFlagged == 1 ? 'unflag' : 'flag';
    const res = await moderateFeedback(feedbackId, action);
    if (res.success) {
        showAdminToast(action === 'flag' ? 'Feedback flagged' : 'Flag removed', 'info');
        loadFeedback(getSearchTerm(), getMinRating(), currentStatusFilter);
    }
}

async function deleteFeedback(feedbackId) {
    if (!confirm('Permanently delete this feedback? This cannot be undone.')) return;
    const res = await moderateFeedback(feedbackId, 'delete');
    if (res.success) {
        showAdminToast('Feedback deleted', 'success');
        loadFeedback(getSearchTerm(), getMinRating(), currentStatusFilter);
    } else {
        showAdminToast(res.message || 'Failed', 'error');
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────
function getSearchTerm() { return document.getElementById('searchInput')?.value || ''; }
function getMinRating()  {
    const val = document.getElementById('filterRating')?.value || 'all';
    return val === 'all' ? 0 : parseInt(val);
}

function showAdminToast(message, type = 'success') {
    // Re-use existing toast if available, else alert
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        alert(message);
    }
}

// ─── DOM Ready ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    pagination = new Pagination({
        totalItems: 0,
        itemsPerPage: 6,
        currentPage: 1,
        onPageChange: function () {
            loadFeedback(getSearchTerm(), getMinRating(), currentStatusFilter);
        }
    });
    window.pagination = pagination;

    setTimeout(async () => {
        try { await loadFeedback('', 0, 'all'); }
        catch (e) { showError('Failed to load feedback'); }
    }, 0);

    // Search
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput?.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            pagination.goToPage(1);
            loadFeedback(searchInput.value, getMinRating(), currentStatusFilter);
        }, 300);
    });

    // Rating filter
    const filterRating = document.getElementById('filterRating');
    filterRating?.addEventListener('change', function () {
        pagination.goToPage(1);
        loadFeedback(getSearchTerm(), getMinRating(), currentStatusFilter);
    });

    // Status filter tabs
    document.querySelectorAll('.status-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentStatusFilter = this.dataset.status;
            pagination.goToPage(1);
            loadFeedback(getSearchTerm(), getMinRating(), currentStatusFilter);
        });
    });

    // Image lightbox close
    document.getElementById('feedbackLightbox')?.addEventListener('click', function (e) {
        if (e.target === this) closeLightbox();
    });
});

// ─── Load & Render ────────────────────────────────────────────────────
async function loadFeedback(searchTerm = '', minRating = 0, status = 'all') {
    const feedbackList = document.getElementById('feedbackList');
    if (!feedbackList) return;

    feedbackList.innerHTML = `
        <div class="loading-row">
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><span>Loading feedback...</span></div>
        </div>`;

    try {
        const result = await fetchFeedback(searchTerm, minRating, status);
        filteredFeedback = [...feedbackData];

        if (result.stats) updateRatingBreakdown(result.stats);

        // Update badge counts
        updateStatusBadges(result.stats);

        if (filteredFeedback.length === 0) {
            feedbackList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No feedback found</p>
                    <p class="subtitle">Try adjusting your search or filters</p>
                </div>`;
            pagination.updateTotalItems(0);
            return;
        }

        pagination.updateTotalItems(filteredFeedback.length);
        feedbackList.innerHTML = '';

        const range = pagination.getCurrentPageRange();
        const toDisplay = filteredFeedback.slice(range.start, range.end);

        toDisplay.forEach(feedback => {
            const card = document.createElement('div');
            card.className = `feedback-card ${feedback.is_flagged == 1 ? 'is-flagged' : ''} status-${feedback.status}`;

            const statusBadge = `<span class="feedback-status-badge badge-${feedback.status}">${capitalise(feedback.status)}</span>`;
            const flagBadge   = feedback.is_flagged == 1
                ? `<span class="feedback-flag-badge"><i class="fas fa-flag"></i> Flagged</span>`
                : '';

            const photosHtml = feedback.photos && feedback.photos.length > 0
                ? `<div class="feedback-photo-grid">
                    ${feedback.photos.map((p, i) =>
                        `<img src="${IMG_BASE_URL}${p.photo_path}" 
                              alt="Photo ${i+1}" 
                              class="feedback-thumb" 
                              onclick="openLightbox('${IMG_BASE_URL}${p.photo_path}')"
                              loading="lazy">`
                    ).join('')}
                   </div>`
                : '';

            const adminNoteHtml = feedback.admin_note
                ? `<div class="admin-note"><i class="fas fa-sticky-note"></i> <em>${escapeHtml(feedback.admin_note)}</em></div>`
                : '';

            card.innerHTML = `
                <div class="feedback-header">
                    <div class="feedback-user">
                        <div class="customer-name">${escapeHtml(feedback.customerName)}</div>
                        <div class="feedback-time">${formatDateTime(feedback.date, feedback.time)}</div>
                    </div>
                    <div class="feedback-rating">
                        <div class="feedback-stars">${generateStars(feedback.rating)}</div>
                        <div class="feedback-service"><i class="fas fa-spa"></i><span>${escapeHtml(feedback.service)}</span></div>
                    </div>
                </div>
                <div class="feedback-badges">${statusBadge}${flagBadge}</div>
                <div class="feedback-content">
                    <div class="feedback-text">${escapeHtml(feedback.feedback)}</div>
                    ${photosHtml}
                    ${adminNoteHtml}
                </div>
                <div class="feedback-actions">
                    ${feedback.status !== 'approved'
                        ? `<button class="btn-action btn-approve" onclick="approveFeedback(${feedback.feedback_id})">
                               <i class="fas fa-check"></i> Approve
                           </button>`
                        : ''}
                    ${feedback.status !== 'rejected'
                        ? `<button class="btn-action btn-reject" onclick="rejectFeedback(${feedback.feedback_id})">
                               <i class="fas fa-ban"></i> Reject
                           </button>`
                        : ''}
                    <button class="btn-action btn-flag ${feedback.is_flagged == 1 ? 'btn-flagged' : ''}" 
                            onclick="flagFeedback(${feedback.feedback_id}, ${feedback.is_flagged})">
                        <i class="fas fa-flag"></i> ${feedback.is_flagged == 1 ? 'Unflag' : 'Flag'}
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteFeedback(${feedback.feedback_id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            `;
            feedbackList.appendChild(card);
        });

    } catch (error) {
        showError('Failed to load feedback');
    }
}

function showError(msg) {
    const fl = document.getElementById('feedbackList');
    if (fl) fl.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <p>${msg}</p>
        </div>`;
}

// ─── Lightbox ─────────────────────────────────────────────────────────
function openLightbox(src) {
    let lb = document.getElementById('feedbackLightbox');
    if (!lb) {
        lb = document.createElement('div');
        lb.id = 'feedbackLightbox';
        lb.className = 'feedback-lightbox';
        lb.innerHTML = `<div class="lightbox-inner">
            <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
            <img id="lightboxImg" src="" alt="Feedback Photo">
        </div>`;
        document.body.appendChild(lb);
        lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
    }
    document.getElementById('lightboxImg').src = src;
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lb = document.getElementById('feedbackLightbox');
    if (lb) lb.style.display = 'none';
    document.body.style.overflow = '';
}

// ─── Status Badge Counter ─────────────────────────────────────────────
function updateStatusBadges(stats) {
    const pendingBadge = document.getElementById('pendingCount');
    const flaggedBadge = document.getElementById('flaggedCount');
    if (pendingBadge && stats) pendingBadge.textContent = stats.pendingCount || 0;
    if (flaggedBadge && stats) flaggedBadge.textContent = stats.flaggedCount || 0;
}

// ─── Shared Helpers ───────────────────────────────────────────────────
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function capitalise(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function generateStars(rating) {
    const full  = Math.floor(rating);
    const half  = rating % 1 !== 0;
    const empty = 5 - full - (half ? 1 : 0);
    return '<i class="fas fa-star"></i>'.repeat(full)
         + (half ? '<i class="fas fa-star-half-alt"></i>' : '')
         + '<i class="far fa-star"></i>'.repeat(empty);
}

function formatDateTime(dateString, timeString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
         + (timeString ? ` at ${timeString}` : '');
}

function updateRatingBreakdown(stats) {
    const overall     = parseFloat(stats.averageRating).toFixed(1);
    const total       = stats.totalReviews || 0;

    document.getElementById('overallRating').textContent  = overall;
    document.getElementById('totalReviews').textContent   = `${total} review${total !== 1 ? 's' : ''}`;
    document.getElementById('overallStars').innerHTML     = generateStars(parseFloat(overall));

    const counts = { 5: +stats.fiveStars||0, 4: +stats.fourStars||0, 3: +stats.threeStars||0, 2: +stats.twoStars||0, 1: +stats.oneStar||0 };
    const bd     = document.getElementById('ratingBreakdown');
    bd.innerHTML = '';

    for (let i = 5; i >= 1; i--) {
        const pct = total > 0 ? Math.round((counts[i] / total) * 100) : 0;
        const bar = document.createElement('div');
        bar.className = 'rating-bar-container';
        bar.innerHTML = `
            <div class="rating-number">${i} <i class="fas fa-star"></i></div>
            <div class="rating-bar"><div class="rating-progress" style="width:${pct}%"></div></div>
            <div class="rating-count">${counts[i]}</div>`;
        bd.appendChild(bar);
    }
}

async function loadReports() {
  const res  = await fetch(`${API_BASE}feedback/reports`);
  const data = await res.json();

  if (!data.success || !data.data.length) {
    // show empty state
    return;
  }

  const rows = data.data.map(r => `
    <tr class="${r.is_blocked == 1 ? 'row-blocked' : ''}">
      <td>
        <div class="report-reviewer">${r.customer_name}</div>
        <div class="report-service">${r.service ?? ''}</div>
      </td>
      <td class="report-comment">${r.comment}</td>
      <td><span class="report-count-badge">${r.report_count}</span></td>
      <td class="report-reasons">${r.reasons}</td>
      <td>
        ${r.is_blocked == 0
          ? `<button class="btn-mod btn-block" onclick="moderate(${r.feedback_id},'block')">
               <i class="fas fa-ban"></i> Block
             </button>`
          : `<button class="btn-mod btn-unblock" onclick="moderate(${r.feedback_id},'unblock')">
               <i class="fas fa-eye"></i> Unblock
             </button>`
        }
        <button class="btn-mod btn-dismiss" onclick="moderate(${r.feedback_id},'dismiss')">
          <i class="fas fa-check"></i> Dismiss
        </button>
        <button class="btn-mod btn-delete" onclick="moderate(${r.feedback_id},'delete')">
          <i class="fas fa-trash"></i> Delete
        </button>
      </td>
    </tr>`).join('');

  document.getElementById('reportsTableBody').innerHTML = rows;
}

async function moderate(feedbackId, action) {
  const labels = { block: 'Block this review?', delete: 'Permanently delete this review?', dismiss: 'Dismiss all reports for this review?', unblock: 'Unblock this review?' };
  if (!confirm(labels[action] ?? 'Confirm?')) return;

  const res  = await fetch(`${API_BASE}feedback/moderate`, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ feedback_id: feedbackId, action })
  });
  const data = await res.json();

  if (data.success) {
    Toast.success(data.message);
    loadReports();         // refresh the table
    loadFeedbackStats();   // refresh stat cards
  } else {
    Toast.error(data.message);
  }
}