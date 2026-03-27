const API = '/HFABS/backend/public/index.php';

// ── Live clock ──
function updateClock() {
    const now  = new Date();
    let h      = now.getHours();
    const m    = String(now.getMinutes()).padStart(2, '0');
    const s    = String(now.getSeconds()).padStart(2, '0');
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    document.getElementById('liveTime').textContent = `${h}:${m}:${s} ${ampm}`;
}
updateClock();
setInterval(updateClock, 1000);

// ── Helpers ──
function formatTime(t) {
    if (!t) return '—';
    const [hh, mm] = t.split(':');
    const hr = parseInt(hh);
    return `${hr % 12 || 12}:${mm} ${hr >= 12 ? 'PM' : 'AM'}`;
}

function capitalize(s) {
    if (!s) return '';
    // Handle hyphenated statuses like "no-show" → "No-Show"
    return s.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('-');
}

function formatCurrency(amount) {
    return '₱' + parseFloat(amount || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDatetime(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
         + ' ' + d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
}

function makeStatCard(bg, icon, label, value) {
    return `<div class="stat-card">
        <div class="stat-icon" style="background:${bg}">
            <i class="fas ${icon}"></i>
        </div>
        <div class="stat-content">
            <p class="stat-label">${label}</p>
            <p class="stat-number">${value}</p>
        </div>
    </div>`;
}

// ── Stat cards + status breakdown ──
// FIX: Uses session branch_id via a dedicated stats endpoint to get ALL records,
//      not paginated. Falls back to getAllReservations with large itemsPerPage.
async function loadDashboardStats() {
    try {
        // Fetch all reservations unpaginated (itemsPerPage=9999)
        const res  = await fetch(`${API}?url=reservation/getAllReservations&status=all&page=1&itemsPerPage=9999`, {
            credentials: 'include'
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message ?? 'failed');

        const all = data.data ?? [];

        // FIX: DB stores 'no-show' with a hyphen — match exactly
        const counts = {
            pending:     all.filter(r => r.status === 'pending').length,
            confirmed:   all.filter(r => r.status === 'confirmed').length,
            completed:   all.filter(r => r.status === 'completed').length,
            rescheduled: all.filter(r => r.status === 'rescheduled').length,
            cancelled:   all.filter(r => r.status === 'cancelled').length,
            noshow:      all.filter(r => r.status === 'no-show').length,
        };

        document.getElementById('statsGrid').innerHTML =
            makeStatCard('linear-gradient(135deg,#f59e0b,#fbbf24)', 'fa-hourglass-half', 'Pending',     counts.pending)     +
            makeStatCard('linear-gradient(135deg,#10b981,#34d399)', 'fa-check-circle',   'Confirmed',   counts.confirmed)   +
            makeStatCard('linear-gradient(135deg,#6366f1,#818cf8)', 'fa-calendar-check', 'Completed',   counts.completed)   +
            makeStatCard('linear-gradient(135deg,#0ea5e9,#38bdf8)', 'fa-calendar-minus', 'Rescheduled', counts.rescheduled) +
            makeStatCard('linear-gradient(135deg,#ef4444,#f87171)', 'fa-times-circle',   'Cancelled',   counts.cancelled)   +
            makeStatCard('linear-gradient(135deg,#64748b,#94a3b8)', 'fa-user-slash',     'No-Show',     counts.noshow);

        renderStatusBreakdown(all, counts);

    } catch (e) {
        document.getElementById('statsGrid').innerHTML =
            `<p style="color:#e11;font-size:13px;padding:8px 0;grid-column:1/-1">Could not load stats: ${e.message}</p>`;
    }
}

function renderStatusBreakdown(all, counts) {
    const container = document.getElementById('statusBreakdown');
    if (!all.length) {
        container.innerHTML = '<div class="dash-empty"><i class="fas fa-chart-pie"></i><p>No data yet.</p></div>';
        return;
    }

    const statuses = [
        { key: 'pending',     label: 'Pending',     color: '#f59e0b', count: counts.pending },
        { key: 'confirmed',   label: 'Confirmed',   color: '#10b981', count: counts.confirmed },
        { key: 'completed',   label: 'Completed',   color: '#6366f1', count: counts.completed },
        { key: 'cancelled',   label: 'Cancelled',   color: '#ef4444', count: counts.cancelled },
        { key: 'rescheduled', label: 'Rescheduled', color: '#0ea5e9', count: counts.rescheduled },
        { key: 'no-show',     label: 'No-Show',     color: '#64748b', count: counts.noshow },
    ];

    const total = all.length;
    let html = '<div class="breakdown-list">';

    statuses.forEach(s => {
        const pct = total > 0 ? Math.round((s.count / total) * 100) : 0;
        html += `<div class="breakdown-item">
            <div class="breakdown-meta">
                <span class="breakdown-label">${s.label}</span>
                <span class="breakdown-count">${s.count} <span class="breakdown-pct">(${pct}%)</span></span>
            </div>
            <div class="breakdown-bar-track">
                <div class="breakdown-bar-fill" style="width:${pct}%;background:${s.color}"></div>
            </div>
        </div>`;
    });

    html += `<div class="breakdown-total">Total: <strong>${total}</strong> reservation${total !== 1 ? 's' : ''}</div>`;
    html += '</div>';
    container.innerHTML = html;
}

// ── Today's Reservations table ──
async function loadTodayReservations() {
    const container = document.getElementById('todayTable');
    try {
        const res  = await fetch(`${API}?url=reservation/getTodaysReservations`, {
            credentials: 'include'
        });
        const data = await res.json();

        if (!data.success || !data.data?.length) {
            container.innerHTML = `<div class="dash-empty">
                <i class="fas fa-calendar-times"></i>
                <p>No reservations scheduled for today.</p>
            </div>`;
            return;
        }

        const rows = data.data.map(r => {
            const time     = r.schedule?.start_time ? formatTime(r.schedule.start_time) : '—';
            const services = (r.services ?? []).map(s => s.service_name).join(', ') || '—';
            return `<tr>
                <td><strong>#${r.reservation_id}</strong></td>
                <td>${r.customer_name ?? '—'}</td>
                <td>${time}</td>
                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${services}">${services}</td>
                <td><span class="status-badge status-${r.status}">${capitalize(r.status)}</span></td>
            </tr>`;
        }).join('');

        container.innerHTML = `<table class="dash-table">
            <thead><tr><th>ID</th><th>Customer</th><th>Time</th><th>Services</th><th>Status</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`;

    } catch (e) {
        container.innerHTML = `<p style="color:#e11;font-size:13px;padding:8px 0">Error: ${e.message}</p>`;
    }
}

// ── Recent Transactions ──
async function loadRecentTransactions() {
    const container = document.getElementById('transactionsTable');
    try {
        const res = await fetch(`${API}?url=transaction/getByBranch`, {
            credentials: 'include'
        });

        if (!res.ok) {
            const text = await res.text();
            container.innerHTML = `<p style="color:#e11;font-size:12px;padding:8px">HTTP ${res.status}: ${text}</p>`;
            return;
        }

        const data = await res.json();

        if (!data.success) {
            container.innerHTML = `<p style="color:#e11;font-size:12px;padding:8px">API error: ${data.message ?? 'unknown'}</p>`;
            return;
        }

        if (!data.data?.length) {
            container.innerHTML = `<div class="dash-empty">
                <i class="fas fa-receipt"></i>
                <p>No transactions found for this branch.</p>
            </div>`;
            return;
        }

        // FIX: Define today here — it was missing from this scope before
        const today = new Date().toISOString().slice(0, 10);

        const todayRevenue = data.data
            .filter(t => t.status === 'paid' && (t.created_at ?? '').slice(0, 10) === today)
            .reduce((sum, t) => sum + parseFloat(t.amount_paid || 0), 0);

        const totalRevenue = data.data
            .filter(t => t.status === 'paid')
            .reduce((sum, t) => sum + parseFloat(t.amount_paid || 0), 0);

        const summary = `<div class="txn-summary">
            <div class="txn-chip">
                <span class="txn-chip-label"><i class="fas fa-sun"></i> Today's Revenue</span>
                <span class="txn-chip-value">${formatCurrency(todayRevenue)}</span>
            </div>
            <div class="txn-chip">
                <span class="txn-chip-label"><i class="fas fa-coins"></i> Total Revenue</span>
                <span class="txn-chip-value">${formatCurrency(totalRevenue)}</span>
            </div>
        </div>`;

        const rows = data.data.slice(0, 5).map(t => {
            const payBadge = t.payment_method === 'gcash'
                ? `<span class="pay-badge pay-gcash"><i class="fas fa-mobile-alt"></i> GCash</span>`
                : `<span class="pay-badge pay-cash"><i class="fas fa-money-bill-wave"></i> Cash</span>`;
            const statusBadge = t.status === 'paid'
                ? `<span class="status-badge status-confirmed">Paid</span>`
                : `<span class="status-badge status-pending">${capitalize(t.status)}</span>`;
            return `<tr>
                <td><strong>#${t.payment_id}</strong></td>
                <td>${t.customer_name ?? '—'}</td>
                <td>${formatCurrency(t.amount_paid)}</td>
                <td>${payBadge}</td>
                <td>${statusBadge}</td>
                <td>${formatDatetime(t.created_at)}</td>
            </tr>`;
        }).join('');

        container.innerHTML = summary + `<table class="dash-table">
            <thead><tr><th>ID</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`;

    } catch (e) {
        container.innerHTML = `<p style="color:#e11;font-size:13px;padding:8px 0">Error: ${e.message}</p>`;
    }
}

// ── Also fix getAllReservations to allow cashier role ──
// (see ReservationController.php fix below)

// ── Init ──
loadDashboardStats();
loadTodayReservations();
loadRecentTransactions();
