document.addEventListener('DOMContentLoaded', init);

let GRID_START_HOUR = 8;    // fallbacks in case fetchBranchHours fails
let GRID_END_HOUR   = 22;
const HOUR_HEIGHT_PX = 64;

/* ─── Boot ───────────────────────────────────────────────── */
async function init() {
    await fetchBranchHours();
    fetchTodaysReservations();

    setInterval(async () => {
        await fetchBranchHours();
        fetchTodaysReservations();
    }, 30000);

    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    navItems.forEach(item => {
        if (item.querySelector('span') &&
            item.querySelector('span').textContent.includes("Today's Reservations")) {
            item.classList.add('active');
        }
    });
}

/* ─── Fetch branch opening/closing time ─────────────────── */
async function fetchBranchHours() {
    try {
        const res = await fetch('../../backend/public/index.php?url=branch/settings', {
            credentials: 'same-origin'
        });
        if (!res.ok) return;

        const result = await res.json();
        if (result.success && result.data) {
            const opening = result.data.opening_time;
            const closing = result.data.closing_time; 

            if (opening) {
                GRID_START_HOUR = parseInt(opening.split(':')[0], 10);
            }
            if (closing) {
                const [ch, cm] = closing.split(':').map(Number);
                GRID_END_HOUR = cm > 0 ? ch + 1 : ch;
            }
        }
    } catch (e) {
        console.warn('[BranchHours] fetch error, using defaults:', e);
    }
}

/* ─── API fetch ──────────────────────────────────── */
async function fetchTodaysReservations() {
    try {
        const response = await fetch('../../backend/public/index.php?url=reservation/getTodaysReservations', {
            credentials: 'same-origin'
        });

        if (response.status === 401) {
            alert('Session expired or not authorized. Please log in again.');
            return;
        }
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const result = await response.json();

        // ── FIX 1: result.data is always an array (never wrapped in .reservations)
        // getTodaysReservations returns a plain array, not { reservations: [], total: n }
        const data = result.success ? result.data : [];
        renderReservations(Array.isArray(data) ? data : []);

    } catch (error) {
        console.error('Error fetching reservations:', error);
        renderReservations(getSampleReservations());
    }
}

/* ─── Sample fallback data ───────────────────────── */
function getSampleReservations() {
    return [
        {
            reservation_id: 1,
            customer_name: 'John Doe',
            customer_contact: '+63 917 123 4567',
            customer_email: 'john.doe@example.com',
            services: [
                { service_name: 'Hair Cut',        category_name: 'Hair Services',    price: 500,  duration_minutes: 30 },
                { service_name: 'Facial Treatment', category_name: 'Facial Services', price: 1200, duration_minutes: 60 }
            ],
            schedule: { schedule_date: '2026-04-10', start_time: '09:00:00', end_time: '10:30:00' },
            reservation_date: '2026-04-10',
            branch_name: 'Quezon City Branch',
            status: 'confirmed',
            total_price: 1700
        },
        {
            reservation_id: 2,
            customer_name: 'Jane Smith',
            customer_contact: '+63 917 987 6543',
            customer_email: 'jane.smith@example.com',
            services: [
                { service_name: 'Full Body Massage', category_name: 'Massage Services', price: 1500, duration_minutes: 90 }
            ],
            schedule: { schedule_date: '2026-04-10', start_time: '09:30:00', end_time: '11:00:00' },
            reservation_date: '2026-04-10',
            branch_name: 'Quezon City Branch',
            status: 'confirmed',
            total_price: 1500
        }
    ];
}

/* ─── Helpers ────────────────────────────────────── */
function parseTime(timeStr) {
    if (!timeStr || typeof timeStr !== 'string') return -1;
    const parts = timeStr.split(':');
    if (parts.length < 2) return -1;
    const h = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    if (isNaN(h) || isNaN(m)) return -1;
    return h * 60 + m;
}

function formatTime(timeStr) {
    if (!timeStr) return 'N/A';
    const parts = timeStr.split(':');
    if (parts.length < 2) return 'N/A';
    const hour = parseInt(parts[0], 10);
    const min  = parts[1];
    return `${hour % 12 || 12}:${min} ${hour >= 12 ? 'PM' : 'AM'}`;
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function capitalizeFirstLetter(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ── FIX 3: Safe schedule accessor — reads actual field names from the model
// Model returns: { schedule_date, start_time, end_time }
function getStartTime(reservation) {
    return reservation.schedule?.start_time || null;
}

function getEndTime(reservation) {
    return reservation.schedule?.end_time || null;
}

function getScheduleDate(reservation) {
    // Model returns schedule_date inside schedule object
    return reservation.schedule?.schedule_date || reservation.reservation_date || null;
}

/* ─── Overlap layout ─────────────────────────────── */
function computeLayout(reservations) {
    const valid = reservations.filter(r => {
        const s = parseTime(getStartTime(r));
        const e = parseTime(getEndTime(r));
        return s >= 0 && e > s;
    });

    const sorted = [...valid].sort((a, b) =>
        parseTime(getStartTime(a)) - parseTime(getStartTime(b))
    );

    const columns = [];
    const layout  = [];

    sorted.forEach(res => {
        const start = parseTime(getStartTime(res));
        const end   = parseTime(getEndTime(res));

        let placed = false;
        for (let c = 0; c < columns.length; c++) {
            const lastEnd = parseTime(getEndTime(columns[c][columns[c].length - 1]));
            if (start >= lastEnd) {
                columns[c].push(res);
                layout.push({ reservation: res, col: c });
                placed = true;
                break;
            }
        }

        if (!placed) {
            columns.push([res]);
            layout.push({ reservation: res, col: columns.length - 1 });
        }
    });

    layout.forEach(item => {
    const start = parseTime(getStartTime(item.reservation));
    const end   = parseTime(getEndTime(item.reservation));

        // Collect all columns used by ANY reservation that overlaps with this one
        const overlappingCols = new Set();
        layout.forEach(other => {
            const oStart = parseTime(getStartTime(other.reservation));
            const oEnd   = parseTime(getEndTime(other.reservation));
            if (oStart < end && oEnd > start) {
                overlappingCols.add(other.col);
            }
        });

        item.totalCols = overlappingCols.size;
    });

    return layout;
}

/* ─── Current time indicator ─────────────────────── */
function renderCurrentTimeLine(slotsCol) {
    const now         = new Date();
    const nowMin      = now.getHours() * 60 + now.getMinutes();
    const gridStartMin = GRID_START_HOUR * 60;
    const gridEndMin   = GRID_END_HOUR   * 60;

    if (nowMin < gridStartMin || nowMin > gridEndMin) return;

    const topPx = (nowMin - gridStartMin) / 60 * HOUR_HEIGHT_PX;

    const line = document.createElement('div');
    line.className = 'current-time-line';
    line.style.top = `${topPx}px`;

    const dot = document.createElement('div');
    dot.className = 'current-time-dot';
    line.appendChild(dot);

    const lbl = document.createElement('div');
    lbl.className = 'current-time-label';
    lbl.textContent = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    line.appendChild(lbl);

    slotsCol.appendChild(line);

    setTimeout(() => {
        line.remove();
        renderCurrentTimeLine(slotsCol);
    }, 60000 - now.getSeconds() * 1000);
}

/* ─── Main render ────────────────────────────────── */
let currentReservations = [];

function renderReservations(reservations) {
    currentReservations = reservations;

    const noReservations  = document.getElementById('noReservations');
    const calendarWrapper = document.getElementById('calendarWrapper');
    const grid            = document.getElementById('timeGrid');

    if (!reservations || reservations.length === 0) {
        grid.innerHTML = '';
        noReservations.style.display  = 'block';
        calendarWrapper.style.display = 'none';
        updateStats([]);
        return;
    }

    noReservations.style.display  = 'none';
    calendarWrapper.style.display = 'block';
    grid.innerHTML = '';

    const totalHours  = GRID_END_HOUR - GRID_START_HOUR;
    const totalGridPx = totalHours * HOUR_HEIGHT_PX;

    // ── Left: time-label column ──
    const labelCol = document.createElement('div');
    labelCol.className = 'time-label-col';
    labelCol.style.height = `${totalGridPx}px`;

    for (let h = GRID_START_HOUR; h <= GRID_END_HOUR; h++) {
        const lbl     = document.createElement('div');
        lbl.className = 'hour-label';
        const suffix  = h >= 12 ? 'PM' : 'AM';
        const display = h % 12 || 12;
        lbl.textContent = `${display} ${suffix}`;
        labelCol.appendChild(lbl);
    }

    // ── Right: slots column ──
    const slotsCol = document.createElement('div');
    slotsCol.className = 'time-slots-col';
    slotsCol.style.height   = `${totalGridPx}px`;
    slotsCol.style.position = 'relative';

    for (let h = 0; h < totalHours; h++) {
        const row     = document.createElement('div');
        row.className = 'hour-row';
        slotsCol.appendChild(row);
    }

    renderCurrentTimeLine(slotsCol);

    const layout = computeLayout(reservations);
    const GUTTER = 4;

    layout.forEach(({ reservation, col, totalCols }) => {
        const startStr = getStartTime(reservation);
        const endStr   = getEndTime(reservation);
        const start    = parseTime(startStr);
        const end      = parseTime(endStr);

        // ── FIX 5: Skip blocks with invalid times (guard against null schedule)
        if (start < 0 || end <= start) return;

        const gridStart = GRID_START_HOUR * 60;
        const topPx     = (start - gridStart) / 60 * HOUR_HEIGHT_PX;
        const heightPx  = Math.max((end - start) / 60 * HOUR_HEIGHT_PX - 2, 24);
        const widthPct  = (1 / totalCols) * 100;
        const leftPct   = (col / totalCols) * 100;

        const block     = document.createElement('div');
        block.className = `res-block status-${reservation.status}`;
        if (heightPx >= 80) block.classList.add('tall');

        block.style.cssText = `
            top:    ${topPx}px;
            height: ${heightPx}px;
            left:   calc(${leftPct}% + ${col === 0 ? 4 : GUTTER}px);
            width:  calc(${widthPct}% - ${GUTTER * 2}px);
            z-index: ${col + 1};
        `;

        const serviceList = (reservation.services || []).slice(0, 2)
            .map(s => (typeof s === 'string' ? s : s.service_name))
            .join(', ');
        const moreServices = reservation.services && reservation.services.length > 2
            ? ` +${reservation.services.length - 2}` : '';

        block.innerHTML = `
            <div class="res-block-name">${reservation.customer_name || 'Unknown'}</div>
            <div class="res-block-time">${formatTime(startStr)} – ${formatTime(endStr)}</div>
            ${heightPx >= 48 ? `<div class="res-block-services">${serviceList}${moreServices}</div>` : ''}
            ${heightPx >= 64 ? `<div class="res-block-status">${capitalizeFirstLetter(reservation.status)}</div>` : ''}
            <div class="res-block-actions">
                <button class="res-block-btn btn-view"   onclick="event.stopPropagation();viewReservationDetails(${reservation.reservation_id})"><i class="fas fa-eye"></i> View</button>
                <button class="res-block-btn btn-done"   onclick="event.stopPropagation();handleStatusChange(${reservation.reservation_id},'completed')"><i class="fas fa-check"></i></button>
                <button class="res-block-btn btn-cancel" onclick="event.stopPropagation();handleStatusChange(${reservation.reservation_id},'cancelled')"><i class="fas fa-times"></i></button>
                <button class="res-block-btn btn-noshow" onclick="event.stopPropagation();handleStatusChange(${reservation.reservation_id},'no-show')"><i class="fas fa-user-slash"></i></button>
            </div>
        `;

        block.addEventListener('click', () => viewReservationDetails(reservation.reservation_id));
        slotsCol.appendChild(block);
    });

    grid.appendChild(labelCol);
    grid.appendChild(slotsCol);

    updateStats(reservations);
    scrollToNowOrFirst(reservations);
}

/* ─── Stats bar ──────────────────────────────────── */
function updateStats(reservations) {
    document.getElementById('statTotal').textContent          = reservations.length;
    document.getElementById('statConfirmed').textContent     = reservations.filter(r => r.status === 'confirmed').length;
    document.getElementById('statRescheduled').textContent   = reservations.filter(r => r.status === 'rescheduled').length;
    document.getElementById('statCompleted').textContent     = reservations.filter(r => r.status === 'completed').length;
    document.getElementById('statNoShow').textContent        = reservations.filter(r => r.status === 'no-show').length;
}

/* ─── Auto-scroll ────────────────────────────────── */
function scrollToNowOrFirst(reservations) {
    const wrapper      = document.getElementById('calendarWrapper');
    const now          = new Date();
    const nowMin       = now.getHours() * 60 + now.getMinutes();
    const gridStartMin = GRID_START_HOUR * 60;

    if (nowMin >= gridStartMin) {
        wrapper.scrollTop = Math.max(0, (nowMin - gridStartMin) / 60 * HOUR_HEIGHT_PX - 80);
    } else if (reservations.length > 0) {
        const firstStart = parseTime(getStartTime(reservations[0]));
        if (firstStart >= 0) {
            wrapper.scrollTop = Math.max(0, (firstStart - gridStartMin) / 60 * HOUR_HEIGHT_PX - 40);
        }
    }
}

/* ─── Modal + status logic ───────────────────────── */
let confirmReservationId = null;
let confirmNewStatus     = null;

window.viewReservationDetails = function (reservationId) {
    const reservation = currentReservations.find(r => r.reservation_id == reservationId);
    if (reservation) {
        populateTodaysReservationDetailsModal(reservation);
        openTodaysReservationDetailsModal();
    } else {
        alert('Reservation not found!');
    }
};

function populateTodaysReservationDetailsModal(reservation) {
    // Expose reservation ID for the inline onclick buttons in the modal
    window._modalReservationId = reservation.reservation_id;

    document.getElementById('todaysModalCustomerName').textContent = reservation.customer_name || 'N/A';
    document.getElementById('todaysModalEmail').textContent        = reservation.customer_email || 'N/A';
    document.getElementById('todaysModalContact').textContent      = reservation.customer_contact || 'N/A';

    // Services list
    const servicesContainer = document.getElementById('todaysModalServices');
    if (reservation.services && reservation.services.length > 0) {
        servicesContainer.innerHTML = reservation.services.map(service => {
            const name     = typeof service === 'string' ? service : (service.service_name || 'N/A');
            const category = typeof service === 'string' ? 'N/A' : (service.category_name || 'N/A');
            const price    = typeof service === 'string' ? 'N/A'
                : `₱${parseFloat(service.price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
            const balance  = (typeof service !== 'string' && service.remaining_balance != null)
                ? `₱${parseFloat(service.remaining_balance).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
                : null;
            return `<div class="service-item">
                        <div class="service-name">${name}</div>
                        <div class="service-category">${category}</div>
                        <div class="service-price">${price}</div>
                        ${balance ? `<div class="service-balance" style="font-size:11px;color:#d91a7e;margin-top:2px;">Balance: ${balance}</div>` : ''}
                    </div>`;
        }).join('');
    } else {
        servicesContainer.innerHTML = '<p>No services listed</p>';
    }

    // Reservation info
    document.getElementById('todaysModalReservationId').textContent   = reservation.reservation_id;
    document.getElementById('todaysModalScheduleDate').textContent    = formatDate(getScheduleDate(reservation));
    document.getElementById('todaysModalReservationDate').textContent = formatDate(reservation.reservation_date);
    document.getElementById('todaysModalTime').textContent =
        `${formatTime(getStartTime(reservation))} - ${formatTime(getEndTime(reservation))}`;
    document.getElementById('todaysModalBranch').textContent = reservation.branch_name || 'N/A';

    // Payment summary
    document.getElementById('todaysModalTotalPrice').textContent =
        `₱${parseFloat(reservation.total_price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;

    const remaining = reservation.total_remaining_balance != null
        ? parseFloat(reservation.total_remaining_balance)
        : null;
    const balanceEl = document.getElementById('todaysModalRemainingBalance');
    if (remaining !== null) {
        balanceEl.textContent = `₱${remaining.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        balanceEl.style.color = remaining > 0 ? '#d91a7e' : '#0d894f';
    } else {
        balanceEl.textContent = 'N/A';
    }

    // Status badge
    document.getElementById('todaysModalStatus').innerHTML =
        `<span class="status-badge status-${reservation.status}">${capitalizeFirstLetter(reservation.status)}</span>`;

    // Show/hide status action buttons based on current status
    // Terminal statuses (completed, cancelled, no-show) cannot be changed
    const terminalStatuses = ['completed', 'cancelled', 'no-show'];
    const actionsSection = document.getElementById('todaysModalStatusActions');
    if (terminalStatuses.includes(reservation.status)) {
        actionsSection.classList.add('modal-status-actions-hidden');
    } else {
        actionsSection.classList.remove('modal-status-actions-hidden');
    }
}

function openTodaysReservationDetailsModal() {
    document.getElementById('todaysReservationDetailsModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeTodaysReservationDetailsModal() {
    document.getElementById('todaysReservationDetailsModal').classList.remove('active');
    document.body.style.overflow = '';
}

window.handleStatusChange = function (reservationId, newStatus) {
    showStatusConfirmationModal(reservationId, newStatus);
};

function showStatusConfirmationModal(reservationId, newStatus) {
    confirmReservationId = reservationId;
    confirmNewStatus     = newStatus;

    const statusName      = capitalizeFirstLetter(newStatus);
    document.getElementById('modalTitle').textContent   = `Confirm ${statusName}`;
    document.getElementById('modalMessage').textContent = `Are you sure you want to mark this reservation as ${statusName}?`;

    const modalIcon       = document.getElementById('modalIcon');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    modalIcon.innerHTML   = '';
    const icon            = document.createElement('i');

    if (newStatus === 'completed') {
        icon.className                     = 'fas fa-check-circle';
        modalIcon.style.color              = '#28a745';
        modalConfirmBtn.style.backgroundColor = '#28a745';
        modalConfirmBtn.style.color        = '#fff';
    } else if (newStatus === 'cancelled') {
        icon.className                     = 'fas fa-times-circle';
        modalIcon.style.color              = '#dc3545';
        modalConfirmBtn.style.backgroundColor = '#dc3545';
        modalConfirmBtn.style.color        = '#fff';
    } else if (newStatus === 'no-show') {
        icon.className                     = 'fas fa-user-slash';
        modalIcon.style.color              = '#ffc107';
        modalConfirmBtn.style.backgroundColor = '#ffc107';
        modalConfirmBtn.style.color        = '#000';
    }

    modalIcon.appendChild(icon);
    document.getElementById('statusConfirmationModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeStatusConfirmationModal() {
    document.getElementById('statusConfirmationModal').classList.remove('active');
    document.body.style.overflow = '';
    confirmReservationId = null;
    confirmNewStatus     = null;
}

function confirmStatusChange() {
    if (confirmReservationId && confirmNewStatus) {
        updateReservationStatus(confirmReservationId, confirmNewStatus);
    }
    closeStatusConfirmationModal();
}

async function updateReservationStatus(reservationId, status) {
    try {
        const response = await fetch('../../backend/public/index.php?url=reservation/updateReservationStatus', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ reservation_id: reservationId, status })
        });

        if (response.status === 401) {
            Toast.error('Session expired. Please log in again.');
            return;
        }

        const result = await response.json();

        if (result.success) {
            const statusLabels = {
                'completed': 'Reservation marked as Completed.',
                'cancelled': 'Reservation has been Cancelled.',
                'no-show':   'Reservation marked as No-Show.'
            };
            Toast.success(statusLabels[status] || 'Status updated successfully.');
            fetchTodaysReservations();
        } else {
            Toast.error(result.message || 'Failed to update reservation status.');
        }
    } catch (error) {
        console.error('Error updating reservation status:', error);
        Toast.error('Could not connect to the server. Please try again.');
    }
}

// Close modals on outside click or Escape
document.addEventListener('click', function (e) {
    if (e.target === document.getElementById('todaysReservationDetailsModal')) closeTodaysReservationDetailsModal();
    if (e.target === document.getElementById('statusConfirmationModal'))       closeStatusConfirmationModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeTodaysReservationDetailsModal();
        closeStatusConfirmationModal();
    }
});