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
                </div>
            </div>
            
            <div class="reservation-actions">
                <button class="action-btn btn-primary" onclick="viewReservationDetails(${reservation.reservation_id})">
                    <i class="fas fa-eye"></i>
                    View Details
                </button>
                
                ${reservation.status === 'confirmed' ? `
                    <button class="action-btn btn-secondary" onclick="openRescheduleModal(${reservation.reservation_id})">
                        <i class="fas fa-calendar"></i>
                        Reschedule
                    </button>
                    <button class="action-btn btn-secondary" onclick="cancelReservation(${reservation.reservation_id})">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                ` : ''}

                ${reservation.status === 'rescheduled' ? `
                    <button class="action-btn btn-secondary" onclick="openRescheduleModal(${reservation.reservation_id})">
                        <i class="fas fa-calendar"></i>
                        Reschedule
                    </button>
                    <button class="action-btn btn-secondary" onclick="cancelReservation(${reservation.reservation_id})">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                ` : ''}
                
                ${reservation.status === 'completed' ? (reservation.feedback && reservation.feedback.length > 0 ? `
                    <button class="action-btn btn-secondary" onclick="openViewReviewModal(${reservation.reservation_id})">
                        <i class="fas fa-eye"></i>
                        View Review
                    </button>
                ` : `
                    <button class="action-btn btn-secondary" onclick="openReviewModal(${reservation.reservation_id})">
                        <i class="fas fa-star"></i>
                        Leave Review
                    </button>
                `) : ''}
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
        <div class="modal-grid">
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

// Function to cancel reservation
async function cancelReservation(reservationId) {
    if (confirm('Are you sure you want to cancel this reservation?')) {
        try {
            const response = await fetch('/HFABS/backend/public/index.php?url=reservation/cancelReservation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ reservation_id: reservationId })
            });

            const result = await response.json();

            if (result.success) {
                alert('Reservation cancelled successfully!');
                // Refresh reservations list
                location.reload();
            } else {
                alert('Failed to cancel reservation: ' + result.message);
            }
        } catch (error) {
            console.error('Error cancelling reservation:', error);
            alert('Failed to cancel reservation. Please try again.');
        }
    }
}

// Function to open reschedule modal
function openRescheduleModal(reservationId) {
    const reservation = window.currentReservations.find(r => r.reservation_id === reservationId);
    if (!reservation) {
        alert('Reservation not found!');
        return;
    }

    // Set reservation id in modal
    document.getElementById('rescheduleReservationId').value = reservationId;
    
    // Calculate minimum date (today or later)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('newDate').min = today;
    
    // Show modal
    document.getElementById('rescheduleModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to reschedule reservation
async function rescheduleReservation() {
    const reservationId = document.getElementById('rescheduleReservationId').value;
    const newDate = document.getElementById('newDate').value;
    const newTime = document.getElementById('newTime').value;
    const reason = document.getElementById('rescheduleReason').value;

    if (!newDate || !newTime) {
        alert('Please select both date and time');
        return;
    }

    try {
        const response = await fetch('/HFABS/backend/public/index.php?url=reservation/rescheduleReservation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                reservation_id: reservationId,
                new_date: newDate,
                new_time: newTime,
                reason: reason
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Reservation rescheduled successfully!');
            // Close modal and refresh reservations list
            closeRescheduleModal();
            location.reload();
        } else {
            alert('Failed to reschedule reservation: ' + result.message);
        }
    } catch (error) {
        console.error('Error rescheduling reservation:', error);
        alert('Failed to reschedule reservation. Please try again.');
    }
}

// Function to open review modal
function openReviewModal(reservationId) {
    const reservation = window.currentReservations.find(r => r.reservation_id === reservationId);
    if (!reservation) {
        alert('Reservation not found!');
        return;
    }

    // Set reservation data in modal
    document.getElementById('reviewReservationId').value = reservationId;
    document.getElementById('reviewBranchId').value = reservation.branch_id;
    
    // Show modal
    document.getElementById('reviewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to submit review
async function submitReview() {
    const reservationId = document.getElementById('reviewReservationId').value;
    const branchId = document.getElementById('reviewBranchId').value;
    const rating = document.getElementById('reviewRating').value;
    const comment = document.getElementById('reviewComment').value;

    if (!rating || !comment) {
        alert('Please provide both rating and comment');
        return;
    }

    // Get reservation services to submit feedback for each service
    const reservation = window.currentReservations.find(r => r.reservation_id === parseInt(reservationId));
    if (!reservation || reservation.services.length === 0) {
        alert('No services found for this reservation');
        return;
    }

    try {
        // Submit feedback for each service
        for (const service of reservation.services) {
            const response = await fetch('/HFABS/backend/public/index.php?url=reservation/submitFeedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    reservation_service_id: service.reservation_service_id,
                    branch_id: branchId,
                    rating: rating,
                    comment: comment
                })
            });

            const result = await response.json();

            if (!result.success) {
                alert('Failed to submit feedback for service: ' + result.message);
                return;
            }
        }

        alert('Feedback submitted successfully!');
        // Close modal and refresh reservations list
        closeReviewModal();
        location.reload();
    } catch (error) {
        console.error('Error submitting feedback:', error);
        alert('Failed to submit feedback. Please try again.');
    }
}

// Function to close reservation modal
function closeReservationModal() {
    document.getElementById('reservationModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Make closeReservationModal globally accessible
window.closeReservationModal = closeReservationModal;

// Function to close reschedule modal
function closeRescheduleModal() {
    document.getElementById('rescheduleModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Make closeRescheduleModal globally accessible
window.closeRescheduleModal = closeRescheduleModal;
// Function to open view review modal
function openViewReviewModal(reservationId) {
    const reservation = window.currentReservations.find(r => r.reservation_id === reservationId);
    if (!reservation) {
        alert('Reservation not found!');
        return;
    }

    // Build review content
    const reviewBody = document.getElementById('viewReviewBody');
    if (reservation.feedback && reservation.feedback.length > 0) {
        // Get average rating
        const totalRating = reservation.feedback.reduce((sum, feedback) => sum + parseInt(feedback.rating), 0);
        const averageRating = totalRating / reservation.feedback.length;
        
        // Create stars display
        const fullStars = Math.floor(averageRating);
        const hasHalfStar = averageRating % 1 >= 0.5;
        let starsHtml = '';
        
        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                starsHtml += '★'; // Full star
            } else if (i === fullStars + 1 && hasHalfStar) {
                starsHtml += '☆'; // Half star (we'll use CSS to style)
            } else {
                starsHtml += '☆'; // Empty star
            }
        }

        // Display all feedback comments
        const commentsHtml = reservation.feedback.map(feedback => `
            <div class="review-comment">
                <div class="review-rating">${'★'.repeat(feedback.rating)}${'☆'.repeat(5 - feedback.rating)}</div>
                <div class="review-text">${feedback.comment}</div>
                <div class="review-date">${new Date(feedback.created_at).toLocaleDateString()}</div>
            </div>
        `).join('');

        reviewBody.innerHTML = `
            <div class="review-summary">
                <div class="average-rating">
                    <span class="rating-number">${averageRating.toFixed(1)}</span>
                    <span class="display-stars">${starsHtml}</span>
                </div>
                <div class="review-count">${reservation.feedback.length} Review${reservation.feedback.length > 1 ? 's' : ''}</div>
            </div>
            <div class="review-comments">
                ${commentsHtml}
            </div>
        `;
    } else {
        reviewBody.innerHTML = `
            <div class="no-review">
                <i class="fas fa-star"></i>
                <p>No review has been submitted for this reservation.</p>
            </div>
        `;
    }

    // Show modal
    document.getElementById('viewReviewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to close view review modal
function closeViewReviewModal() {
    document.getElementById('viewReviewModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Function to close review modal
function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Make functions globally accessible
window.closeReviewModal = closeReviewModal;
window.openViewReviewModal = openViewReviewModal;
window.closeViewReviewModal = closeViewReviewModal;


// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
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
