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
function createReservationCard(reservation, index) {
    const servicesHtml = reservation.services.map(service =>
        `<span class="service-tag">${service.service_name}</span>`
    ).join('');

    const scheduleDate = reservation.schedule?.schedule_date || reservation.reservation_date;
    const startTime = reservation.schedule?.start_time ? formatTime(reservation.schedule.start_time) : '';
    const endTime = reservation.schedule?.end_time ? formatTime(reservation.schedule.end_time) : '';

    return `
        <div class="reservation-card">
            <div class="reservation-header">
                <div class="reservation-id">No. ${index + 1}</div>
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
    
    const reservationsHtml = reservations.map((reservation, index) =>
        createReservationCard(reservation, index)
    ).join('');
    
    container.innerHTML = reservationsHtml;
}

// Function to view reservation details (opens modal)
function viewReservationDetails(reservationId) {
    const reservation = window.currentReservations.find(r => r.reservation_id === reservationId);
    if (!reservation) { alert('Reservation not found!'); return; }

    const scheduleDate = reservation.schedule?.schedule_date || reservation.reservation_date;
    const startTime    = reservation.schedule?.start_time ? formatTime(reservation.schedule.start_time) : '';
    const endTime      = reservation.schedule?.end_time   ? formatTime(reservation.schedule.end_time)   : '';

    const servicesHtml = reservation.services.map(s => `
        <div class="detail-row">
            <span class="detail-row__label"><i class="fas fa-spa"></i> ${s.service_name}</span>
            <span class="detail-row__value">${s.duration_minutes ? s.duration_minutes + ' mins' : '—'}</span>
        </div>
    `).join('');

    document.getElementById('modalBody').innerHTML = `

        <!-- Section: Schedule Info -->
        <div class="modal-section">
            <div class="modal-section-title">
                <i class="fas fa-calendar-alt"></i> Schedule Info
            </div>
            <div class="detail-row">
                <span class="detail-row__label">Branch</span>
                <span class="detail-row__value">${reservation.branch_name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row__label">Date</span>
                <span class="detail-row__value">${scheduleDate}</span>
            </div>
            ${startTime ? `
            <div class="detail-row">
                <span class="detail-row__label">Time</span>
                <span class="detail-row__value">${startTime} – ${endTime}</span>
            </div>` : ''}
            <div class="detail-row">
                <span class="detail-row__label">Status</span>
                <span class="detail-row__value">
                    <span class="reservation-status ${getStatusBadgeClass(reservation.status)}">
                        ${capitalize(reservation.status)}
                    </span>
                </span>
            </div>
        </div>

        <!-- Section: Services -->
        <div class="modal-section">
            <div class="modal-section-title">
                <i class="fas fa-spa"></i> Services
            </div>
            ${servicesHtml}
        </div>

        <!-- Section: Payment -->
        <div class="modal-section">
            <div class="modal-section-title">
                <i class="fas fa-receipt"></i> Payment
            </div>
            <div class="detail-row">
                <span class="detail-row__label">Total</span>
                <span class="detail-row__value detail-row__value--price">${formatPrice(reservation.total_price)}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row__label">Remaining Balance</span>
                <span class="detail-row__value detail-row__value--balance">${formatPrice(reservation.total_remaining_balance)}</span>
            </div>
        </div>

    `;

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

    // NOW reservation exists, safe to use it:
    document.getElementById('reviewServiceId').value = reservation.services[0].reservation_service_id;
    document.getElementById('reviewReservationId').value = reservationId;
    document.getElementById('reviewBranchId').value = reservation.branch_id;

    // Reset modal for new review
    document.querySelector('#reviewModal .modal-title').textContent = 'Leave a Review';
    document.querySelector('#reviewModal button.btn-primary').textContent = 'Submit Review';
    document.querySelector('#reviewModal button.btn-primary').onclick = submitReview;
    document.getElementById('reviewComment').value = '';
    document.getElementById('reviewRating').value = '5';
    document.getElementById('star5').checked = true;

    document.getElementById('reviewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}


// Function to open edit review modal
function openEditReviewModal(reservationId) {
    closeViewReviewModal();

    const reservation = window.currentReservations.find(r => r.reservation_id === reservationId);
    if (!reservation) { alert('Reservation not found!'); return; }

    document.getElementById('reviewReservationId').value = reservationId;
    document.getElementById('reviewBranchId').value      = reservation.branch_id;

    // Set modal title and button to edit mode
    document.querySelector('#reviewModal .modal-title').textContent            = 'Update Review';
    document.querySelector('#reviewModal button.btn-primary').textContent      = 'Update Review';
    document.querySelector('#reviewModal button.btn-primary').onclick          = updateReview;

    // Reset new file selections
    selectedPhotoFiles   = [];
    window.existingPhotosToKeep = []; // track which existing photos user wants to keep

    // Populate rating & comment
    if (reservation.feedback && reservation.feedback.length > 0) {
        const feedback = reservation.feedback[0];
        document.getElementById('reviewComment').value = feedback.comment;
        document.getElementById('reviewRating').value  = feedback.rating;
        const starInput = document.getElementById(`star${feedback.rating}`);
        if (starInput) starInput.checked = true;

        // Render existing photos
        renderExistingPhotos(feedback.photos || []);
    }

    renderPhotoPreviews(); // reset new upload area
    document.getElementById('reviewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function renderExistingPhotos(photos) {
    let section = document.getElementById('existingPhotosSection');
    if (!section) {
        const uploadArea = document.getElementById('photoUploadArea');
        section = document.createElement('div');
        section.id        = 'existingPhotosSection';
        section.className = 'existing-photos-section';
        uploadArea.parentNode.insertBefore(section, uploadArea);
    }

    if (!photos || photos.length === 0) {
        section.innerHTML = '';
        window.existingPhotosToKeep = [];
        return;
    }

    window.existingPhotosToKeep = photos.map(p => p.photo_path);
    const photoUrls = photos.map(p => `/HFABS/backend/public/${p.photo_path}`);

    section.innerHTML = `
        <div class="existing-photos-label">Existing Photos (click × to remove)</div>
        <div id="existingPhotosList" style="display:flex;flex-wrap:wrap;gap:6px;"
             data-photos='${JSON.stringify(photoUrls).replace(/'/g, "&#39;")}'>
            ${photos.map((p, idx) =>
                `<div class="existing-photo-item" id="existingPhoto_${idx}" data-path="${p.photo_path}">
                    <img src="/HFABS/backend/public/${p.photo_path}"
                         alt="Existing photo ${idx + 1}"
                         data-index="${idx}"
                         class="existing-preview-img"
                         title="Click to preview">
                    <button type="button"
                            class="existing-photo-remove"
                            data-idx="${idx}"
                            data-path="${p.photo_path}"
                            title="Remove photo">×</button>
                </div>`
            ).join('')}
        </div>
    `;

    // Attach preview clicks safely after DOM insert
    const listEl = section.querySelector('#existingPhotosList');
    const urls   = JSON.parse(listEl.dataset.photos);

    listEl.querySelectorAll('img.existing-preview-img').forEach(img => {
        img.addEventListener('click', function () {
            openPhotoLightbox(urls, parseInt(this.dataset.index));
        });
    });

    // Attach remove clicks safely
    listEl.querySelectorAll('.existing-photo-remove').forEach(btn => {
        btn.addEventListener('click', function () {
            const idx  = parseInt(this.dataset.idx);
            const path = this.dataset.path;
            removeExistingPhoto(idx, path);
        });
    });
}

function removeExistingPhoto(index, photoPath) {
    // Remove from keep list
    window.existingPhotosToKeep = (window.existingPhotosToKeep || []).filter(p => p !== photoPath);
    // Remove from DOM
    const el = document.getElementById(`existingPhoto_${index}`);
    if (el) el.remove();
}

window.removeExistingPhoto   = removeExistingPhoto;

// ─── Photo Upload Setup ──────────────────────────────────────────────
const MAX_PHOTOS = 5;
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
let selectedPhotoFiles = [];

function setupPhotoUpload() {
    const input = document.getElementById('reviewPhotos');
    const uploadArea = document.getElementById('photoUploadArea');

    if (!input) return;

    input.addEventListener('change', function () {
        handlePhotoSelection(Array.from(this.files));
        this.value = ''; // Reset so same file can be re-selected
    });

    // Drag-and-drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        handlePhotoSelection(Array.from(e.dataTransfer.files));
    });
}

function handlePhotoSelection(files) {
    const errors = [];

    // Count existing kept photos + already selected new photos combined
    const existingCount = (window.existingPhotosToKeep || []).length;

    files.forEach(file => {
        const totalUsed = existingCount + selectedPhotoFiles.length;

        if (totalUsed >= MAX_PHOTOS) {
            errors.push(`Maximum ${MAX_PHOTOS} photos allowed (including existing photos).`);
            return;
        }
        if (!ALLOWED_TYPES.includes(file.type)) {
            errors.push(`"${file.name}" is not a valid image (JPG, PNG, WEBP only).`);
            return;
        }
        if (file.size > MAX_FILE_SIZE) {
            errors.push(`"${file.name}" exceeds the 5MB size limit.`);
            return;
        }
        selectedPhotoFiles.push(file);
    });

    if (errors.length > 0) {
        // Use showToast if available, fallback to Toast
        if (typeof Toast !== 'undefined') {
            Toast.error(errors[0]);
        } else {
            showToast(errors[0], 'error');
        }
    }

    renderPhotoPreviews();
}

function renderPhotoPreviews() {
    const container = document.getElementById('photoPreviewContainer');
    const countText = document.getElementById('photoCountText');

    if (!container) return;

    container.innerHTML = '';

    selectedPhotoFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'photo-preview-item';
            wrapper.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${index + 1}">
                <button type="button" class="photo-remove-btn" onclick="removePhoto(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });

    // Total used = existing kept + new selections
    const existingCount = (window.existingPhotosToKeep || []).length;
    const totalUsed     = existingCount + selectedPhotoFiles.length;
    const remaining     = MAX_PHOTOS - totalUsed;

    if (selectedPhotoFiles.length > 0 || existingCount > 0) {
        countText.textContent = `${totalUsed} of ${MAX_PHOTOS} photos used (${remaining} more allowed)`;
    } else {
        countText.textContent = '';
    }
}

function removePhoto(index) {
    selectedPhotoFiles.splice(index, 1);
    renderPhotoPreviews();
}

// ─── Lightbox ────────────────────────────────────────────────────────────
let lightboxPhotos = [];
let lightboxIndex  = 0;

function openPhotoLightbox(photos, startIndex) {
    lightboxPhotos = photos;
    lightboxIndex  = startIndex || 0;
    renderLightboxSlide();
    document.getElementById('photoLightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function renderLightboxSlide() {
    const img     = document.getElementById('lightboxImage');
    const counter = document.getElementById('lightboxCounter');
    const prev    = document.getElementById('lightboxPrev');
    const next    = document.getElementById('lightboxNext');

    img.src         = lightboxPhotos[lightboxIndex];
    counter.textContent = `${lightboxIndex + 1} / ${lightboxPhotos.length}`;
    prev.disabled   = lightboxIndex === 0;
    next.disabled   = lightboxIndex === lightboxPhotos.length - 1;
}

function lightboxNavigate(dir) {
    lightboxIndex = Math.max(0, Math.min(lightboxPhotos.length - 1, lightboxIndex + dir));
    renderLightboxSlide();
}

function closePhotoLightbox() {
    document.getElementById('photoLightbox').classList.remove('active');
    document.body.style.overflow = '';
    lightboxPhotos = [];
    lightboxIndex  = 0;
}

// Keyboard navigation for lightbox
document.addEventListener('keydown', function(e) {
    const lb = document.getElementById('photoLightbox');
    if (!lb || !lb.classList.contains('active')) return;
    if (e.key === 'ArrowLeft')  lightboxNavigate(-1);
    if (e.key === 'ArrowRight') lightboxNavigate(1);
    if (e.key === 'Escape')     closePhotoLightbox();
});

window.openPhotoLightbox  = openPhotoLightbox;
window.closePhotoLightbox = closePhotoLightbox;
window.lightboxNavigate   = lightboxNavigate;

// Function to submit review
async function submitReview() {
    const reservationServiceId = document.getElementById('reviewServiceId')?.value;
    const branchId             = document.getElementById('reviewBranchId')?.value;
    const rating               = document.getElementById('reviewRating')?.value;
    const comment              = document.getElementById('reviewComment')?.value?.trim();

    if (!comment) {
        showToast('Please write a comment before submitting.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('reservation_service_id', reservationServiceId);
    formData.append('branch_id', branchId);
    formData.append('rating', rating);
    formData.append('comment', comment);

    selectedPhotoFiles.forEach((file) => {
        formData.append('photos[]', file);
    });

    try {
        const response = await fetch('/HFABS/backend/public/index.php?url=feedback/submit', {
            method: 'POST',
            credentials: 'include',
            body: formData   // Do NOT set Content-Type header — browser sets multipart boundary
        });

        const result = await response.json();

        if (result.success) {
            showToast('Review submitted! It will appear after admin approval.', 'success');
            closeReviewModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message || 'Failed to submit review.', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    }
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('reviewComment').value = '';
    document.getElementById('reviewRating').value  = '5';
    selectedPhotoFiles = [];
    window.existingPhotosToKeep = [];
    renderPhotoPreviews();
    const star5 = document.getElementById('star5');
    if (star5) star5.checked = true;
    // Clear existing photos section
    const section = document.getElementById('existingPhotosSection');
    if (section) section.innerHTML = '';
    document.body.style.overflow = '';
}

// Function to update review
async function updateReview() {
    const reservationId = document.getElementById('reviewReservationId').value;
    const branchId      = document.getElementById('reviewBranchId').value;
    const rating        = document.getElementById('reviewRating').value;
    const comment       = document.getElementById('reviewComment').value.trim();

    if (!rating || !comment) {
        Toast.warning('Please provide both a rating and a comment.');
        return;
    }

    const reservation = window.currentReservations.find(r => r.reservation_id === parseInt(reservationId));
    if (!reservation || reservation.services.length === 0) {
        Toast.error('No services found for this reservation.');
        return;
    }

    const reservationServiceId = reservation.services[0].reservation_service_id;

    const formData = new FormData();
    formData.append('reservation_service_id', reservationServiceId);
    formData.append('branch_id', branchId);
    formData.append('rating', rating);
    formData.append('comment', comment);

    // Tell backend which existing photos to preserve
    const keepPhotos = window.existingPhotosToKeep || [];
    keepPhotos.forEach(path => {
        formData.append('keep_photos[]', path);
    });

    // Append any newly selected files
    selectedPhotoFiles.forEach(file => {
        formData.append('photos[]', file);
    });

    try {
        const response = await fetch('/HFABS/backend/public/index.php?url=feedback/submit', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            Toast.success('Review updated successfully!');
            closeReviewModal();
            window.existingPhotosToKeep = [];
            setTimeout(() => location.reload(), 1500);
        } else {
            Toast.error(result.message || 'Failed to update review.');
        }
    } catch (error) {
        console.error('Error updating review:', error);
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
    if (!reservation) { alert('Reservation not found!'); return; }

    const reviewBody = document.getElementById('viewReviewBody');

    if (reservation.feedback && reservation.feedback.length > 0) {
        const commentsHtml = reservation.feedback.map(feedback => {
            const photoUrls = (feedback.photos && feedback.photos.length > 0)
                ? feedback.photos.map(p => `/HFABS/backend/public/${p.photo_path}`)
                : [];

            // Safe: use data-index + data-photos-json on a wrapper, NOT inside onclick
            const photosHtml = photoUrls.length > 0
                ? `<div class="review-photos"
                        data-photos='${JSON.stringify(photoUrls).replace(/'/g, "&#39;")}'
                        style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;">
                        ${photoUrls.map((url, idx) =>
                            `<img src="${url}"
                                  alt="Review photo ${idx + 1}"
                                  class="review-photo-thumb"
                                  data-index="${idx}"
                                  title="Click to enlarge">`
                        ).join('')}
                   </div>`
                : '';

            return `
                <div class="review-comment">
                    <div class="review-rating">${'★'.repeat(feedback.rating)}${'☆'.repeat(5 - feedback.rating)}</div>
                    <div class="review-text">${feedback.comment}</div>
                    ${photosHtml}
                    <div class="review-date">${new Date(feedback.created_at).toLocaleDateString()}</div>
                </div>
            `;
        }).join('');

        reviewBody.innerHTML = `
            <div class="review-comments">${commentsHtml}</div>
            <div class="review-actions">
                <button class="action-btn btn-primary" onclick="openEditReviewModal(${reservation.reservation_id})">
                    <i class="fas fa-edit"></i> Update Review
                </button>
            </div>
        `;

        // Attach click listeners AFTER innerHTML is set (safe, no inline JSON)
        reviewBody.querySelectorAll('.review-photos').forEach(photoWrapper => {
            const urls = JSON.parse(photoWrapper.dataset.photos);
            photoWrapper.querySelectorAll('img.review-photo-thumb').forEach(img => {
                img.addEventListener('click', function () {
                    openPhotoLightbox(urls, parseInt(this.dataset.index));
                });
            });
        });

    } else {
        reviewBody.innerHTML = `
            <div class="no-review">
                <i class="fas fa-star"></i>
                <p>No review has been submitted for this reservation.</p>
            </div>
        `;
    }

    document.getElementById('viewReviewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to close view review modal
function closeViewReviewModal() {
    document.getElementById('viewReviewModal').classList.remove('active');
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
    
    //Call setupPhotoUpload when DOM is ready
    setupPhotoUpload();
    
    // Add filter event listener
    const filterSelect = document.getElementById('filterStatus');
    filterSelect.addEventListener('change', function() {
        filterReservationsWithPagination(this.value);
    });
});
