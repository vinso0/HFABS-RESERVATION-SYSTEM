// API Configuration
const API_BASE_URL = '/api';

// State Management
let selectedService = null;
let selectedBranch = null;
let selectedDate = null;
let selectedTime = null;
let currentMonth = new Date();
let availableTimeSlots = [];
let branchOpeningTime = null;
let branchClosingTime = null;
let branchClosedDates = new Set();
let branchBlockedDays = new Set();  // 0=Sun..6=Sat

// Check if user is logged in
function isLoggedIn() {
const token = localStorage.getItem('token');
const userData = localStorage.getItem('userData');
return !!(token && userData);
}

// Initialize page
document.addEventListener('DOMContentLoaded', async function() {
  // Check if user is logged in
  if (!isLoggedIn()) {
    alert('Please log in first to access the booking page.');
    window.location.href = './customer-login.html';
    return;
  }

  await loadBookingData();
  initializeCalendar();
  setupEventListeners();
});

async function loadBookingData() {
  // Load selected service from sessionStorage
  const servicesData = sessionStorage.getItem('selectedServices');
  const branchId = sessionStorage.getItem('selectedBranchId');
  const branchName = sessionStorage.getItem('selectedBranchName');
  
  if (!servicesData) {
    alert('No service selected. Redirecting to services page.');
    window.location.href = './customer-home.php';
    return;
  }
  
  if (!branchId) {
    alert('No branch selected. Redirecting to branch page.');
    window.location.href = './customer-home.php';
    return;
  }

  const services = JSON.parse(servicesData);
  selectedService = services[0]; // Only one service now
  selectedBranch = { id: branchId, name: branchName };

  await loadBranchHours(branchId);
  await loadClosedDates(branchId);
  await loadBlockedDays(branchId);

  displayServiceInfo();
  renderCalendar();
}

function displayServiceInfo() {
  const price = parseFloat(selectedService.price);

  // ✅ Read dynamic rate from sessionStorage (set by services.js)
  const rate = parseFloat(sessionStorage.getItem('branchDownpaymentRate') || '0.5');
  const ratePercent = Math.round(rate * 100);
  const downpayment = price * rate;

  const label = selectedService.is_package ? 'Book Package:' : 'Book:';
  document.getElementById('serviceName').textContent = `${label} ${selectedService.servicename}`;
  document.getElementById('servicePrice').textContent = `₱${price.toFixed(2)}`;
  document.getElementById('serviceDuration').textContent = `Duration: ${selectedService.duration || 'N/A'}`;
  document.getElementById('serviceDownpayment').textContent = `Downpayment (${ratePercent}%): ₱${downpayment.toFixed(2)}`;

  // Booking hours from branch settings
  branchOpeningTime = sessionStorage.getItem('branchOpeningTime') || '09:00:00';
  branchClosingTime = sessionStorage.getItem('branchClosingTime') || '18:00:00';

  const formattedOpening = formatBranchTime(branchOpeningTime);
  const formattedClosing = formatBranchTime(branchClosingTime);
  document.getElementById('serviceHours').textContent = `Hours: ${formattedOpening} - ${formattedClosing}`;
}

async function loadBranchHours(branchId) {
  try {
    const response = await fetch(`/HFABS/backend/public/index.php?url=branch/${branchId}`);
    if (!response.ok) throw new Error('Failed to fetch branch hours');
    const branch = await response.json();

    if (branch.opening_time) {
      branchOpeningTime = branch.opening_time;
      sessionStorage.setItem('branchOpeningTime', branchOpeningTime);
    }
    if (branch.closing_time) {
      branchClosingTime = branch.closing_time;
      sessionStorage.setItem('branchClosingTime', branchClosingTime);
    }

    if (branch.down_payment_rate !== undefined && branch.down_payment_rate !== null) {
      sessionStorage.setItem('branchDownpaymentRate', branch.down_payment_rate);
    }
  } catch (error) {
    console.error('Error loading branch hours:', error);
    branchOpeningTime = sessionStorage.getItem('branchOpeningTime') || '09:00:00';
    branchClosingTime = sessionStorage.getItem('branchClosingTime') || '18:00:00';
  }
}

async function loadClosedDates(branchId) {
  try {
    const response = await fetch(`/HFABS/backend/public/index.php?url=branch/closedDatesPublic&branch_id=${branchId}`);
    if (!response.ok) throw new Error('Failed to load closed dates');
    const result = await response.json();
    branchClosedDates = new Set((result.data || []).map(d => d));
  } catch (error) {
    console.error('Error loading closed dates:', error);
    branchClosedDates = new Set();
  }
}

async function loadBlockedDays(branchId) {
  try {
    const response = await fetch(`/HFABS/backend/public/index.php?url=branch/blockedDaysPublic&branch_id=${branchId}`);
    if (!response.ok) throw new Error('Failed to load blocked days');
    const result = await response.json();
    branchBlockedDays = new Set((result.data || []).map(d => parseInt(d, 10)).filter(n => !Number.isNaN(n)));
  } catch (error) {
    console.error('Error loading blocked days:', error);
    branchBlockedDays = new Set();
  }
}

function setupEventListeners() {
  document.getElementById('prevMonth').addEventListener('click', () => {
    currentMonth.setMonth(currentMonth.getMonth() - 1);
    renderCalendar();
  });
  
  document.getElementById('nextMonth').addEventListener('click', () => {
    currentMonth.setMonth(currentMonth.getMonth() + 1);
    renderCalendar();
  });
  
  document.getElementById('proceedBtn').addEventListener('click', proceedToPayment);
}

// Calendar Functions
function initializeCalendar() {
  renderCalendar();
}

function renderCalendar() {
  const year = currentMonth.getFullYear();
  const month = currentMonth.getMonth();
  
  // Update month display
  const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                      'July', 'August', 'September', 'October', 'November', 'December'];
  document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
  
  // Get first day of month and number of days
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();
  
  const calendarGrid = document.getElementById('calendarGrid');
  calendarGrid.innerHTML = '';
  
  // Add day headers
  const dayHeaders = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
  dayHeaders.forEach(day => {
    const header = document.createElement('div');
    header.className = 'calendar-day-header';
    header.textContent = day;
    calendarGrid.appendChild(header);
  });
  
  // Add previous month days
  for (let i = firstDay - 1; i >= 0; i--) {
    const day = createCalendarDay(daysInPrevMonth - i, true, false);
    calendarGrid.appendChild(day);
  }
  
  // Add current month days
  const today = new Date();
  for (let i = 1; i <= daysInMonth; i++) {
    const date = new Date(year, month, i);
    const isPast = date < new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const isToday = date.getDate() === today.getDate() && 
                    date.getMonth() === today.getMonth() && 
                    date.getFullYear() === today.getFullYear();
    
    const day = createCalendarDay(i, false, isPast, isToday, date);
    calendarGrid.appendChild(day);
  }
  
  // Add next month days to complete the grid
  const totalCells = calendarGrid.children.length - 7; // Subtract headers
  const remainingCells = (Math.ceil(totalCells / 7) * 7) - totalCells;
  for (let i = 1; i <= remainingCells; i++) {
    const day = createCalendarDay(i, true, false);
    calendarGrid.appendChild(day);
  }
}

function createCalendarDay(dayNumber, isOtherMonth, isDisabled, isToday = false, date = null) {
  const day = document.createElement('div');
  day.className = 'calendar-day';
  day.textContent = dayNumber;

  if (isOtherMonth) {
    day.classList.add('other-month');
  }

  let disabled = isDisabled;
  let closed = false;
  let blockedDay = false;

  if (date) {
    const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

    if (branchClosedDates.has(dateStr)) {
      closed = true;
      disabled = true;
      day.title = 'Branch is closed on this day';
      day.classList.add('closed');
    }

    if (branchBlockedDays.has(date.getDay())) {
      blockedDay = true;
      disabled = true;
      day.title = 'Branch is always closed on this day of week';
      day.classList.add('blocked-day');
    }
  }

  if (disabled) {
    day.classList.add('disabled');
  }

  if (isToday) {
    day.classList.add('today');
  }

  if (!disabled && !isOtherMonth && date) {
    day.addEventListener('click', () => selectDate(date, day));
  }

  if (closed) {
    const badge = document.createElement('span');
    badge.className = 'calendar-badge calendar-badge-closed';
    badge.textContent = 'Closed';
    day.appendChild(badge);
  } else if (blockedDay) {
    const badge = document.createElement('span');
    badge.className = 'calendar-badge calendar-badge-blocked';
    badge.textContent = 'No service';
    day.appendChild(badge);
  }

  return day;
}

function selectDate(date, element) {
  // Remove previous selection
  document.querySelectorAll('.calendar-day.selected').forEach(day => {
    day.classList.remove('selected');
  });
  
  // Add new selection
  element.classList.add('selected');
  selectedDate = date;
  
  // Load available time slots
  loadTimeSlots(date);
  
  // Show time section
  document.getElementById('timeSection').style.display = 'block';
  document.getElementById('infoMessage').style.display = 'flex';
  
  // Reset time selection
  selectedTime = null;
  updateProceedButton();
}

// Time Slots Functions
async function loadTimeSlots(date) {
  try {
    const branchId = selectedBranch.id;
    const dateStr = date.getFullYear() + '-' + 
                    String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(date.getDate()).padStart(2, '0');
    
    // Generate time slots
    generateTimeSlots();
    
    // Check availability for each time slot
    await checkTimeSlotAvailability(dateStr);
    
  } catch (error) {
    console.error('Error loading time slots:', error);
    generateTimeSlots();
  }
}

async function checkTimeSlotAvailability(dateStr) {
  console.log('🔍 Checking availability for date:', dateStr);
  console.log('🔍 Service ID:', selectedService.serviceid);
  console.log('🔍 Service details:', selectedService);
  
  const timeSlotElements = document.querySelectorAll('.time-slot');
  console.log('🔍 Found time slots:', timeSlotElements.length);
  
  for (let i = 0; i < timeSlotElements.length; i++) {
    const slot = timeSlotElements[i];
    const timeText = slot.textContent.trim();

    if (slot.disabled) {
      continue; // keep past/no-select and pre-disabled state
    }
    
    try {
      // Convert time to 24-hour format for API
      const time24 = convertTo24Hour(timeText);
      console.log(`🕐 Checking ${timeText} (${time24})...`);
      
      // Check availability via API
      const apiUrl = `../../backend/public/index.php?url=reservation/checkAvailability&date=${dateStr}&time=${time24}&serviceId=${selectedService.serviceid}`;
      console.log(`📡 API URL: ${apiUrl}`);
      
      const response = await fetch(apiUrl);
      console.log(`📡 Response status: ${response.status} ${response.statusText}`);
      
      if (response.ok) {
        const result = await response.json();
        console.log(`📊 API Response for ${timeText}:`, result);
        
        if (!result.available) {
          // Disable the slot if not available
          slot.classList.add('disabled');
          slot.disabled = true;
          slot.title = 'This time slot is already booked';
          console.log(`❌ ${timeText} is UNAVAILABLE - already booked`);
        } else {
          // Enable the slot and add click listener
          slot.addEventListener('click', () => selectTime(timeText, slot));
          slot.title = 'Available';
          console.log(`✅ ${timeText} is AVAILABLE - enabled for selection`);
        }
      } else {
        console.error(`❌ API Error for ${timeText}: ${response.status} ${response.statusText}`);
        // If API fails, enable the slot (fallback behavior)
        slot.addEventListener('click', () => selectTime(timeText, slot));
        slot.title = 'Available (API error fallback)';
        console.log(`⚠️  ${timeText} enabled due to API error`);
      }
    } catch (error) {
      console.error(`💥 Exception checking ${timeText}:`, error);
      // If API fails, enable the slot (fallback behavior)
      slot.addEventListener('click', () => selectTime(timeText, slot));
      slot.title = 'Available (exception fallback)';
      console.log(`⚠️  ${timeText} enabled due to exception`);
    }
  }
  
  console.log('✅ Availability check completed');
}

function convertTo24Hour(time12) {
  // Convert time from "HH:MM AM/PM" format to "HH:MM" 24-hour format
  const time = time12.trim();
  const [timePart, period] = time.split(' ');
  const [hours, minutes] = timePart.split(':');
  
  let hours24 = parseInt(hours);
  if (period === 'PM' && hours24 !== 12) {
    hours24 += 12;
  } else if (period === 'AM' && hours24 === 12) {
    hours24 = 0;
  }
  
  return `${hours24.toString().padStart(2, '0')}:${minutes}`;
}

function getServiceDurationMinutes() {
  if (!selectedService || !selectedService.duration) return 0;

  // Accept formats like "60", "60 min", "1 hour", etc.
  const match = selectedService.duration.toString().match(/(\d+)/);
  if (!match) return 0;

  const minutes = parseInt(match[1], 10);
  if (isNaN(minutes)) return 0;

  // If duration is given as hours (e.g., 2h), keep it as minutes since we match digits only
  return minutes;
}

function generateTimeSlots() {
  const opening = branchOpeningTime || sessionStorage.getItem('branchOpeningTime') || '09:00:00';
  const closing = branchClosingTime || sessionStorage.getItem('branchClosingTime') || '18:00:00';

  const openingMinutes = parseTimeToMinutes(opening);
  const closingMinutes = parseTimeToMinutes(closing);

  let slots = [];

  if (openingMinutes === null || closingMinutes === null || openingMinutes >= closingMinutes) {
    console.warn('Invalid branch hours, falling back to default time slots.');
    slots = ['09:00 AM','10:00 AM','11:00 AM','12:00 PM','01:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM','06:00 PM'];
  } else {
    const serviceDuration = getServiceDurationMinutes();
    const slotStep = 30; // minutes now fixed to 15-min interval

    // if service duration is zero or not set, allow up to closing time; else allow start times that complete before closing
    const lastStart = serviceDuration > 0 ? 
      Math.max(openingMinutes, closingMinutes - serviceDuration) :
      closingMinutes;

    for (let mins = openingMinutes; mins <= lastStart; mins += slotStep) {
      slots.push(formatTimeFromMinutes(mins));
    }

    // If service duration is zero or not set, include closing as optional slot (if exact boundary), else closing is not a starting slot.
    if (serviceDuration <= 0 && closingMinutes > openingMinutes && !slots.includes(formatTimeFromMinutes(closingMinutes))) {
      slots.push(formatTimeFromMinutes(closingMinutes));
    }
  }

  availableTimeSlots = slots;
  renderTimeSlots();
}

function parseTimeToMinutes(timeString) {
  if (!timeString) return null;
  const parts = timeString.split(':');
  if (parts.length < 2) return null;

  let hour = parseInt(parts[0], 10);
  let minute = parseInt(parts[1], 10);

  if (isNaN(hour) || isNaN(minute)) return null;
  return hour * 60 + minute;
}

function formatTimeFromMinutes(totalMinutes) {
  const hour24 = Math.floor(totalMinutes / 60);
  const minute = totalMinutes % 60;
  const period = hour24 >= 12 ? 'PM' : 'AM';
  const hour12 = hour24 % 12 === 0 ? 12 : hour24 % 12;
  return `${String(hour12).padStart(2, '0')}:${String(minute).padStart(2, '0')} ${period}`;
}

function formatBranchTime(timeString) {
  if (!timeString) return 'N/A';
  const parts = timeString.split(':');
  if (parts.length < 2) return timeString;
  let hours = parseInt(parts[0], 10);
  const mins = parts[1].padStart(2, '0');
  if (isNaN(hours)) return timeString;
  const period = hours >= 12 ? 'PM' : 'AM';
  const hour12 = hours % 12 === 0 ? 12 : hours % 12;
  return `${hour12}:${mins} ${period}`;
}

function renderTimeSlots() {
  const timeSlotsContainer = document.getElementById('timeSlots');
  timeSlotsContainer.innerHTML = '';

  const now = new Date();
  const isToday = selectedDate && selectedDate.getFullYear() === now.getFullYear() &&
                  selectedDate.getMonth() === now.getMonth() &&
                  selectedDate.getDate() === now.getDate();

  availableTimeSlots.forEach(time => {
    const slot = document.createElement('button');
    slot.className = 'time-slot';
    slot.textContent = time;

    if (isToday) {
      const slot24 = convertTo24Hour(time);
      const slotParts = slot24.split(':');
      if (slotParts.length === 2) {
        const slotHour = parseInt(slotParts[0], 10);
        const slotMin = parseInt(slotParts[1], 10);

        if (!isNaN(slotHour) && !isNaN(slotMin)) {
          const slotDateTime = new Date(
            selectedDate.getFullYear(),
            selectedDate.getMonth(),
            selectedDate.getDate(),
            slotHour,
            slotMin,
            0
          );

          if (slotDateTime <= now) {
            slot.classList.add('disabled');
            slot.disabled = true;
            slot.title = 'Past time (today)';
          }
        }
      }
    }

    if (!slot.disabled) {
      slot.title = 'Checking availability...';
    }

    timeSlotsContainer.appendChild(slot);
  });
}

function selectTime(time, element) {
  // Remove previous selection
  document.querySelectorAll('.time-slot.selected').forEach(slot => {
    slot.classList.remove('selected');
  });
  
  // Add new selection
  element.classList.add('selected');
  selectedTime = time;
  
  updateProceedButton();
}

function updateProceedButton() {
  const proceedBtn = document.getElementById('proceedBtn');
  proceedBtn.disabled = !(selectedDate && selectedTime);
}

// Proceed to Payment
function proceedToPayment() {
  if (!selectedDate || !selectedTime) return;

  const price = parseFloat(selectedService.price);

  // ✅ Read dynamic rate from sessionStorage
  const rate = parseFloat(sessionStorage.getItem('branchDownpaymentRate') || '0.5');
  const downpayment = Math.max(price * rate, 1.00); // Minimum ₱1.00 for PayMongo

  const bookingData = {
    service: selectedService,
    branch: selectedBranch,
    date: selectedDate.getFullYear() + '-' +
          String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
          String(selectedDate.getDate()).padStart(2, '0'),
    time: selectedTime,
    totalPrice: price,
    downpayment: downpayment,
    downpaymentRate: rate,           // ✅ carry rate forward for payment.html
    is_package: selectedService.is_package || false,
    booked_package_id: selectedService.is_package ? selectedService.package_id : null
  };

  sessionStorage.setItem('bookingData', JSON.stringify(bookingData));
  window.location.href = './payment.html';
}