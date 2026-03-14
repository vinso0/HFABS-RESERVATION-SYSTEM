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
                    <button class="action-btn btn-primary" onclick="openRescheduleModal(${reservation.reservation_id})">
                        <i class="fas fa-calendar"></i>
                        Reschedule
                    </button>
                    <button class="action-btn btn-cancel" onclick="cancelReservation(${reservation.reservation_id})">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                ` : ''}

                ${reservation.status === 'rescheduled' ? `
                    <button class="action-btn btn-primary" onclick="openRescheduleModal(${reservation.reservation_id})">
                        <i class="fas fa-calendar"></i>
                        Reschedule
                    </button>
                    <button class="action-btn btn-cancel" onclick="cancelReservation(${reservation.reservation_id})">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                ` : ''}
                
                ${reservation.status === 'completed' ? (reservation.feedback && reservation.feedback.length > 0 ? `
                    <button class="action-btn btn-primary" onclick="openViewReviewModal(${reservation.reservation_id})">
                        <i class="fas fa-eye"></i>
                        View Review
                    </button>
                ` : `
                    <button class="action-btn btn-primary" onclick="openReviewModal(${reservation.reservation_id})">
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
                <span class="modal-detail-label">Schedule Date</span>
                <span class="modal-detail-value">${scheduleDate}</span>
            </div>
            <div class="modal-detail">
                <span class="modal-detail-label">Reservation Date</span>
                <span class="modal-detail-value">${reservation.reservation_date}</span>
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
  const confirmed = await Toast.confirm(
    'Are you sure you want to cancel this reservation? This cannot be undone.',
    { confirmText: 'Yes, Cancel It', cancelText: 'Keep It', type: 'error' }
  );
  if (!confirmed) return;

  try {
    const response = await fetch('/HFABS/backend/public/index.php?url=reservation/cancelReservation', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ reservation_id: reservationId })
    });
    const result = await response.json();

    if (result.success) {
      Toast.success('Reservation cancelled successfully!');
      setTimeout(() => location.reload(), 1500);
    } else {
      Toast.error('Failed to cancel reservation: ' + result.message);
    }
  } catch (error) {
    console.error('Error cancelling reservation:', error);
    Toast.error('Failed to cancel reservation. Please try again.');
  }
}

// Calendar and Time Picker Logic
let currentDate = new Date();
let selectedDate = null;
let selectedTime = null;
let availableTimeSlots = [];

// Initialize calendar when reschedule modal opens
function initializeCalendar() {
    renderCalendar();
    renderTimeSlots();
    
    // Remove existing event listeners first to prevent duplicates
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    
    // Remove all existing event listeners
    const newPrevBtn = prevMonthBtn.cloneNode(true);
    const newNextBtn = nextMonthBtn.cloneNode(true);
    prevMonthBtn.parentNode.replaceChild(newPrevBtn, prevMonthBtn);
    nextMonthBtn.parentNode.replaceChild(newNextBtn, nextMonthBtn);
    
    // Add new event listeners
    newPrevBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });
    
    newNextBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });
}

// Render calendar
function renderCalendar() {
    const calendarDays = document.getElementById('calendarDays');
    const currentMonth = document.getElementById('currentMonth');
    const today = new Date();
    const minDate = new Date();
    
    // Set month and year header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
    currentMonth.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    
    // Get first and last days of the month
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const prevLastDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0);
    
    const firstDayOfWeek = firstDay.getDay(); // 0 = Sunday
    const lastDate = lastDay.getDate();
    const prevLastDate = prevLastDay.getDate();
    
    let daysHTML = '';
    
    // Previous month days
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
        const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, prevLastDate - i);
        daysHTML += `<button class="calendar-day disabled" data-date="${formatDate(dayDate)}">${prevLastDate - i}</button>`;
    }
    
    // Current month days
    for (let day = 1; day <= lastDate; day++) {
        const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
        const dateString = formatDate(dayDate);
        const isToday = dayDate.toDateString() === today.toDateString();
        const isBeforeMinDate = dayDate < minDate.setHours(0, 0, 0, 0);
        const isUnavailable = checkDateAvailability(dayDate);
        const isFullyBooked = checkDateFullyBooked(dayDate);
        
        let classes = 'calendar-day';
        if (isBeforeMinDate) classes += ' disabled';
        if (isUnavailable) classes += ' unavailable';
        if (isFullyBooked) classes += ' fully-booked';
        if (isToday) classes += ' today';
        if (selectedDate === dateString) classes += ' selected';
        
        daysHTML += `<button class="${classes}" data-date="${dateString}" ${(isBeforeMinDate || isUnavailable || isFullyBooked) ? 'disabled' : ''}>${day}</button>`;
    }
    
    // Next month days
    const remainingDays = 42 - (firstDayOfWeek + lastDate); // 6 weeks calendar
    for (let day = 1; day <= remainingDays; day++) {
        const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, day);
        daysHTML += `<button class="calendar-day disabled" data-date="${formatDate(dayDate)}">${day}</button>`;
    }
    
    calendarDays.innerHTML = daysHTML;
    
    // Add click event listeners to calendar days
    document.querySelectorAll('#calendarDays .calendar-day:not(.disabled):not(.unavailable):not(.fully-booked)').forEach(button => {
        button.addEventListener('click', function() {
            selectedDate = this.dataset.date;
            selectedTime = null;
            renderCalendar();
            renderTimeSlots();
            updateSelectedDateDisplay();
            updateSelectedTimeDisplay();
        });
    });
}

// Render time slots
function renderTimeSlots() {
    const timeSlotsContainer = document.getElementById('timeSlots');
    const timeSlots = generateTimeSlots();
    
    let slotsHTML = '';
    
    timeSlots.forEach(time => {
        const isSelected = selectedTime === time;
        const isAvailable = checkTimeAvailability(selectedDate, time);
        const isFullyBooked = checkTimeFullyBooked(selectedDate, time);
        
        let classes = 'time-slot';
        if (isSelected) classes += ' selected';
        if (!isAvailable) classes += ' unavailable';
        if (isFullyBooked) classes += ' fully-booked';
        if (!selectedDate) classes += ' disabled';
        
        slotsHTML += `<button class="${classes}" data-time="${time}" ${(!selectedDate || !isAvailable || isFullyBooked) ? 'disabled' : ''}>${formatTimeForDisplay(time)}</button>`;
    });
    
    timeSlotsContainer.innerHTML = slotsHTML;
    
    // Add click event listeners to time slots
    document.querySelectorAll('#timeSlots .time-slot:not(.disabled):not(.unavailable):not(.fully-booked)').forEach(button => {
        button.addEventListener('click', function() {
            selectedTime = this.dataset.time;
            renderTimeSlots();
            updateSelectedTimeDisplay();
        });
    });
}

// Generate time slots (9 AM to 7 PM, 30-minute intervals)
function generateTimeSlots() {
    const slots = [];
    const startTime = 9; // 9 AM
    const endTime = 19; // 7 PM
    const interval = 30; // minutes
    
    for (let hour = startTime; hour < endTime; hour++) {
        slots.push(`${String(hour).padStart(2, '0')}:00`);
        if (hour < endTime - 1) {
            slots.push(`${String(hour).padStart(2, '0')}:30`);
        }
    }
    
    return slots;
}

// Format date as YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Format time for display (12-hour format)
function formatTimeForDisplay(timeString) {
    if (!timeString) return '';
    
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Update selected date display
function updateSelectedDateDisplay() {
    const display = document.getElementById('selectedDateDisplay');
    if (selectedDate) {
        const date = new Date(selectedDate);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        display.innerHTML = `Selected: <span class="selected-date">${date.toLocaleDateString('en-US', options)}</span>`;
    } else {
        display.innerHTML = 'Please select a date';
    }
}

// Update selected time display
function updateSelectedTimeDisplay() {
    const display = document.getElementById('selectedTimeDisplay');
    if (selectedTime) {
        display.innerHTML = `Selected: <span class="selected-time">${formatTimeForDisplay(selectedTime)}</span>`;
    } else {
        display.innerHTML = selectedDate ? 'Please select a time' : 'Select a date first';
    }
}

// Check date availability (placeholder for future implementation)
function checkDateAvailability(date) {
    // TODO: Implement actual date availability checking
    return false;
}

// Check if date is fully booked (placeholder for future implementation)
function checkDateFullyBooked(date) {
    // TODO: Implement actual fully booked checking
    return false;
}

// Check time availability (placeholder for future implementation)
function checkTimeAvailability(date, time) {
    // TODO: Implement actual time availability checking
    return true;
}

// Check if time slot is fully booked (placeholder for future implementation)
function checkTimeFullyBooked(date, time) {
    // TODO: Implement actual fully booked checking
    return false;
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
    
    // Reset selected date and time
    selectedDate = null;
    selectedTime = null;
    currentDate = new Date();
    
    // Initialize calendar
    initializeCalendar();
    updateSelectedDateDisplay();
    updateSelectedTimeDisplay();
    
    // Show modal
    document.getElementById('rescheduleModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to reschedule reservation
async function rescheduleReservation() {
  const reservationId = document.getElementById('rescheduleReservationId').value;
  const newDate = selectedDate;
  const newTime = selectedTime;
  const reason = document.getElementById('rescheduleReason').value;

  if (!newDate || !newTime) {
    Toast.warning('Please select both a date and a time slot.');
    return;
  }

  try {
    const response = await fetch('/HFABS/backend/public/index.php?url=reservation/rescheduleReservation', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ reservation_id: reservationId, new_date: newDate, new_time: newTime, reason })
    });
    const result = await response.json();

    if (result.success) {
      Toast.success('Reservation rescheduled successfully!');
      closeRescheduleModal();
      setTimeout(() => location.reload(), 1500);
    } else {
      Toast.error('Failed to reschedule: ' + result.message);
    }
  } catch (error) {
    console.error('Error rescheduling reservation:', error);
    Toast.error('Failed to reschedule reservation. Please try again.');
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
    
    // Reset modal for new review
    document.querySelector('#reviewModal .modal-title').textContent = 'Leave a Review';
    document.querySelector('#reviewModal button.btn-primary').textContent = 'Submit Review';
    document.querySelector('#reviewModal button.btn-primary').onclick = submitReview;
    document.getElementById('reviewComment').value = '';
    document.getElementById('reviewRating').value = '5';
    document.getElementById('star5').checked = true;
    
    // Show modal
    document.getElementById('reviewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to open edit review modal
function openEditReviewModal(reservationId) {
    // Close the view review modal first
    closeViewReviewModal();
    
    const reservation = window.currentReservations.find(r => r.reservation_id === reservationId);
    if (!reservation) {
        alert('Reservation not found!');
        return;
    }

    // Set reservation data in modal
    document.getElementById('reviewReservationId').value = reservationId;
    document.getElementById('reviewBranchId').value = reservation.branch_id;
    
    // Set modal for edit mode
    document.querySelector('#reviewModal .modal-title').textContent = 'Update Review';
    document.querySelector('#reviewModal button.btn-primary').textContent = 'Update Review';
    document.querySelector('#reviewModal button.btn-primary').onclick = updateReview;
    
    // Populate existing review data
    if (reservation.feedback && reservation.feedback.length > 0) {
        // Use first feedback entry (assuming all services have same review)
        const feedback = reservation.feedback[0];
        document.getElementById('reviewComment').value = feedback.comment;
        document.getElementById('reviewRating').value = feedback.rating;
        // Check the corresponding star radio button
        document.getElementById(`star${feedback.rating}`).checked = true;
    }
    
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

  if (!rating || !comment.trim()) {
    Toast.warning('Please provide both a rating and a comment.');
    return;
  }

  const reservation = window.currentReservations.find(r => r.reservation_id === parseInt(reservationId));
  if (!reservation || reservation.services.length === 0) {
    Toast.error('No services found for this reservation.');
    return;
  }

  try {
    for (const service of reservation.services) {
      const response = await fetch('/HFABS/backend/public/index.php?url=reservation/submitFeedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ reservation_service_id: service.reservation_service_id, branch_id: branchId, rating, comment })
      });
      const result = await response.json();
      if (!result.success) {
        Toast.error('Failed to submit review: ' + result.message);
        return;
      }
    }
    Toast.success('Review submitted successfully! Thank you for your feedback. 🌟');
    closeReviewModal();
    setTimeout(() => location.reload(), 1800);
  } catch (error) {
    console.error('Error submitting feedback:', error);
    Toast.error('Failed to submit review. Please try again.');
  }
}

// Function to update review
async function updateReview() {
  const reservationId = document.getElementById('reviewReservationId').value;
  const branchId = document.getElementById('reviewBranchId').value;
  const rating = document.getElementById('reviewRating').value;
  const comment = document.getElementById('reviewComment').value;

  if (!rating || !comment.trim()) {
    Toast.warning('Please provide both a rating and a comment.');
    return;
  }

  const reservation = window.currentReservations.find(r => r.reservation_id === parseInt(reservationId));
  if (!reservation || reservation.services.length === 0) {
    Toast.error('No services found for this reservation.');
    return;
  }

  try {
    for (const service of reservation.services) {
      const response = await fetch('/HFABS/backend/public/index.php?url=reservation/submitFeedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ reservation_service_id: service.reservation_service_id, branch_id: branchId, rating, comment })
      });
      const result = await response.json();
      if (!result.success) {
        Toast.error('Failed to update review: ' + result.message);
        return;
      }
    }
    Toast.success('Review updated successfully!');
    closeReviewModal();
    setTimeout(() => location.reload(), 1800);
  } catch (error) {
    console.error('Error updating feedback:', error);
    Toast.error('Failed to update review. Please try again.');
  }
}

// Make updateReview globally accessible
window.updateReview = updateReview;
window.openEditReviewModal = openEditReviewModal;

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
            <div class="review-comments">
                ${commentsHtml}
            </div>
            <div class="review-actions">
                <button class="action-btn btn-primary" onclick="openEditReviewModal(${reservation.reservation_id})">
                    <i class="fas fa-edit"></i>
                    Update Review
                </button>
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
