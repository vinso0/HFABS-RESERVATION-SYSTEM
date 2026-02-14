document.addEventListener('DOMContentLoaded', function() {
    // Fetch real data from API
    fetchTodaysReservations();

    // Set active sidebar item
    const currentPage = window.location.pathname.split('/').pop();
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (currentPage.includes('admin-todays-reservations.php')) {
            if (item.querySelector('span').textContent.includes("Today's Reservations")) {
                item.classList.add('active');
            }
        }
    });
});

// Fetch today's reservations from backend API
async function fetchTodaysReservations() {
    try {
        const response = await fetch('../../backend/public/index.php?url=reservation/getTodaysReservations');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            const reservations = result.data;
            renderReservations(reservations);
        } else {
            console.error('Error fetching reservations:', result.message);
            renderReservations([]); // Show empty state
        }
    } catch (error) {
        console.error('Error fetching reservations:', error);
        // Fallback to sample data if API fails
        const sampleReservations = getSampleReservations();
        renderReservations(sampleReservations);
    }
}

// Get sample reservations for fallback
function getSampleReservations() {
    return [
        {
            reservation_id: 1,
            customer_name: 'John Doe',
            customer_contact: '+63 917 123 4567',
            customer_email: 'john.doe@example.com',
            services: [
                {
                    service_name: 'Hair Cut',
                    category_name: 'Hair Services',
                    price: 500.00,
                    duration_minutes: 30,
                    description: 'Professional hair cutting service'
                },
                {
                    service_name: 'Facial Treatment',
                    category_name: 'Facial Services',
                    price: 1200.00,
                    duration_minutes: 60,
                    description: 'Deep cleansing facial treatment'
                }
            ],
            schedule: {
                start_time: '09:00:00',
                end_time: '10:30:00'
            },
            reservation_date: '2026-02-14',
            branch_name: 'Quezon City Branch',
            status: 'confirmed',
            total_price: 1700.00
        },
        {
            reservation_id: 2,
            customer_name: 'Jane Smith',
            customer_contact: '+63 917 987 6543',
            customer_email: 'jane.smith@example.com',
            services: [
                {
                    service_name: 'Full Body Massage',
                    category_name: 'Massage Services',
                    price: 1500.00,
                    duration_minutes: 90,
                    description: 'Relaxing full body massage'
                }
            ],
            schedule: {
                start_time: '10:00:00',
                end_time: '11:30:00'
            },
            reservation_date: '2026-02-14',
            branch_name: 'Quezon City Branch',
            status: 'confirmed',
            total_price: 1500.00
        },
        {
            reservation_id: 3,
            customer_name: 'Michael Johnson',
            customer_contact: '+63 918 456 7890',
            customer_email: 'michael.johnson@example.com',
            services: [
                {
                    service_name: 'Hair Color',
                    category_name: 'Hair Services',
                    price: 1800.00,
                    duration_minutes: 90,
                    description: 'Professional hair coloring service'
                },
                {
                    service_name: 'Hair Treatment',
                    category_name: 'Hair Services',
                    price: 1200.00,
                    duration_minutes: 60,
                    description: 'Nourishing hair treatment'
                }
            ],
            schedule: {
                start_time: '11:30:00',
                end_time: '13:30:00'
            },
            reservation_date: '2026-02-14',
            branch_name: 'Quezon City Branch',
            status: 'confirmed',
            total_price: 3000.00
        },
        {
            reservation_id: 4,
            customer_name: 'Sarah Williams',
            customer_contact: '+63 919 234 5678',
            customer_email: 'sarah.williams@example.com',
            services: [
                {
                    service_name: 'Nail Art',
                    category_name: 'Nail Services',
                    price: 800.00,
                    duration_minutes: 60,
                    description: 'Creative nail art design'
                },
                {
                    service_name: 'Foot Spa',
                    category_name: 'Nail Services',
                    price: 600.00,
                    duration_minutes: 60,
                    description: 'Relaxing foot spa treatment'
                }
            ],
            schedule: {
                start_time: '14:00:00',
                end_time: '16:00:00'
            },
            reservation_date: '2026-02-14',
            branch_name: 'Quezon City Branch',
            status: 'confirmed',
            total_price: 1400.00
        },
        {
            reservation_id: 5,
            customer_name: 'David Brown',
            customer_contact: '+63 920 345 6789',
            customer_email: 'david.brown@example.com',
            services: [
                {
                    service_name: 'Hot Stone Massage',
                    category_name: 'Massage Services',
                    price: 1800.00,
                    duration_minutes: 90,
                    description: 'Hot stone therapy massage'
                }
            ],
            schedule: {
                start_time: '15:30:00',
                end_time: '17:00:00'
            },
            reservation_date: '2026-02-14',
            branch_name: 'Quezon City Branch',
            status: 'confirmed',
            total_price: 1800.00
        }
    ];
}

// Render reservations
function renderReservations(reservations) {
    const reservationsContainer = document.getElementById('reservationsContainer');
    const noReservations = document.getElementById('noReservations');

    // Sort reservations by start time
    const sortedReservations = [...reservations].sort((a, b) => {
        const timeA = parseTime(a.schedule?.start_time || '00:00:00');
        const timeB = parseTime(b.schedule?.start_time || '00:00:00');
        return timeA - timeB;
    });

    if (sortedReservations.length === 0) {
        noReservations.style.display = 'block';
        reservationsContainer.innerHTML = '';
    } else {
        noReservations.style.display = 'none';
        reservationsContainer.innerHTML = '';
        sortedReservations.forEach(reservation => {
            const card = createReservationCard(reservation);
            reservationsContainer.appendChild(card);
        });
    }
}

// Parse time string to minutes for sorting
function parseTime(timeStr) {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}

// Format date for display (MMMM DD, YYYY)
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Format time for display (HH:MM AM/PM)
function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Create reservation card element
function createReservationCard(reservation) {
    const card = document.createElement('div');
    card.className = 'reservation-card';

    card.innerHTML = `
        <div class="reservation-header">
            <div class="reservation-info">
                <div class="reservation-id">${reservation.reservation_id}</div>
                <div class="customer-name">${reservation.customer_name}</div>
                <div class="customer-contact">${reservation.customer_contact}</div>
                <span class="status-badge status-${reservation.status}">${capitalizeFirstLetter(reservation.status)}</span>
            </div>
            <div class="reservation-time">
                <div class="time-display">
                    <div class="start-time">${formatTime(reservation.schedule?.start_time || '00:00:00')}</div>
                    <div class="end-time">to ${formatTime(reservation.schedule?.end_time || '00:00:00')}</div>
                </div>
            </div>
        </div>
        
        <div class="reservation-details">
            <div class="detail-item">
                <div class="detail-label">Services</div>
                <div class="detail-value service-list">
                    ${(reservation.services || []).map(service =>
                        `<span class="service-tag">${typeof service === 'string' ? service : service.service_name}</span>`
                    ).join('')}
                </div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Date</div>
                <div class="detail-value">${formatDate(reservation.reservation_date)}</div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Branch</div>
                <div class="detail-value">${reservation.branch_name}</div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Total Price</div>
                <div class="detail-value">₱${reservation.total_price?.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00'}</div>
            </div>
        </div>
        
        <div class="reservation-actions">
            <button class="action-btn btn-view-details" onclick="viewReservationDetails(${reservation.reservation_id})">
                <i class="fas fa-eye"></i>
                <span>View Details</span>
            </button>
            <button class="action-btn btn-completed" onclick="handleStatusChange(${reservation.reservation_id}, 'completed')">
                <i class="fas fa-check"></i>
                <span>Completed</span>
            </button>
            <button class="action-btn btn-cancelled" onclick="handleStatusChange(${reservation.reservation_id}, 'cancelled')">
                <i class="fas fa-times"></i>
                <span>Cancelled</span>
            </button>
            <button class="action-btn btn-no-show" onclick="handleStatusChange(${reservation.reservation_id}, 'no-show')">
                <i class="fas fa-user-slash"></i>
                <span>No-Show</span>
            </button>
        </div>
    `;

    return card;
}

// Helper function to capitalize first letter
function capitalizeFirstLetter(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// View reservation details - Opens modal
window.viewReservationDetails = function(reservationId) {
    // In a real implementation, you would fetch the full reservation details
    // from the API and populate the modal
    
    // For now, we'll simulate this with sample data
    const reservations = getSampleReservations().find(r => r.reservation_id == reservationId);
    
    if (reservations) {
        // Populate modal with reservation data
        populateReservationModal(reservations);
        openReservationModal();
    } else {
        alert('Reservation not found!');
    }
};

// Populate reservation modal
function populateReservationModal(reservation) {
    // Service details (using first service)
    if (reservation.services && reservation.services.length > 0) {
        const service = typeof reservation.services[0] === 'string'
            ? { service_name: reservation.services[0], category_name: 'N/A', description: 'N/A', duration_minutes: 0, price: 0 }
            : reservation.services[0];
            
        document.getElementById('modalService').textContent = service.service_name;
        document.getElementById('modalCategory').textContent = service.category_name;
        document.getElementById('modalDescription').textContent = service.description;
        document.getElementById('modalDuration').textContent = service.duration_minutes ? `${service.duration_minutes} minutes` : 'N/A';
        document.getElementById('modalPrice').textContent = service.price ? `₱${parseFloat(service.price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'N/A';
    } else {
        document.getElementById('modalService').textContent = 'N/A';
        document.getElementById('modalCategory').textContent = 'N/A';
        document.getElementById('modalDescription').textContent = 'N/A';
        document.getElementById('modalDuration').textContent = 'N/A';
        document.getElementById('modalPrice').textContent = 'N/A';
    }
    
    document.getElementById('modalDate').textContent = formatDate(reservation.reservation_date);
    document.getElementById('modalTime').textContent = `${formatTime(reservation.schedule?.start_time || '00:00:00')} - ${formatTime(reservation.schedule?.end_time || '00:00:00')}`;
    
    document.getElementById('modalCustomerName').textContent = reservation.customer_name;
    document.getElementById('modalEmail').textContent = reservation.customer_email || 'customer@example.com';
    document.getElementById('modalContact').textContent = reservation.customer_contact;
    
    const statusBadge = `<span class="status-badge status-${reservation.status}">${capitalizeFirstLetter(reservation.status)}</span>`;
    document.getElementById('modalStatus').innerHTML = statusBadge;
}

// Status change handler
window.handleStatusChange = function(reservationId, newStatus) {
    console.log(`Changing status of reservation ${reservationId} to ${newStatus}`);
    
    // Show confirmation modal
    showStatusConfirmationModal(reservationId, newStatus);
};

// Show status confirmation modal
function showStatusConfirmationModal(reservationId, newStatus) {
    // Store reservation details for confirmation
    window.confirmReservationId = reservationId;
    window.confirmNewStatus = newStatus;
    
    // Set modal content based on status
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalIcon = document.getElementById('modalIcon');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    
    const statusName = capitalizeFirstLetter(newStatus);
    
    modalTitle.textContent = `Confirm ${statusName}`;
    modalMessage.textContent = `Are you sure you want to mark this reservation as ${statusName}?`;
    
    // Set appropriate icon and button color based on status
    modalIcon.innerHTML = '';
    const icon = document.createElement('i');
    
    if (newStatus === 'completed') {
        icon.className = 'fas fa-check-circle';
        modalIcon.style.color = '#28a745'; // Green
        modalConfirmBtn.style.backgroundColor = '#28a745';
    } else if (newStatus === 'cancelled') {
        icon.className = 'fas fa-times-circle';
        modalIcon.style.color = '#dc3545'; // Red
        modalConfirmBtn.style.backgroundColor = '#dc3545';
    } else if (newStatus === 'no-show') {
        icon.className = 'fas fa-user-slash';
        modalIcon.style.color = '#ffc107'; // Yellow
        modalConfirmBtn.style.backgroundColor = '#ffc107';
        modalConfirmBtn.style.color = '#000';
    }
    
    modalIcon.appendChild(icon);
    
    // Show modal
    const modal = document.getElementById('statusConfirmationModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close status confirmation modal
function closeStatusConfirmationModal() {
    const modal = document.getElementById('statusConfirmationModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Clear stored data
    window.confirmReservationId = null;
    window.confirmNewStatus = null;
}

// Confirm status change
function confirmStatusChange() {
    if (window.confirmReservationId && window.confirmNewStatus) {
        updateReservationStatus(window.confirmReservationId, window.confirmNewStatus);
    }
    
    closeStatusConfirmationModal();
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('statusConfirmationModal');
    if (event.target === modal) {
        closeStatusConfirmationModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeStatusConfirmationModal();
    }
});

// Update reservation status via API
async function updateReservationStatus(reservationId, status) {
    try {
        const response = await fetch('../../backend/public/index.php?url=reservation/updateReservationStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reservation_id: reservationId,
                status: status
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Reservation status updated successfully');
            // Refresh reservations list
            fetchTodaysReservations();
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        console.error('Error updating reservation status:', error);
        alert('Failed to update reservation status. Please try again.');
    }
}

// Modal functions (copy from reservations-data.js)
function openReservationModal() {
    const modal = document.getElementById('reservationModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeReservationModal() {
    const modal = document.getElementById('reservationModal');
    modal.classList.remove('active');
    document.body.style.overflow = ''; // Restore scrolling
}

function editReservation() {
    alert('Edit functionality will be implemented here');
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
