const API_BASE = '../../backend/public/index.php?url=transaction/getByBranch';

let allTransactions = [];
let filteredTransactions = [];

// ── Fetch transactions from backend ──────────────────────────────────
async function fetchTransactions() {
    try {
        const res = await fetch(API_BASE, { credentials: 'same-origin' });
        const data = await res.json();

        if (data.status === 'success') {
            allTransactions = data.data;
            filteredTransactions = [...allTransactions];
            renderTransactions();
            updateSummaryCards();
        } else {
            showEmpty('Could not load transactions.');
        }
    } catch (err) {
        console.error('Fetch error:', err);
        showEmpty('Failed to connect to server.');
    }
}

// ── Render table rows ─────────────────────────────────────────────────
function renderTransactions(data = filteredTransactions) {
    const tbody = document.getElementById('transactionsTableBody');

    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-row">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No transactions found.</p>
                        <span class="subtitle">Try adjusting your filters</span>
                    </div>
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = data.map(tx => `
        <tr onclick="openTransactionModal(${tx.payment_id})" title="Click to view details">
            <td>#${tx.payment_id}</td>
            <td>#${tx.reservation_id}</td>
            <td>${escapeHtml(tx.customer_name || '—')}</td>
            <td>₱${parseFloat(tx.amount_paid).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
            <td>
                <span class="method-badge method-${tx.payment_method}">
                    <i class="fas fa-${tx.payment_method === 'gcash' ? 'mobile-alt' : 'wallet'}"></i>
                    ${tx.payment_method.charAt(0).toUpperCase() + tx.payment_method.slice(1)}
                </span>
            </td>
            <td><span class="status-badge ${tx.status}">${tx.status}</span></td>
            <td>
                ${tx.paymongo_payment_id
                    ? `<span class="ref-text">${escapeHtml(tx.paymongo_payment_id)}</span>`
                    : `<span class="ref-none">N/A</span>`
                }
            </td>
            <td>${formatDate(tx.created_at)}</td>
        </tr>
    `).join('');
}

// ── Summary Cards ─────────────────────────────────────────────────────
function updateSummaryCards() {
    const total = allTransactions.length;
    const paid = allTransactions.filter(t => t.status === 'paid');
    const unpaid = allTransactions.filter(t => t.status === 'unpaid');
    const totalAmt = paid.reduce((sum, t) => sum + parseFloat(t.amount_paid), 0);

    document.getElementById('totalCount').textContent = total;
    document.getElementById('totalCollected').textContent =
        '₱' + totalAmt.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('unpaidCount').textContent = unpaid.length;
}

// ── Modal ─────────────────────────────────────────────────────────────
function openTransactionModal(paymentId) {
    const tx = allTransactions.find(t => t.payment_id == paymentId);
    if (!tx) return;

    document.getElementById('transactionModalBody').innerHTML = `
        <div class="detail-row">
            <span class="detail-label">Payment ID</span>
            <span class="detail-value">#${tx.payment_id}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Reservation ID</span>
            <span class="detail-value">#${tx.reservation_id}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Customer Name</span>
            <span class="detail-value">${escapeHtml(tx.customer_name || '—')}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Customer Email</span>
            <span class="detail-value">${escapeHtml(tx.customer_email || '—')}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Branch</span>
            <span class="detail-value">${escapeHtml(tx.branch_name || '—')}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Reservation Date</span>
            <span class="detail-value">${tx.reservation_date || '—'}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Reservation Total</span>
            <span class="detail-value">₱${parseFloat(tx.total_price || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount Paid</span>
            <span class="detail-value">₱${parseFloat(tx.amount_paid).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value">${tx.payment_method}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value"><span class="status-badge ${tx.status}">${tx.status}</span></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">PayMongo Reference</span>
            <span class="detail-value" style="font-family:monospace; font-size:0.82rem;">
                ${tx.paymongo_payment_id || 'N/A'}
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date Paid</span>
            <span class="detail-value">${formatDate(tx.created_at)}</span>
        </div>
    `;

    document.getElementById('transactionModal').style.display = 'flex';
}

document.getElementById('closeTransactionModal').addEventListener('click', () => {
    document.getElementById('transactionModal').style.display = 'none';
});

document.getElementById('transactionModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// ── Filters & Search ──────────────────────────────────────────────────
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const status = document.getElementById('filterStatus').value;
    const method = document.getElementById('filterMethod').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    filteredTransactions = allTransactions.filter(tx => {
        const matchSearch = !search ||
            (tx.customer_name && tx.customer_name.toLowerCase().includes(search)) ||
            String(tx.reservation_id).includes(search) ||
            String(tx.payment_id).includes(search);

        const matchStatus = status === 'all' || tx.status === status;
        const matchMethod = method === 'all' || tx.payment_method === method;

        const txDate = tx.created_at ? tx.created_at.substring(0, 10) : '';
        const matchStart = !startDate || txDate >= startDate;
        const matchEnd   = !endDate   || txDate <= endDate;

        return matchSearch && matchStatus && matchMethod && matchStart && matchEnd;
    });

    renderTransactions();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('filterStatus').addEventListener('change', applyFilters);
document.getElementById('filterMethod').addEventListener('change', applyFilters);
document.getElementById('applyDateFilter').addEventListener('click', applyFilters);

document.getElementById('clearFilters').addEventListener('click', () => {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatus').value = 'all';
    document.getElementById('filterMethod').value = 'all';
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    filteredTransactions = [...allTransactions];
    renderTransactions();
});

// ── Helpers ───────────────────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function showEmpty(msg) {
    document.getElementById('transactionsTableBody').innerHTML =
        `<tr><td colspan="8" class="empty-row"><div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>${msg}</p><span class="subtitle">Please try again later</span></div></td></tr>`;
}

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', fetchTransactions);
