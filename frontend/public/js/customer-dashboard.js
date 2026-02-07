// Customer Dashboard JavaScript

// Function to fetch customer reservations
async function fetchReservations() {
    try {
        const response = await fetch('/HFABS/backend/public/index.php?url=reservation/getCustomerReservations', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success) {
            return result.data;
        } else {
            console.error('Failed to fetch reservations:', result.message);
            return [];
        }
    } catch (error) {
        console.error('Error fetching reservations:', error);
        return [];
    }
}

// Function to get status badge class
function getStatusBadgeClass(status) {
    switch (status) {
        case 'confirmed':
            return 'status-confirmed';
        case 'completed':
            return 'status-completed';
        case 'pending':
            return 'status-pending';
        case 'cancelled':
            return 'status-cancelled';
        case 'rescheduled':
            return 'status-rescheduled';
        default:
            return 'status-pending';
    }
}

// Function to capitalize first letter
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Function to format time for display
function formatTime(timeString) {
    if (!timeString) return '';
    
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Function to format price
function formatPrice(price) {
    return `₱${parseFloat(price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// Function to create reservation card HTML
function createReservationCard(reservation) {
    const servicesHtml = reservation.services.map(service => 
        `<span class="service-tag">${service.service_name}</span>`
    ).join('');

    const scheduleDate = reservation.schedule?.schedule_date || reservation.reservation_date;
    const startTime = reservation.schedule?.start_time ? formatTime(reservation.schedule.start_time) : '';
    const endTime = reservation.schedule?.end_time ? formatTime(reservation.schedule.end_time) : '';

    return `
        <div class="reservation-card">
            <div class="reservation-header">
                <div class="reservation-id">ID: ${reservation.reservation_id}</div>
                <div class="reservation-status ${getStatusBadgeClass(reservation.status)}">
                    ${capitalize(reservation.status)}
                </div>
            </div>
            
            <div class="reservation-content">
                <div class="reservation-branch">${reservation.branch_name}</div>
                
                <div class="reservation-services">
                    ${servicesHtml}
                </div>
                
                <div class="reservation-details">
                    <div class="detail-item">
                        <span class="detail-label">Date</span>
                        <span class="detail-value">${scheduleDate}</span>
                    </div>
                    
                    ${startTime ? `
                        <div class="detail-item">
                            <span class="detail-label">Time</span>
                            <span class="detail-value">${startTime} - ${endTime}</span>
                        </div>
                    ` : ''}
                    
                    <div class="detail-item">
                        <span class="detail-label">Total Price</span>
                        <span class="detail-value">${formatPrice(reservation.total_price)}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Remaining Balance</span>
                        <span class="detail-value">${formatPrice(reservation.total_remaining_balance)}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Order Status</span>
                        <span class="detail-value">${capitalize(reservation.order_status || 'pending')}</span>
                    </div>
                </div>
            </div>
            
            <div class="reservation-actions">
                <button class="action-btn btn-primary" onclick="viewReservationDetails(${reservation.reservation_id})">
                    <i class="fas fa-eye"></i>
                    View Details
                </button>
                
                ${reservation.status === 'pending' ? `
                    <button class="action-btn btn-secondary" onclick="cancelReservation(${reservation.reservation_id})">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                ` : ''}
                
                ${reservation.status === 'confirmed' ? `
                    <button class="action-btn btn-secondary" onclick="rescheduleReservation(${reservation.reservation_id})">
                        <i class="fas fa-calendar"></i>
                        Reschedule
                    </button>
                ` : ''}
            </div>
        </div>
    `;
}

// Function to display reservations
function displayReservations(reservations, containerId) {
    const container = document.getElementById(containerId);
    
    if (reservations.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Reservations Found</h3>
                <p>You haven't made any reservations yet. Book your first appointment today!</p>
            </div>
        `;
        return;
    }
    
    const reservationsHtml = reservations.map(reservation => 
        createReservationCard(reservation)
    ).join('');
    
    container.innerHTML = reservationsHtml;
}

// Function to view reservation details (opens modal)
function viewReservationDetails(reservationId) {
    const reservation = window.currentReservations.find(r => r.reservation_id === reservationId);
    
    if (!reservation) {
        alert('Reservation not found!');
        return;
    }
    
    const servicesHtml = reservation.services.map(service => `
        <li class="modal-service-item">
            <div class="modal-detail">
                <span class="modal-detail-label">Service</span>
                <span class="modal-detail-value">${service.service_name}</span>
            </div>
            <div class="modal-detail">
                <span class="modal-detail-label">Price</span>
                <span class="modal-detail-value">${formatPrice(service.price)}</span>
            </div>
            ${service.duration_minutes ? `
                <div class="modal-detail">
                    <span class="modal-detail-label">Duration</span>
                    <span class="modal-detail-value">${service.duration_minutes} minutes</span>
                </div>
            ` : ''}
        </li>
    `).join('');
    
    const scheduleDate = reservation.schedule?.schedule_date || reservation.reservation_date;
    const startTime = reservation.schedule?.start_time ? formatTime(reservation.schedule.start_time) : '';
    const endTime = reservation.schedule?.end_time ? formatTime(reservation.schedule.end_time) : '';
    
    const modalBody = document.getElementById('modalBody');
    modalBody.innerHTML = `
        <div class="modal-detail">
            <span class="modal-detail-label">Reservation ID</span>
            <span class="modal-detail-value">${reservation.reservation_id}</span>
        </div>
        
        <div class="modal-detail">
            <span class="modal-detail-label">Branch</span>
            <span class="modal-detail-value">${reservation.branch_name}</span>
        </div>
        
        <div class="modal-detail">
            <span class="modal-detail-label">Date</span>
            <span class="modal-detail-value">${scheduleDate}</span>
        </div>
        
        ${startTime ? `
            <div class="modal-detail">
                <span class="modal-detail-label">Time</span>
                <span class="modal-detail-value">${startTime} - ${endTime}</span>
            </div>
        ` : ''}
        
        <div class="modal-detail">
            <span class="modal-detail-label">Status</span>
            <span class="modal-detail-value">
                <span class="reservation-status ${getStatusBadgeClass(reservation.status)}">
                    ${capitalize(reservation.status)}
                </span>
            </span>
        </div>
        
        <div class="modal-detail">
            <span class="modal-detail-label">Total Price</span>
            <span class="modal-detail-value">${formatPrice(reservation.total_price)}</span>
        </div>
        
        <div class="modal-detail">
            <span class="modal-detail-label">Remaining Balance</span>
            <span class="modal-detail-value">${formatPrice(reservation.total_remaining_balance)}</span>
        </div>
        
        <div class="modal-detail">
            <span class="modal-detail-label">Order Status</span>
            <span class="modal-detail-value">${capitalize(reservation.order_status || 'pending')}</span>
        </div>
        
        <div class="modal-services">
            <h3>Services</h3>
            <ul class="modal-service-list">
                ${servicesHtml}
            </ul>
        </div>
    `;
    
    // Show modal
    document.getElementById('reservationModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to cancel reservation (placeholder)
function cancelReservation(reservationId) {
    if (confirm('Are you sure you want to cancel this reservation?')) {
        // TODO: Implement cancel functionality
        alert('Cancel functionality will be implemented soon');
    }
}

// Function to reschedule reservation (placeholder)
function rescheduleReservation(reservationId) {
    // TODO: Implement reschedule functionality
    alert('Reschedule functionality will be implemented soon');
}

// Function to close modal
function closeReservationModal() {
    document.getElementById('reservationModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('reservationModal');
    if (event.target === modal) {
        closeReservationModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReservationModal();
    }
});

// Initialize pagination
let reservationPagination;
let currentFilteredData = [];
window.currentReservations = [];

// Function to filter reservations with pagination
function filterReservationsWithPagination(status) {
    if (status === 'all') {
        currentFilteredData = window.currentReservations;
    } else {
        currentFilteredData = window.currentReservations.filter(res => res.status === status);
    }
    
    // Update pagination total
    if (reservationPagination) {
        reservationPagination.updateTotalItems(currentFilteredData.length);
        displayCurrentPage();
    }
}

// Function to display current page data
function displayCurrentPage() {
    const range = reservationPagination.getCurrentPageRange();
    const pageData = currentFilteredData.slice(range.start, range.end);
    displayReservations(pageData, 'reservationsList');
}

// Main initialization
document.addEventListener('DOMContentLoaded', async function() {
    // Load reservations
    const reservationsList = document.getElementById('reservationsList');
    reservationsList.innerHTML = `
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <p>Loading reservations...</p>
        </div>
    `;
    
    const reservations = await fetchReservations();
    window.currentReservations = reservations;
    currentFilteredData = reservations;
    
    // Initialize pagination
    reservationPagination = new Pagination({
        totalItems: reservations.length,
        itemsPerPage: 5,
        currentPage: 1,
        maxVisiblePages: 5,
        onPageChange: (page, itemsPerPage) => {
            displayCurrentPage();
        }
    });
    
    // Display initial page
    displayCurrentPage();
    
    // Add filter event listener
    const filterSelect = document.getElementById('filterStatus');
    filterSelect.addEventListener('change', function() {
        filterReservationsWithPagination(this.value);
    });
});
