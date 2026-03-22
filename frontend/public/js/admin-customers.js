// API base URL - adjust based on your environment (using URL param format)
const API_BASE_URL = '../../backend/public/index.php?url=customers';

// Store fetched customers data
let customers = [];
let customerReservationsCache = {};
let filteredCustomers = [];
let pagination;

// Fetch customers from API
async function fetchCustomers(search = '', filterReservations = '') {
    let url = `${API_BASE_URL}&search=${encodeURIComponent(search)}&filter_reservations=${encodeURIComponent(filterReservations)}`;
    
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include'
    });

    const responseText = await response.text();
    
    if (!response.ok) {
        throw new Error(`Server error: ${response.status} - ${responseText}`);
    }

    try {
        const result = JSON.parse(responseText);

        if (result.success) {
            customers = result.data.map(customer => ({
                id: customer.id,
                name: customer.name,
                contact: customer.contact || 'N/A',
                email: customer.email,
                reservation_count: customer.reservation_count
            }));
            return result;
        } else {
            throw new Error(result.message || 'Failed to fetch customers');
        }
    } catch (e) {
        if (e instanceof SyntaxError) {
            throw new Error('Invalid JSON response: ' + responseText);
        }
        throw e;
    }
}

// Fetch customer reservations from API
async function fetchCustomerReservations(userId) {
    // Check cache first
    if (customerReservationsCache[userId]) {
        return customerReservationsCache[userId];
    }

    const url = `../../backend/public/index.php?url=customers/${userId}/reservations`;
    
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include'
    });

    const responseText = await response.text();
    
    if (!response.ok) {
        throw new Error(`Server error: ${response.status} - ${responseText}`);
    }

    try {
        const result = JSON.parse(responseText);

        if (result.success && result.data) {
            // Transform data to match expected format - handle object (numeric keys) and array
            let dataArray = Array.isArray(result.data) ? result.data : Object.values(result.data);
            // Filter out null values
            dataArray = dataArray.filter(res => res !== null && res !== undefined);
            
            const reservations = dataArray.map(res => ({
                id: `ID: ${res.reservation_id}`,
                service: res.service_name || 'N/A',
                duration: res.duration_minutes ? `${res.duration_minutes} minutes` : 'N/A',
                reservationDate: res.reservation_date || '',
                scheduledDate: res.schedule_date || '',
                scheduledTime: res.start_time ? formatTime(res.start_time) : 'N/A',
                status: res.status || 'unknown',
                branch: res.branch_name || 'N/A',
                price: res.total_price ? `₱${res.total_price}` : '₱0',
                paymentStatus: 'completed',
                services: res.services || []
            }));
            
            // Cache the results
            customerReservationsCache[userId] = reservations;
            return reservations;
        } else {
            throw new Error(result.message || 'Failed to fetch reservations - no data returned');
        }
    } catch (e) {
        if (e instanceof SyntaxError) {
            throw new Error('Invalid JSON response: ' + responseText);
        }
        throw e;
    }
}

// Format time from 24h to 12h format
function formatTime(timeStr) {
    if (!timeStr) return 'N/A';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes || '00'} ${ampm}`;
}

// Status badge colors
const statusColors = {
    'confirmed': 'status-confirmed',
    'completed': 'status-completed',
    'cancelled': 'status-cancelled',
    'rescheduled': 'status-rescheduled',
    'no-show': 'status-no-show'
};

document.addEventListener('DOMContentLoaded', function() {
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        searchCustomers(searchInput.value);
    });
    
    // Filter functionality
    const filterSelect = document.getElementById('filterReservations');
    filterSelect.addEventListener('change', function() {
        filterCustomersByReservations(filterSelect.value);
    });
    
    // Modal functionality
    const closeReservationsModal = document.getElementById('closeReservationsModal');
    const closeReservationsModalBtn = document.getElementById('closeReservationsModalBtn');
    const customerReservationsModal = document.getElementById('customerReservationsModal');
    
    closeReservationsModal.addEventListener('click', function() {
        customerReservationsModal.classList.remove('show');
    });
    
    closeReservationsModalBtn.addEventListener('click', function() {
        customerReservationsModal.classList.remove('show');
    });
    
    customerReservationsModal.addEventListener('click', function(e) {
        if (e.target === customerReservationsModal) {
            customerReservationsModal.classList.remove('show');
        }
    });
    
    const closeReservationDetailsModal = document.getElementById('closeReservationDetailsModal');
    const closeReservationDetailsModalBtn = document.getElementById('closeReservationDetailsModalBtn');
    const reservationDetailsModal = document.getElementById('reservationDetailsModal');
    
    closeReservationDetailsModal.addEventListener('click', function() {
        reservationDetailsModal.classList.remove('show');
    });
    
    closeReservationDetailsModalBtn.addEventListener('click', function() {
        reservationDetailsModal.classList.remove('show');
    });
    
    reservationDetailsModal.addEventListener('click', function(e) {
        if (e.target === reservationDetailsModal) {
            reservationDetailsModal.classList.remove('show');
        }
    });
    
    // Initialize pagination
    pagination = new Pagination({
        totalItems: 0,
        itemsPerPage: 5,
        currentPage: 1,
        onPageChange: function(page, itemsPerPage) {
            loadCustomers();
        }
    });
    
    // Now load customers (setTimeout ensures pagination is ready)
    setTimeout(async () => {
        try {
            await loadCustomers();
        } catch (err) {
            const tableBody = document.getElementById('customersTableBody');
            if (tableBody) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Failed to load customers</p>
                            <p class="subtitle">${err.message || 'Unknown error'}</p>
                        </td>
                    </tr>
                `;
            }
        }
    }, 0);
});

async function loadCustomers(searchTerm = '', filterValue = '') {
    const tableBody = document.getElementById('customersTableBody');
    if (!tableBody) return;
    tableBody.innerHTML = `
        <tr class="loading-row">
            <td colspan="6">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading customers...</span>
                </div>
            </td>
        </tr>
    `;
    
    try {
        const result = await fetchCustomers(searchTerm, filterValue);
        filteredCustomers = [...customers];
        
        if (filteredCustomers.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No customers found</p>
                        <p class="subtitle">Try adjusting your search or filters</p>
                    </td>
                </tr>
            `;
            pagination.updateTotalItems(0);
            return;
        }
        
        // Clear loading and display customers
        tableBody.innerHTML = '';
        
        // Get current page range from pagination
        const range = pagination.getCurrentPageRange();
        const customersToDisplay = filteredCustomers.slice(range.start, range.end);
        
        customersToDisplay.forEach(customer => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="customer-id">${customer.id}</td>
                <td class="customer-name">${customer.name}</td>
                <td class="customer-contact">${customer.contact}</td>
                <td class="customer-email">${customer.email}</td>
                <td class="reservation-count">${customer.reservation_count || 0}</td>
                <td>
                    <button class="action-btn" onclick="viewCustomerReservations(${customer.id})">
                        <i class="fas fa-eye"></i>
                        <span>View Reservations</span>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        
        // Update pagination
        pagination.updateTotalItems(filteredCustomers.length);
    } catch (error) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load customers</p>
                    <p class="subtitle">Please try again later</p>
                </td>
            </tr>
        `;
    }
}

// Search functionality
let searchTimeout;
function searchCustomers(searchTerm) {
    // Debounce search
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const filterValue = document.getElementById('filterReservations')?.value || 'all';
        pagination.goToPage(1);
        loadCustomers(searchTerm, filterValue);
    }, 300);
}

// Filter functionality
function filterCustomersByReservations(filterValue) {
    const searchTerm = document.getElementById('searchInput')?.value || '';
    pagination.goToPage(1); // Reset to first page
    loadCustomers(searchTerm, filterValue);
}

async function viewCustomerReservations(customerId) {
    const customer = customers.find(c => c.id === customerId);
    if (!customer) return;
    
    // Populate customer details
    document.getElementById('modalCustomerName').textContent = `${customer.name}'s Reservations`;
    document.getElementById('modalCustomerFullName').textContent = customer.name;
    document.getElementById('modalCustomerContact').textContent = customer.contact;
    document.getElementById('modalCustomerEmail').textContent = customer.email;
    
    // Show loading state
    const reservationsList = document.getElementById('reservationsList');
    const noReservationsDiv = document.getElementById('noReservations');
    
    reservationsList.innerHTML = `
        <div class="loading-row">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading reservations...</span>
            </div>
        </div>
    `;
    noReservationsDiv.style.display = 'none';
    
    // Show modal
    document.getElementById('customerReservationsModal').classList.add('show');
    
    try {
        // Fetch reservations from API
        const reservations = await fetchCustomerReservations(customerId);
        document.getElementById('modalTotalReservations').textContent = reservations.length;
        
        if (reservations.length === 0) {
            reservationsList.innerHTML = '';
            noReservationsDiv.style.display = 'block';
        } else {
            noReservationsDiv.style.display = 'none';
            reservationsList.innerHTML = '';
            
            reservations.forEach(reservation => {
                const reservationItem = document.createElement('div');
                reservationItem.className = 'reservation-item';
                reservationItem.innerHTML = `
                    <div class="reservation-header">
                        <span class="reservation-id">${reservation.id}</span>
                        <span class="status-badge ${statusColors[reservation.status]}">${capitalizeFirst(reservation.status)}</span>
                    </div>
                    <div class="reservation-service">${reservation.service}</div>
                    <div class="reservation-date">${formatDate(reservation.scheduledDate)} at ${reservation.scheduledTime}</div>
                `;
                
                reservationItem.addEventListener('click', function() {
                    showReservationDetails(reservation);
                });
                
                reservationsList.appendChild(reservationItem);
            });
        }
    } catch (error) {
        reservationsList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-circle"></i>
                <p>Failed to load reservations</p>
                <p class="subtitle">${error.message || 'Unknown error'}</p>
            </div>
        `;
    }
}

function showReservationDetails(reservation) {
    document.getElementById('modalReservationId').textContent = reservation.id;
    
    // Build services list HTML if available
    let servicesHtml = '';
    if (reservation.services && reservation.services.length > 0) {
        servicesHtml = '<ul class="services-list">';
        reservation.services.forEach(svc => {
            servicesHtml += `<li>${svc.service_name} (${svc.duration_minutes} min) - ₱${svc.price}</li>`;
        });
        servicesHtml += '</ul>';
    } else {
        servicesHtml = reservation.service;
    }
    
    const detailsDiv = document.getElementById('reservationDetails');
    detailsDiv.innerHTML = `
        <div class="reservation-detail-group">
            <div class="reservation-detail-item">
                <span class="reservation-detail-label">Service(s):</span>
                <span class="reservation-detail-value">${servicesHtml}</span>
            </div>
            <div class="reservation-detail-item">
                <span class="reservation-detail-label">Duration:</span>
                <span class="reservation-detail-value">${reservation.duration}</span>
            </div>
        </div>
        
        <div class="reservation-detail-group">
            <div class="reservation-detail-item">
                <span class="reservation-detail-label">Reservation Date:</span>
                <span class="reservation-detail-value">${formatDate(reservation.reservationDate)}</span>
            </div>
            <div class="reservation-detail-item">
                <span class="reservation-detail-label">Scheduled Date & Time:</span>
                <span class="reservation-detail-value">${formatDate(reservation.scheduledDate)} at ${reservation.scheduledTime}</span>
            </div>
        </div>
        
        <div class="reservation-detail-group">
            <div class="reservation-detail-item">
                <span class="reservation-detail-label">Price:</span>
                <span class="reservation-detail-value">${reservation.price}</span>
            </div>
            <div class="reservation-detail-item">
                <span class="reservation-detail-label">Status:</span>
                <span class="reservation-detail-value">
                    <span class="status-badge ${statusColors[reservation.status]}">${capitalizeFirst(reservation.status)}</span>
                </span>
            </div>
        </div>
    `;
    
    document.getElementById('reservationDetailsModal').classList.add('show');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
