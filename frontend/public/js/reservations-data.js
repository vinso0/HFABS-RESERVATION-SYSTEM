// dummy data 
const reservationsData = [
    {
        reservation_id: 1,
        user_id: 2,
        customer_name: 'Maria Santos', // From JOIN with users table
        service: 'Swedish Massage', // From JOIN with services via branch_services
        reservation_date: '2026-01-15',
        start_time: '10:00:00',
        end_time: '11:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 2,
        user_id: 3,
        customer_name: 'Juan dela Cruz',
        service: 'Hot Stone Therapy',
        reservation_date: '2026-01-15',
        start_time: '14:00:00',
        end_time: '15:30:00',
        status: 'pending'
    },
    {
        reservation_id: 3,
        user_id: 4,
        customer_name: 'Anna Reyes',
        service: 'Deep Cleansing Facial',
        reservation_date: '2026-01-16',
        start_time: '11:00:00',
        end_time: '12:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 4,
        user_id: 5,
        customer_name: 'Pedro Garcia',
        service: 'Hair Spa Treatment',
        reservation_date: '2026-01-16',
        start_time: '15:00:00',
        end_time: '15:30:00',
        status: 'pending'
    },
    {
        reservation_id: 5,
        user_id: 6,
        customer_name: 'Sofia Torres',
        service: 'Aromatherapy Massage',
        reservation_date: '2026-01-17',
        start_time: '09:00:00',
        end_time: '10:30:00',
        status: 'cancelled'
    },
    {
        reservation_id: 6,
        user_id: 7,
        customer_name: 'Carlos Mendoza',
        service: 'Deep Cleansing Facial',
        reservation_date: '2026-01-17',
        start_time: '13:00:00',
        end_time: '14:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 7,
        user_id: 8,
        customer_name: 'Isabella Cruz',
        service: 'Hair Spa Treatment',
        reservation_date: '2026-01-18',
        start_time: '10:30:00',
        end_time: '11:00:00',
        status: 'completed'
    },
    {
        reservation_id: 8,
        user_id: 9,
        customer_name: 'Miguel Ramos',
        service: 'Hair Rebonding',
        reservation_date: '2026-01-18',
        start_time: '14:30:00',
        end_time: '15:30:00',
        status: 'rescheduled'
    },
    {
        reservation_id: 9,
        user_id: 10,
        customer_name: 'Lucia Fernandez',
        service: 'Classic Manicure',
        reservation_date: '2026-01-19',
        start_time: '11:00:00',
        end_time: '12:00:00',
        status: 'confirmed'
    },
    {
        reservation_id: 10,
        user_id: 11,
        customer_name: 'Ricardo Aquino',
        service: 'Gel Pedicure',
        reservation_date: '2026-01-19',
        start_time: '16:00:00',
        end_time: '17:00:00',
        status: 'pending'
    },
    {
        reservation_id: 11,
        user_id: 12,
        customer_name: 'Elena Bautista',
        service: 'Anti-Aging Facial',
        reservation_date: '2026-01-20',
        start_time: '09:30:00',
        end_time: '10:30:00',
        status: 'confirmed'
    },
    {
        reservation_id: 12,
        user_id: 13,
        customer_name: 'Diego Castillo',
        service: 'Keratin Treatment',
        reservation_date: '2026-01-20',
        start_time: '13:30:00',
        end_time: '16:30:00',
        status: 'completed'
    }
];

// Updated function to format time for display
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Updated populate table function
function populateTable(data) {
    const tbody = document.getElementById('reservationsTableBody');
    tbody.innerHTML = '';

    data.forEach(reservation => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td class="reservation-id">RES-${String(reservation.reservation_id).padStart(3, '0')}</td>
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
