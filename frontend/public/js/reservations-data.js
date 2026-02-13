// Updated dummy data with email and contact_number
const reservationsData = [
    {
        reservation_id: 1,
        user_id: 2,
        customer_name: 'Maria Santos',
        email: 'maria.santos@email.com',
        contact_number: '+63 917 123 4567',
        service: 'Swedish Massage',
        category: 'Massage Therapy',
        price: 1500.00,
        duration: 90,
        reservation_date: '2026-01-15',
        start_time: '10:00:00',
        end_time: '11:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 2,
        user_id: 3,
        customer_name: 'Juan dela Cruz',
        email: 'juan.delacruz@email.com',
        contact_number: '+63 918 234 5678',
        service: 'Hot Stone Therapy',
        category: 'Massage Therapy',
        price: 2000.00,
        duration: 90,
        reservation_date: '2026-01-15',
        start_time: '14:00:00',
        end_time: '15:30:00',
        status: 'pending'
    },
    {
        reservation_id: 3,
        user_id: 4,
        customer_name: 'Anna Reyes',
        email: 'anna.reyes@email.com',
        contact_number: '+63 919 345 6789',
        service: 'Deep Cleansing Facial',
        category: 'Facial Treatment',
        price: 1200.00,
        duration: 90,
        reservation_date: '2026-01-16',
        start_time: '11:00:00',
        end_time: '12:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 4,
        user_id: 5,
        customer_name: 'Pedro Garcia',
        email: 'pedro.garcia@email.com',
        contact_number: '+63 920 456 7890',
        service: 'Hair Spa Treatment',
        category: 'Hair Treatment',
        price: 1800.00,
        duration: 30,
        reservation_date: '2026-01-16',
        start_time: '15:00:00',
        end_time: '15:30:00',
        status: 'pending'
    },
    {
        reservation_id: 5,
        user_id: 6,
        customer_name: 'Sofia Torres',
        email: 'sofia.torres@email.com',
        contact_number: '+63 921 567 8901',
        service: 'Aromatherapy Massage',
        category: 'Massage Therapy',
        price: 1600.00,
        duration: 90,
        reservation_date: '2026-01-17',
        start_time: '09:00:00',
        end_time: '10:30:00',
        status: 'cancelled'
    },
    {
        reservation_id: 6,
        user_id: 7,
        customer_name: 'Carlos Mendoza',
        email: 'carlos.mendoza@email.com',
        contact_number: '+63 922 678 9012',
        service: 'Deep Cleansing Facial',
        category: 'Facial Treatment',
        price: 1200.00,
        duration: 90,
        reservation_date: '2026-01-17',
        start_time: '13:00:00',
        end_time: '14:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 7,
        user_id: 8,
        customer_name: 'Isabella Cruz',
        email: 'isabella.cruz@email.com',
        contact_number: '+63 923 789 0123',
        service: 'Hair Spa Treatment',
        category: 'Hair Treatment',
        price: 1800.00,
        duration: 30,
        reservation_date: '2026-01-18',
        start_time: '10:30:00',
        end_time: '11:00:00',
        status: 'completed'
    },
    {
        reservation_id: 8,
        user_id: 9,
        customer_name: 'Miguel Ramos',
        email: 'miguel.ramos@email.com',
        contact_number: '+63 924 890 1234',
        service: 'Hair Rebonding',
        category: 'Hair Treatment',
        price: 3500.00,
        duration: 60,
        reservation_date: '2026-01-18',
        start_time: '14:30:00',
        end_time: '15:30:00',
        status: 'rescheduled'
    },
    {
        reservation_id: 9,
        user_id: 10,
        customer_name: 'Lucia Fernandez',
        email: 'lucia.fernandez@email.com',
        contact_number: '+63 925 901 2345',
        service: 'Classic Manicure',
        category: 'Nail Care',
        price: 350.00,
        duration: 60,
        reservation_date: '2026-01-19',
        start_time: '11:00:00',
        end_time: '12:00:00',
        status: 'confirmed'
    },
    {
        reservation_id: 10,
        user_id: 11,
        customer_name: 'Ricardo Aquino',
        email: 'ricardo.aquino@email.com',
        contact_number: '+63 926 012 3456',
        service: 'Gel Pedicure',
        category: 'Nail Care',
        price: 600.00,
        duration: 60,
        reservation_date: '2026-01-19',
        start_time: '16:00:00',
        end_time: '17:00:00',
        status: 'pending'
    },
    {
        reservation_id: 11,
        user_id: 12,
        customer_name: 'Elena Bautista',
        email: 'elena.bautista@email.com',
        contact_number: '+63 927 123 4567',
        service: 'Anti-Aging Facial',
        category: 'Facial Treatment',
        price: 2000.00,
        duration: 60,
        reservation_date: '2026-01-20',
        start_time: '09:30:00',
        end_time: '10:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 12,
        user_id: 13,
        customer_name: 'Diego Castillo',
        email: 'diego.castillo@email.com',
        contact_number: '+63 928 234 5678',
        service: 'Keratin Treatment',
        category: 'Hair Treatment',
        price: 4500.00,
        duration: 180,
        reservation_date: '2026-01-20',
        start_time: '13:30:00',
        end_time: '16:30:00',
        status: 'completed'
    }
];

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

// Function to populate table with pagination
function populateTableWithPagination(data) {
    const tbody = document.getElementById('reservationsTableBody');
    tbody.innerHTML = '';

    data.forEach(reservation => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td class="reservation-id">${reservation.reservation_id}</td>
            <td class="customer-name">${reservation.customer_name}</td>
            <td class="service-name">${reservation.service}</td>
            <td>
                <div class="date-time">
                    <span class="date">${reservation.reservation_date}</span>
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

// Function to view reservation - Opens modal
function viewReservation(reservationId) {
    const reservation = reservationsData.find(r => r.reservation_id === reservationId);
    
    if (!reservation) {
        alert('Reservation not found!');
        return;
    }

    // Populate modal with reservation data
    document.getElementById('modalService').textContent = reservation.service;
    document.getElementById('modalCategory').textContent = reservation.category;
    document.getElementById('modalDate').textContent = reservation.reservation_date;
    document.getElementById('modalTime').textContent = `${formatTime(reservation.start_time)} - ${formatTime(reservation.end_time)}`;
    document.getElementById('modalPrice').textContent = formatPrice(reservation.price);
    
    document.getElementById('modalCustomerName').textContent = reservation.customer_name;
    document.getElementById('modalEmail').textContent = reservation.email;
    document.getElementById('modalContact').textContent = reservation.contact_number;
    
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
function editReservation() {
    alert('Edit functionality will be implemented here');
    // TODO: Implement edit functionality
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
let currentFilteredData = reservationsData;

// Function to filter reservations with pagination
function filterReservationsWithPagination(status) {
    if (status === 'all') {
        currentFilteredData = reservationsData;
    } else {
        currentFilteredData = reservationsData.filter(res => res.status === status);
    }
    
    // Update pagination total
    reservationPagination.updateTotalItems(currentFilteredData.length);
    
    // Display current page
    displayCurrentPage();
}

// Function to display current page data
function displayCurrentPage() {
    const range = reservationPagination.getCurrentPageRange();
    const pageData = currentFilteredData.slice(range.start, range.end);
    populateTableWithPagination(pageData);
}

// Update DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination
    reservationPagination = new Pagination({
        totalItems: reservationsData.length,
        itemsPerPage: 10,
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
