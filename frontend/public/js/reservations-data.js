// Global variables for reservations data
let reservationsData = [];
let currentPage = 1;
let itemsPerPage = 10;
let currentStatus = 'all';
let totalReservations = 0;
let currentStartDate = null;
let currentEndDate = null;

// API endpoint for reservations
const API_URL = '../../backend/public/index.php?url=reservation/getAllReservations';

// Function to fetch reservations from API
async function fetchReservations(status = 'all', page = 1, perPage = 10, startDate = null, endDate = null) {
    try {
        let url = `${API_URL}&status=${status}&page=${page}&itemsPerPage=${perPage}`;
        
        // Add date range parameters if provided
        if (startDate && endDate) {
            url += `&startDate=${startDate}&endDate=${endDate}`;
        }
        
        const response = await fetch(url, {
            credentials: 'same-origin'
        });
        
        // Check if unauthorized (session expired or not logged in)
        if (response.status === 401) {
            console.error('Unauthorized - please log in as admin. Check console for details.');
            alert('Session expired or not authorized. Please log in again.');
            // Don't redirect automatically, let user see the error
            return null;
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            reservationsData = result.data;
            totalReservations = result.total;
            return result;
        } else {
            console.error('Error fetching reservations:', result.message);
            return null;
        }
    } catch (error) {
        console.error('Error fetching reservations:', error);
        // Fallback to empty data if API fails
        reservationsData = [];
        totalReservations = 0;
        return null;
    }
}

// Function to get status badge class
function getStatusBadgeClass(status) {
    switch (status) {
        case 'confirmed':
            return 'status-confirmed';
        case 'no-show':
            return 'status-no-show';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        case 'rescheduled':
            return 'status-rescheduled';
        default:
            return 'status-confirmed';
    }
}

// Function to capitalize first letter
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Function to format time for display
function formatTime(timeString) {
    if (!timeString) return 'N/A';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Function to format price
function formatPrice(price) {
    return `₱${parseFloat(price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// Function to populate table with data
function populateTableWithPagination(data) {
    const tbody = document.getElementById('reservationsTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="6" style="text-align: center; padding: 40px;">
                No reservations found
            </td>
        `;
        tbody.appendChild(row);
        return;
    }

    data.forEach(reservation => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td class="reservation-id">${reservation.reservation_id}</td>
            <td class="customer-name">${reservation.customer_name || 'N/A'}</td>
            <td class="service-name">${reservation.services && reservation.services.length > 0 ? reservation.services[0].service_name : 'N/A'}</td>
            <td>
                <div class="date-time">
                    <span class="date">${reservation.schedule?.schedule_date || reservation.reservation_date || 'N/A'}</span>
                    <span class="time">${formatTime(reservation.start_time)}</span>
                </div>
            </td>
            <td>
                <span class="status-badge ${getStatusBadgeClass(reservation.status)}">
                    ${capitalize(reservation.status)}
                </span>
            </td>
            <td>
                <a href="#" class="action-btn" onclick="viewReservation(${reservation.reservation_id}); return false;">
                    <i class="far fa-eye"></i>
                    View
                </a>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

// Global variable to store all fetched data for view modal
let allReservationsCache = [];

// Global variable to track current reservation ID
let currentReservationId = null;

// Function to view reservation - Opens modal
async function viewReservation(reservationId) {
    // Store current reservation ID for edit function
    currentReservationId = reservationId;
    
    // Try to find in current page data first
    let reservation = reservationsData.find(r => r.reservation_id === reservationId);
    
    // If not found, try to fetch from cache or API
    if (!reservation) {
        reservation = allReservationsCache.find(r => r.reservation_id === reservationId);
    }
    
    // If still not found, try fetching from API directly
    if (!reservation) {
        const result = await fetchReservations('all', 1, 1000);
        if (result && result.data) {
            reservation = result.data.find(r => r.reservation_id === reservationId);
        }
    }
    
    if (!reservation) {
        showNotification('Reservation not found!', 'error');
        return;
    }

    // Populate modal with reservation data
    if (reservation.services && reservation.services.length > 0) {
        const service = reservation.services[0];
        document.getElementById('modalService').textContent = service.service_name || 'N/A';
        document.getElementById('modalCategory').textContent = service.category_name || 'N/A';
        document.getElementById('modalDescription').textContent = service.description || 'N/A';
        document.getElementById('modalDuration').textContent = `${service.duration_minutes || 0} minutes`;
        document.getElementById('modalPrice').textContent = formatPrice(service.price);
    } else {
        document.getElementById('modalService').textContent = 'N/A';
        document.getElementById('modalCategory').textContent = 'N/A';
        document.getElementById('modalDescription').textContent = 'N/A';
        document.getElementById('modalDuration').textContent = 'N/A';
        document.getElementById('modalPrice').textContent = 'N/A';
    }
    
    document.getElementById('modalScheduleDate').textContent = reservation.schedule?.schedule_date || reservation.reservation_date || 'N/A';
    document.getElementById('modalReservationDate').textContent = reservation.reservation_date || 'N/A';
    document.getElementById('modalTime').textContent = `${formatTime(reservation.start_time)} - ${formatTime(reservation.end_time)}`;
    
    document.getElementById('modalCustomerName').textContent = reservation.customer_name || 'N/A';
    document.getElementById('modalEmail').textContent = reservation.customer_email || 'N/A';
    document.getElementById('modalContact').textContent = reservation.customer_contact || 'N/A';
    
    const statusBadge = `<span class="status-badge ${getStatusBadgeClass(reservation.status)}">${capitalize(reservation.status)}</span>`;
    document.getElementById('modalStatus').innerHTML = statusBadge;

    // Show modal
    openReservationModal();
}

// Function to open modal
function openReservationModal() {
    const modal = document.getElementById('reservationModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

// Function to close modal
function closeReservationModal() {
    const modal = document.getElementById('reservationModal');
    modal.classList.remove('active');
    document.body.style.overflow = ''; // Restore scrolling
}

// Function to edit reservation (placeholder)
async function editReservation() {
    // First, get the current reservation data from the view modal
    const reservationId = document.getElementById('modalService').dataset.reservationId || getCurrentReservationId();
    
    // Try to find in current page data first
    let reservation = reservationsData.find(r => r.reservation_id === parseInt(reservationId));
    
    if (!reservation) {
        reservation = allReservationsCache.find(r => r.reservation_id === parseInt(reservationId));
    }
    
    if (!reservation) {
        showNotification('Reservation not found!', 'error');
        return;
    }
    
    // Close view modal first
    closeReservationModal();
    
    // Populate edit form with reservation data
    document.getElementById('editReservationId').value = reservation.reservation_id;
    document.getElementById('editReservationDate').value = reservation.reservation_date;
    document.getElementById('editReservationTime').value = reservation.start_time;
    document.getElementById('editReservationStatus').value = reservation.status;
    
    // Open edit modal
    openEditReservationModal();
}

// Function to open edit modal
function openEditReservationModal() {
    const modal = document.getElementById('editReservationModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to close edit modal
function closeEditReservationModal() {
    const modal = document.getElementById('editReservationModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function getCurrentReservationId() {
    return currentReservationId;
}

// Toast notification function (matching admin-services.js)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        background-color: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations dynamically
if (!document.querySelector('style[data-notification-animations]')) {
    const style = document.createElement('style');
    style.setAttribute('data-notification-animations', 'true');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Function to handle edit form submission
async function handleEditReservation(event) {
    event.preventDefault();
    
    const reservationId = document.getElementById('editReservationId').value;
    const newDate = document.getElementById('editReservationDate').value;
    const newTime = document.getElementById('editReservationTime').value;
    const newStatus = document.getElementById('editReservationStatus').value;
    
    if (!reservationId || !newDate || !newTime || !newStatus) {
        showNotification('Please fill in all required fields.', 'error');
        return;
    }
    
    try {
        // Determine if we need to update status or reschedule
        const reservation = reservationsData.find(r => r.reservation_id === parseInt(reservationId)) || 
                          allReservationsCache.find(r => r.reservation_id === parseInt(reservationId));
        
        let result;
        
        // Check if date/time changed
        const dateChanged = reservation && (reservation.reservation_date !== newDate || reservation.start_time !== newTime);
        
        if (dateChanged && newStatus !== 'rescheduled') {
            // If date/time changed, we need to reschedule first
            const rescheduleResponse = await fetch('../../backend/public/index.php?url=reservation/adminReschedule', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    reservation_id: parseInt(reservationId),
                    new_date: newDate,
                    new_time: newTime,
                    reason: 'Updated by admin'
                })
            });
            
            const rescheduleResult = await rescheduleResponse.json();
            
            if (!rescheduleResult.success) {
                showNotification('Failed to reschedule: ' + rescheduleResult.message, 'error');
                return;
            }
        }
        
        // If status changed, update status
        if (newStatus !== reservation?.status) {
            const statusResponse = await fetch('../../backend/public/index.php?url=reservation/updateReservationStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    reservation_id: parseInt(reservationId),
                    status: newStatus
                })
            });
            
            result = await statusResponse.json();
            
            if (!result.success) {
                showNotification('Failed to update status: ' + result.message, 'error');
                return;
            }
        }
        
        // Close modal
        closeEditReservationModal();
        
        // Show success message
        showNotification('Reservation updated successfully!', 'success');
        
        // Refresh the table
        await fetchReservations(currentStatus, currentPage, itemsPerPage);
        displayCurrentPage();
        
    } catch (error) {
        console.error('Error updating reservation:', error);
        showNotification('Failed to update reservation. Please try again.', 'error');
    }
}

// Initialize edit form submission
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editReservationForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditReservation);
    }
});

// Close edit modal when clicking outside
document.addEventListener('click', function(event) {
    const editModal = document.getElementById('editReservationModal');
    if (event.target === editModal) {
        closeEditReservationModal();
    }
});

// Close edit modal with Escape key
document.addEventListener('keydown', function(event) {
    const editModal = document.getElementById('editReservationModal');
    if (event.key === 'Escape' && editModal.classList.contains('active')) {
        closeEditReservationModal();
    }
});

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

// Pagination instance
let reservationPagination;

// Function to display current page data
function displayCurrentPage() {
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const pageData = reservationsData.slice(start, end);
    populateTableWithPagination(pageData);
}

// Function to update pagination
function updatePagination() {
    if (reservationPagination) {
        reservationPagination.totalItems = totalReservations;
        reservationPagination.itemsPerPage = itemsPerPage;
        reservationPagination.currentPage = currentPage;
        reservationPagination.render();
    }
}

// Function to load initial data
async function loadInitialData() {
    const result = await fetchReservations(currentStatus, currentPage, itemsPerPage, currentStartDate, currentEndDate);
    
    if (result) {
        // Initialize pagination
        reservationPagination = new Pagination({
            totalItems: totalReservations,
            itemsPerPage: itemsPerPage,
            currentPage: currentPage,
            maxVisiblePages: 5,
            onPageChange: async (page, perPage) => {
                currentPage = page;
                itemsPerPage = perPage;
                
                await fetchReservations(currentStatus, currentPage, itemsPerPage, currentStartDate, currentEndDate);
                displayCurrentPage();
            }
        });
        
        displayCurrentPage();
    } else {
        // Initialize with empty data
        reservationPagination = new Pagination({
            totalItems: 0,
            itemsPerPage: 10,
            currentPage: 1,
            maxVisiblePages: 5,
            onPageChange: async (page, perPage) => {
                currentPage = page;
                itemsPerPage = perPage;
                
                await fetchReservations(currentStatus, currentPage, itemsPerPage, currentStartDate, currentEndDate);
                displayCurrentPage();
            }
        });
        
        populateTableWithPagination([]);
    }
}

// Function to filter reservations by date range
async function filterReservationsByDate(startDate, endDate) {
    currentStartDate = startDate;
    currentEndDate = endDate;
    currentPage = 1;
    
    const result = await fetchReservations(currentStatus, currentPage, itemsPerPage, startDate, endDate);
    
    if (result) {
        updatePagination();
        displayCurrentPage();
    }
}

// Function to clear date range filter
async function clearDateFilter() {
    currentStartDate = null;
    currentEndDate = null;
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    currentPage = 1;
    
    const result = await fetchReservations(currentStatus, currentPage, itemsPerPage);
    
    if (result) {
        updatePagination();
        displayCurrentPage();
    }
}

// Function to filter reservations
async function filterReservations(status) {
    currentStatus = status;
    currentPage = 1;
    
    const result = await fetchReservations(status, currentPage, itemsPerPage, currentStartDate, currentEndDate);
    
    if (result) {
        updatePagination();
        displayCurrentPage();
    }
}

// Function to load initial data
async function loadInitialData() {
    const result = await fetchReservations(currentStatus, currentPage, itemsPerPage);
    
    if (result) {
        // Initialize pagination
        reservationPagination = new Pagination({
            totalItems: totalReservations,
            itemsPerPage: itemsPerPage,
            currentPage: currentPage,
            maxVisiblePages: 5,
            onPageChange: async (page, perPage) => {
                currentPage = page;
                itemsPerPage = perPage;
                
                await fetchReservations(currentStatus, currentPage, itemsPerPage);
                displayCurrentPage();
            }
        });
        
        displayCurrentPage();
    } else {
        // Initialize with empty data
        reservationPagination = new Pagination({
            totalItems: 0,
            itemsPerPage: 10,
            currentPage: 1,
            maxVisiblePages: 5,
            onPageChange: async (page, perPage) => {
                currentPage = page;
                itemsPerPage = perPage;
                
                await fetchReservations(currentStatus, currentPage, itemsPerPage);
                displayCurrentPage();
            }
        });
        
        populateTableWithPagination([]);
    }
}

// Update DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data from API
    loadInitialData();
    
    // Set up auto-refresh for real-time data (every 30 seconds)
    setInterval(() => {
        fetchReservations(currentStatus, currentPage, itemsPerPage, currentStartDate, currentEndDate).then(result => {
            if (result) {
                displayCurrentPage();
            }
        });
    }, 30000);
    
    // Add filter event listener
    const filterSelect = document.getElementById('filterStatus');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterReservations(this.value);
        });
    }
    
    // Add date filter event listeners
    const applyDateFilterBtn = document.getElementById('applyDateFilter');
    const clearDateFilterBtn = document.getElementById('clearDateFilter');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (applyDateFilterBtn) {
        applyDateFilterBtn.addEventListener('click', function() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            if (startDate && endDate) {
                if (startDate > endDate) {
                    showNotification('Start date cannot be after end date', 'error');
                    return;
                }
                
                filterReservationsByDate(startDate, endDate);
            } else {
                showNotification('Please select both start and end dates', 'error');
            }
        });
    }
    
    if (clearDateFilterBtn) {
        clearDateFilterBtn.addEventListener('click', function() {
            clearDateFilter();
        });
    }
    
    // Allow pressing Enter to apply filter
    if (startDateInput) {
        startDateInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyDateFilterBtn.click();
            }
        });
    }
    
    if (endDateInput) {
        endDateInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyDateFilterBtn.click();
            }
        });
    }
});