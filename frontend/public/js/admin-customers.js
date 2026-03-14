// Sample customers data
const customers = [
    {
        id: 1,
        name: "John Doe",
        contact: "+1 234 567 8900",
        email: "john.doe@example.com",
        reservations: [
            {
                id: "ID: 1",
                service: "Full Body Massage",
                duration: "60 minutes",
                reservationDate: "2026-03-05",
                scheduledDate: "2026-03-10",
                scheduledTime: "10:00 AM",
                status: "confirmed",
                branch: "Main Branch",
                price: "₱150",
                paymentStatus: "paid"
            },
            {
                id: "Id: 5",
                service: "Facial Treatment",
                duration: "45 minutes",
                reservationDate: "2026-03-03",
                scheduledDate: "2026-03-08",
                scheduledTime: "2:00 PM",
                status: "completed",
                branch: "Downtown Branch",
                price: "₱85",
                paymentStatus: "paid"
            },
            {
                id: "Id: 20",
                service: "Hair Styling",
                duration: "30 minutes",
                reservationDate: "2026-02-20",
                scheduledDate: "2026-02-25",
                scheduledTime: "3:30 PM",
                status: "completed",
                branch: "Main Branch",
                price: "₱75",
                paymentStatus: "paid"
            }
        ]
    },
    {
        id: 2,
        name: "Jane Smith",
        contact: "+1 345 678 9011",
        email: "jane.smith@example.com",
        reservations: [
            {
                id: "Id: 8",
                service: "Nail Art",
                duration: "90 minutes",
                reservationDate: "2026-03-08",
                scheduledDate: "2026-03-12",
                scheduledTime: "11:00 AM",
                status: "confirmed",
                branch: "East Side Branch",
                price: "₱60",
                paymentStatus: "pending"
            },
            {
                id: "15",
                service: "Body Scrub",
                duration: "60 minutes",
                reservationDate: "2026-03-01",
                scheduledDate: "2026-03-05",
                scheduledTime: "10:30 AM",
                status: "completed",
                branch: "Main Branch",
                price: "₱95",
                paymentStatus: "paid"
            }
        ]
    },
    {
        id: 3,
        name: "Mike Johnson",
        contact: "+1 456 789 0122",
        email: "mike.johnson@example.com",
        reservations: [
            {
                id: "10",
                service: "Hot Stone Massage",
                duration: "75 minutes",
                reservationDate: "2026-03-10",
                scheduledDate: "2026-03-15",
                scheduledTime: "4:00 PM",
                status: "confirmed",
                branch: "West Side Branch",
                price: "₱120",
                paymentStatus: "paid"
            }
        ]
    },
    {
        id: 4,
        name: "Emily Davis",
        contact: "+1 567 890 1233",
        email: "emily.davis@example.com",
        reservations: [
            {
                id: "3",
                service: "Hair Color",
                duration: "90 minutes",
                reservationDate: "2026-02-25",
                scheduledDate: "2026-03-02",
                scheduledTime: "1:00 PM",
                status: "completed",
                branch: "Main Branch",
                price: "₱110",
                paymentStatus: "paid"
            },
            {
                id: "7",
                service: "Hair Cut",
                duration: "30 minutes",
                reservationDate: "2026-02-15",
                scheduledDate: "2026-02-20",
                scheduledTime: "10:00 AM",
                status: "completed",
                branch: "Downtown Branch",
                price: "₱45",
                paymentStatus: "paid"
            },
            {
                id: "18",
                service: "Facial Treatment",
                duration: "45 minutes",
                reservationDate: "2026-02-10",
                scheduledDate: "2026-02-15",
                scheduledTime: "3:00 PM",
                status: "completed",
                branch: "East Side Branch",
                price: "₱85",
                paymentStatus: "paid"
            },
            {
                id: "25",
                service: "Manicure",
                duration: "30 minutes",
                reservationDate: "2026-02-05",
                scheduledDate: "2026-02-10",
                scheduledTime: "11:00 AM",
                status: "completed",
                branch: "West Side Branch",
                price: "₱50",
                paymentStatus: "paid"
            }
        ]
    },
    {
        id: 5,
        name: "Chris Wilson",
        contact: "+1 678 901 2344",
        email: "chris.wilson@example.com",
        reservations: [
            {
                id: "12",
                service: "Deep Tissue Massage",
                duration: "60 minutes",
                reservationDate: "2026-03-05",
                scheduledDate: "2026-03-09",
                scheduledTime: "5:00 PM",
                status: "rescheduled",
                branch: "Main Branch",
                price: "₱130",
                paymentStatus: "paid"
            },
            {
                id: "22",
                service: "Swedish Massage",
                duration: "60 minutes",
                reservationDate: "2026-02-25",
                scheduledDate: "2026-03-01",
                scheduledTime: "2:30 PM",
                status: "completed",
                branch: "Downtown Branch",
                price: "₱100",
                paymentStatus: "paid"
            }
        ]
    },
    {
        id: 6,
        name: "Sarah Brown",
        contact: "+1 789 012 3455",
        email: "sarah.brown@example.com",
        reservations: [
            {
                id: "6",
                service: "Pedicure",
                duration: "60 minutes",
                reservationDate: "2026-03-06",
                scheduledDate: "2026-03-11",
                scheduledTime: "9:00 AM",
                status: "confirmed",
                branch: "East Side Branch",
                price: "₱55",
                paymentStatus: "pending"
            },
            {
                id: "9",
                service: "Hair Treatment",
                duration: "45 minutes",
                reservationDate: "2026-02-20",
                scheduledDate: "2026-02-28",
                scheduledTime: "1:30 PM",
                status: "completed",
                branch: "West Side Branch",
                price: "₱90",
                paymentStatus: "paid"
            },
            {
                id: "23",
                service: "Facial Treatment",
                duration: "45 minutes",
                reservationDate: "2026-02-15",
                scheduledDate: "2026-02-22",
                scheduledTime: "4:00 PM",
                status: "completed",
                branch: "Main Branch",
                price: "₱85",
                paymentStatus: "paid"
            }
        ]
    },
    {
        id: 7,
        name: "David Lee",
        contact: "+1 890 123 4566",
        email: "david.lee@example.com",
        reservations: [
            {
                id: "21",
                service: "Back Massage",
                duration: "45 minutes",
                reservationDate: "2026-03-10",
                scheduledDate: "2026-03-14",
                scheduledTime: "3:00 PM",
                status: "confirmed",
                branch: "Downtown Branch",
                price: "₱75",
                paymentStatus: "paid"
            }
        ]
    },
    {
        id: 8,
        name: "Lisa Garcia",
        contact: "+1 901 234 5677",
        email: "lisa.garcia@example.com",
        reservations: [
            {
                id: "4",
                service: "Full Body Massage",
                duration: "60 minutes",
                reservationDate: "2026-03-01",
                scheduledDate: "2026-03-07",
                scheduledTime: "11:30 AM",
                status: "completed",
                branch: "East Side Branch",
                price: "₱150",
                paymentStatus: "paid"
            },
            {
                id: "19",
                service: "Facial Treatment",
                duration: "45 minutes",
                reservationDate: "2026-02-25",
                scheduledDate: "2026-03-04",
                scheduledTime: "9:30 AM",
                status: "completed",
                branch: "West Side Branch",
                price: "₱85",
                paymentStatus: "paid"
            },
            {
                id: "11",
                service: "Nail Polish Change",
                duration: "15 minutes",
                reservationDate: "2026-02-20",
                scheduledDate: "2026-02-26",
                scheduledTime: "2:00 PM",
                status: "completed",
                branch: "Main Branch",
                price: "₱25",
                paymentStatus: "paid"
            }
        ]
    }
];

let filteredCustomers = [...customers];
let pagination;

// Status badge colors
const statusColors = {
    'confirmed': 'status-confirmed',
    'completed': 'status-completed',
    'cancelled': 'status-cancelled',
    'rescheduled': 'status-rescheduled',
    'no-show': 'status-no-show'
};

document.addEventListener('DOMContentLoaded', function() {
    // Load customers after a short delay for loading effect
    setTimeout(loadCustomers, 800);
    
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
        totalItems: filteredCustomers.length,
        itemsPerPage: 5,
        currentPage: 1,
        onPageChange: function(page, itemsPerPage) {
            loadCustomers();
        }
    });
});

function loadCustomers() {
    const tableBody = document.getElementById('customersTableBody');
    tableBody.innerHTML = '';
    
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
            <td class="reservation-count">${customer.reservations.length}</td>
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
}

function searchCustomers(searchTerm) {
    const term = searchTerm.toLowerCase();
    filteredCustomers = customers.filter(customer =>
        customer.name.toLowerCase().includes(term) ||
        customer.email.toLowerCase().includes(term) ||
        customer.contact.toLowerCase().includes(term)
    );
    
    pagination.goToPage(1); // Reset to first page
    loadCustomers();
}

function filterCustomersByReservations(filterValue) {
    if (filterValue === 'all') {
        filteredCustomers = [...customers];
    } else if (filterValue === '1-5') {
        filteredCustomers = customers.filter(customer => customer.reservations.length >= 1 && customer.reservations.length <= 5);
    } else if (filterValue === '6-10') {
        filteredCustomers = customers.filter(customer => customer.reservations.length >= 6 && customer.reservations.length <= 10);
    } else if (filterValue === '11-20') {
        filteredCustomers = customers.filter(customer => customer.reservations.length >= 11 && customer.reservations.length <= 20);
    } else if (filterValue === '20+') {
        filteredCustomers = customers.filter(customer => customer.reservations.length > 20);
    }
    
    pagination.goToPage(1); // Reset to first page
    loadCustomers();
}

function viewCustomerReservations(customerId) {
    const customer = customers.find(c => c.id === customerId);
    if (!customer) return;
    
    // Populate customer details
    document.getElementById('modalCustomerName').textContent = `${customer.name}'s Reservations`;
    document.getElementById('modalCustomerFullName').textContent = customer.name;
    document.getElementById('modalCustomerContact').textContent = customer.contact;
    document.getElementById('modalCustomerEmail').textContent = customer.email;
    document.getElementById('modalTotalReservations').textContent = customer.reservations.length;
    
    // Populate reservations list
    const reservationsList = document.getElementById('reservationsList');
    const noReservationsDiv = document.getElementById('noReservations');
    
    if (customer.reservations.length === 0) {
        reservationsList.innerHTML = '';
        noReservationsDiv.style.display = 'block';
    } else {
        noReservationsDiv.style.display = 'none';
        reservationsList.innerHTML = '';
        
        customer.reservations.forEach(reservation => {
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
    
    // Show modal
    document.getElementById('customerReservationsModal').classList.add('show');
}

function showReservationDetails(reservation) {
    document.getElementById('modalReservationId').textContent = reservation.id;
    
    const detailsDiv = document.getElementById('reservationDetails');
    detailsDiv.innerHTML = `
        <div class="reservation-detail-group">
            <div class="reservation-detail-item">
                <span class="reservation-detail-label">Service:</span>
                <span class="reservation-detail-value">${reservation.service}</span>
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

