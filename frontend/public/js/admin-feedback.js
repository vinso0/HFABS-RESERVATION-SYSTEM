/* ════════════════════════════════════════════════════════════
   admin-feedback.js  ·  No approval workflow — block/unblock only
   Tabs: All · Active · Blocked · Reported
════════════════════════════════════════════════════════════ */

const API_BASE_URL = '../../backend/public/index.php?url=feedback';
const MODERATE_URL = '../../backend/public/index.php?url=feedback/moderate';
const REPORTS_URL  = '../../backend/public/index.php?url=feedback/reports';
const IMG_BASE_URL = '../../backend/public/';

let allFeedback         = [];
let currentFilter       = 'all';   // 'all' | 'active' | 'blocked' | 'reported'
let pagination;

// ─── Toast ───────────────────────────────────────────────────────────
function adminToast(msg, type = 'success') {
    typeof showToast === 'function' ? showToast(msg, type) : console.warn('[Toast]', type, msg);
}

// ═════════════════════════════════════════════════════════════════════
//  A.  FEEDBACK LIST (All / Active / Blocked)
// ═════════════════════════════════════════════════════════════════════

async function fetchFeedback(search = '', minRating = 0, filter = 'all') {
    const url = `${API_BASE_URL}&search=${encodeURIComponent(search)}`
              + `&min_rating=${encodeURIComponent(minRating)}`
              + `&status=${encodeURIComponent(filter)}`;

    const res = await fetch(url, { credentials: 'include' });
    if (!res.ok) throw new Error(`Server error ${res.status}`);
    const result = await res.json();
    if (!result.success) throw new Error(result.message || 'Failed to fetch');

    allFeedback = result.data.map(f => ({
        feedback_id:  f.feedback_id,
        customerName: f.customerName,
        rating:       parseFloat(f.rating),
        service:      f.service,
        feedback:     f.feedback,
        date:         f.date,
        time:         f.time,
        is_blocked:   f.is_blocked,
        report_count: f.report_count || 0,
        photos:       f.photos || []
    }));

    return result;
}

async function loadFeedback(search = '', minRating = 0, filter = 'all') {
    showSection('feedback');
    const list = document.getElementById('feedbackList');
    if (!list) return;

    list.innerHTML = loadingHtml();

    try {
        const result = await fetchFeedback(search, minRating, filter);

        if (result.stats) {
            updateRatingOverview(result.stats);
            updateTabBadges(result.stats);
        }

        if (!allFeedback.length) {
            list.innerHTML = emptyHtml('No feedback found', 'Try adjusting your search or filters');
            pagination.updateTotalItems(0);
            return;
        }

        pagination.updateTotalItems(allFeedback.length);
        list.innerHTML = '';

        const { start, end } = pagination.getCurrentPageRange();
        allFeedback.slice(start, end).forEach(f => {
            list.appendChild(buildCard(f));
        });

    } catch (e) {
        list.innerHTML = emptyHtml('Failed to load feedback', e.message, 'fa-exclamation-circle');
    }
}

function buildCard(f) {
    const card = document.createElement('div');
    card.className = `feedback-card${f.is_blocked == 1 ? ' is-blocked' : ''}`;

    const blockedBadge = f.is_blocked == 1
        ? `<span class="fb-badge badge-blocked"><i class="fas fa-ban"></i> Blocked</span>`
        : `<span class="fb-badge badge-active"><i class="fas fa-check-circle"></i> Active</span>`;

    const reportBadge = f.report_count > 0
        ? `<span class="fb-badge badge-reported"><i class="fas fa-flag"></i> ${f.report_count} report${f.report_count > 1 ? 's' : ''}</span>`
        : '';

    const photosHtml = f.photos.length
        ? `<div class="feedback-photo-grid">
            ${f.photos.map((p, i) =>
                `<img src="${IMG_BASE_URL}${p.photo_path}" alt="Photo ${i+1}"
                      class="feedback-thumb"
                      onclick="openLightbox('${IMG_BASE_URL}${p.photo_path}')"
                      loading="lazy">`
            ).join('')}
           </div>`
        : '';

    card.innerHTML = `
        <div class="feedback-header">
            <div class="feedback-user">
                <div class="customer-name">${esc(f.customerName)}</div>
                <div class="feedback-time">${fmtDate(f.date, f.time)}</div>
            </div>
            <div class="feedback-rating">
                <div class="feedback-stars">${stars(f.rating)}</div>
                <div class="feedback-service"><i class="fas fa-spa"></i><span>${esc(f.service)}</span></div>
            </div>
        </div>
        <div class="feedback-badges">${blockedBadge}${reportBadge}</div>
        <div class="feedback-content">
            <div class="feedback-text">${esc(f.feedback)}</div>
            ${photosHtml}
        </div>
        <div class="feedback-actions">
            ${f.is_blocked == 1
                ? `<button class="btn-action btn-unblock" onclick="doModerate(${f.feedback_id},'unblock')">
                       <i class="fas fa-eye"></i> Unblock
                   </button>`
                : `<button class="btn-action btn-block" onclick="doModerate(${f.feedback_id},'block')">
                       <i class="fas fa-ban"></i> Block
                   </button>`
            }
            <button class="btn-action btn-delete" onclick="doModerate(${f.feedback_id},'delete')">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>`;

    return card;
}

// ═════════════════════════════════════════════════════════════════════
//  B.  REPORTED REVIEWS TABLE
// ═════════════════════════════════════════════════════════════════════

async function loadReports() {
    showSection('reports');
    const tbody = document.getElementById('reportsTableBody');
    const empty = document.getElementById('reportsEmptyState');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr><td colspan="5">${loadingHtml()}</td></tr>`;

    try {
        const res  = await fetch(REPORTS_URL, { credentials: 'include' });
        const data = await res.json();

        if (!data.success || !data.data.length) {
            tbody.innerHTML = '';
            if (empty) empty.style.display = 'flex';
            return;
        }
        if (empty) empty.style.display = 'none';

        tbody.innerHTML = data.data.map(r => `
            <tr class="report-row${r.is_blocked == 1 ? ' row-blocked' : ''}">
                <td>
                    <div class="report-name">${esc(r.customer_name)}</div>
                    <div class="report-service">${esc(r.service ?? '')}</div>
                </td>
                <td class="report-comment-cell">${esc(r.comment)}</td>
                <td class="report-count-cell">
                    <span class="report-count-badge">${r.report_count}</span>
                </td>
                <td class="report-reasons-cell">${esc(r.reasons ?? '')}</td>
                <td class="report-actions-cell">
                    ${r.is_blocked == 0
                        ? `<button class="btn-mod btn-mod--block"   onclick="doModerate(${r.feedback_id},'block')">
                               <i class="fas fa-ban"></i> Block
                           </button>`
                        : `<button class="btn-mod btn-mod--unblock" onclick="doModerate(${r.feedback_id},'unblock')">
                               <i class="fas fa-eye"></i> Unblock
                           </button>`
                    }
                    <button class="btn-mod btn-mod--dismiss" onclick="doModerate(${r.feedback_id},'dismiss')">
                        <i class="fas fa-check"></i> Dismiss
                    </button>
                    <button class="btn-mod btn-mod--delete"  onclick="doModerate(${r.feedback_id},'delete')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>`).join('');

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5">${emptyHtml('Failed to load reports', e.message, 'fa-exclamation-circle')}</td></tr>`;
    }
}

// ═════════════════════════════════════════════════════════════════════
//  C.  MODERATE (shared for both sections)
// ═════════════════════════════════════════════════════════════════════

const MODERATE_CFG = {
    block:   { icon: 'ban',       cls: 'confirm-icon--red',   title: 'Block Review',    msg: 'This review will be hidden from all customers.',           label: 'Block',   btnCls: 'btn-confirm--red'   },
    unblock: { icon: 'eye',       cls: 'confirm-icon--green', title: 'Unblock Review',  msg: 'This review will become visible to customers again.',      label: 'Unblock', btnCls: 'btn-confirm--green' },
    dismiss: { icon: 'check',     cls: 'confirm-icon--green', title: 'Dismiss Reports', msg: 'All reports will be cleared. The review stays as-is.',     label: 'Dismiss', btnCls: 'btn-confirm--green' },
    delete:  { icon: 'trash-alt', cls: 'confirm-icon--red',   title: 'Delete Review',   msg: 'This permanently deletes the review and all its reports.', label: 'Delete',  btnCls: 'btn-confirm--red'   }
};

function doModerate(feedbackId, action) {
    const cfg = MODERATE_CFG[action];
    if (!cfg) return;

    showConfirm({
        ...cfg,
        onConfirm: async () => {
            try {
                const res  = await fetch(MODERATE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ feedback_id: feedbackId, action })
                });
                const data = await res.json();
                if (data.success) {
                    adminToast(data.message, 'success');
                    refreshCurrentView();
                } else {
                    adminToast(data.message || 'Action failed', 'error');
                }
            } catch {
                adminToast('Could not connect to server.', 'error');
            }
        }
    });
}

function refreshCurrentView() {
    if (currentFilter === 'reported') {
        loadReports();
    } else {
        loadFeedback(getSearch(), getMinRating(), currentFilter);
    }
}

// ═════════════════════════════════════════════════════════════════════
//  D.  CONFIRM MODAL
// ═════════════════════════════════════════════════════════════════════

function showConfirm({ icon, cls, title, msg, label, btnCls, onConfirm }) {
    let overlay = document.getElementById('adminConfirmModal');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id        = 'adminConfirmModal';
        overlay.className = 'admin-confirm-overlay';
        overlay.innerHTML = `
            <div class="admin-confirm-box">
                <div class="admin-confirm-icon" id="cfmIcon"><i class="fas"></i></div>
                <h3  class="admin-confirm-title"   id="cfmTitle"></h3>
                <p   class="admin-confirm-msg"     id="cfmMsg"></p>
                <div class="admin-confirm-actions">
                    <button class="admin-confirm-btn admin-confirm-btn--cancel" id="cfmCancel">Cancel</button>
                    <button class="admin-confirm-btn" id="cfmOk"></button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', e => { if (e.target === overlay) closeConfirm(); });
        document.getElementById('cfmCancel').addEventListener('click', closeConfirm);
    }

    document.getElementById('cfmIcon').className    = `admin-confirm-icon ${cls}`;
    document.getElementById('cfmIcon').querySelector('i').className = `fas fa-${icon}`;
    document.getElementById('cfmTitle').textContent = title;
    document.getElementById('cfmMsg').textContent   = msg;

    const ok = document.getElementById('cfmOk');
    ok.textContent = label;
    ok.className   = `admin-confirm-btn ${btnCls}`;
    ok.onclick     = () => { closeConfirm(); onConfirm(); };

    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeConfirm() {
    const m = document.getElementById('adminConfirmModal');
    if (m) m.style.display = 'none';
    document.body.style.overflow = '';
}

// ═════════════════════════════════════════════════════════════════════
//  E.  LIGHTBOX
// ═════════════════════════════════════════════════════════════════════

function openLightbox(src) {
    let lb = document.getElementById('feedbackLightbox');
    if (!lb) {
        lb = document.createElement('div');
        lb.id        = 'feedbackLightbox';
        lb.className = 'feedback-lightbox';
        lb.innerHTML = `<div class="lightbox-inner">
            <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
            <img id="lightboxImg" src="" alt="Feedback Photo">
        </div>`;
        document.body.appendChild(lb);
        lb.addEventListener('click', e => { if (e.target === lb) closeLightbox(); });
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

// ═════════════════════════════════════════════════════════════════════
//  F.  SECTION SWITCHER
// ═════════════════════════════════════════════════════════════════════

function showSection(name) {
    const isFeedback = name === 'feedback';
    document.getElementById('feedbackCardsSection').style.display = isFeedback ? '' : 'none';
    document.getElementById('reportsSection').style.display       = isFeedback ? 'none' : '';
    document.getElementById('filterSection').style.display        = isFeedback ? '' : 'none';
}

// ═════════════════════════════════════════════════════════════════════
//  G.  STATS / BADGES
// ═════════════════════════════════════════════════════════════════════

function updateTabBadges(stats) {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val ?? 0; };
    set('blockedCount',  stats.blockedCount);
    set('reportedCount', stats.pendingReports);
}

function updateRatingOverview(stats) {
    const avg   = parseFloat(stats.averageRating || 0).toFixed(1);
    const total = parseInt(stats.totalReviews || 0);

    const el = id => document.getElementById(id);
    if (el('overallRating')) el('overallRating').textContent = avg;
    if (el('totalReviews'))  el('totalReviews').textContent  = `${total} review${total !== 1 ? 's' : ''}`;
    if (el('overallStars'))  el('overallStars').innerHTML    = stars(parseFloat(avg));

    const bd = el('ratingBreakdown');
    if (!bd) return;

    const counts = { 5: +stats.fiveStars||0, 4: +stats.fourStars||0, 3: +stats.threeStars||0, 2: +stats.twoStars||0, 1: +stats.oneStar||0 };
    bd.innerHTML = '';
    for (let i = 5; i >= 1; i--) {
        const pct = total > 0 ? Math.round((counts[i] / total) * 100) : 0;
        const row = document.createElement('div');
        row.className = 'rating-bar-container';
        row.innerHTML = `
            <div class="rating-number">${i} <i class="fas fa-star"></i></div>
            <div class="rating-bar"><div class="rating-progress" style="width:${pct}%"></div></div>
            <div class="rating-count">${counts[i]}</div>`;
        bd.appendChild(row);
    }
}

// ═════════════════════════════════════════════════════════════════════
//  H.  HELPERS
// ═════════════════════════════════════════════════════════════════════

function getSearch()    { return document.getElementById('searchInput')?.value.trim() || ''; }
function getMinRating() {
    const v = document.getElementById('filterRating')?.value || 'all';
    return v === 'all' ? 0 : parseInt(v);
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function stars(rating) {
    const full  = Math.floor(rating);
    const half  = rating % 1 !== 0;
    const empty = 5 - full - (half ? 1 : 0);
    return '<i class="fas fa-star"></i>'.repeat(full)
         + (half ? '<i class="fas fa-star-half-alt"></i>' : '')
         + '<i class="far fa-star"></i>'.repeat(empty);
}

function fmtDate(d, t) {
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
         + (t ? ` at ${t}` : '');
}

function loadingHtml() {
    return `<div class="loading-row">
        <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><span>Loading...</span></div>
    </div>`;
}

function emptyHtml(title, sub = '', icon = 'fa-comments') {
    return `<div class="empty-state">
        <i class="fas ${icon}"></i>
        <p>${title}</p>${sub ? `<p class="subtitle">${sub}</p>` : ''}
    </div>`;
}

// ═════════════════════════════════════════════════════════════════════
//  I.  DOM READY
// ═════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    pagination = new Pagination({
        totalItems:   0,
        itemsPerPage: 6,
        currentPage:  1,
        onPageChange: () => loadFeedback(getSearch(), getMinRating(), currentFilter)
    });
    window.pagination = pagination;

    // Initial load
    loadFeedback('', 0, 'all').catch(() => {});

    // Search
    let searchTimer;
    document.getElementById('searchInput')?.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            pagination.goToPage(1);
            loadFeedback(this.value.trim(), getMinRating(), currentFilter);
        }, 300);
    });

    // Rating filter
    document.getElementById('filterRating')?.addEventListener('change', () => {
        pagination.goToPage(1);
        loadFeedback(getSearch(), getMinRating(), currentFilter);
    });

    // Status tabs
    document.querySelectorAll('.status-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.status;

            if (currentFilter === 'reported') {
                loadReports();
            } else {
                pagination.goToPage(1);
                loadFeedback(getSearch(), getMinRating(), currentFilter);
            }
        });
    });
});