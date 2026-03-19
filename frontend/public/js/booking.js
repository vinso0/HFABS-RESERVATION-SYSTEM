// API Configuration
const API_BASE_URL = '/api';

// State Management
let selectedService = null;
let selectedBranch = null;
let selectedDate = null;
let selectedTime = null;
let currentMonth = new Date();
let availableTimeSlots = [];

// Check if user is logged in
function isLoggedIn() {
const token = localStorage.getItem('token');
const userData = localStorage.getItem('userData');
return !!(token && userData);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  // Check if user is logged in
  if (!isLoggedIn()) {
    alert('Please log in first to access the booking page.');
    window.location.href = './customer-login.html';
    return;
  }

  loadBookingData();
  initializeCalendar();
  setupEventListeners();
});

function loadBookingData() {
  // Load selected service from sessionStorage
  const servicesData = sessionStorage.getItem('selectedServices');
  const branchId = sessionStorage.getItem('selectedBranchId');
  const branchName = sessionStorage.getItem('selectedBranchName');
  
  if (!servicesData) {
    alert('No service selected. Redirecting to services page.');
    window.location.href = './customer-home.php';
    return;
  }
  
  const services = JSON.parse(servicesData);
  selectedService = services[0]; // Only one service now
  selectedBranch = { id: branchId, name: branchName };
  
  displayServiceInfo();
}

function displayServiceInfo() {
  const price = parseFloat(selectedService.price);
  const downpayment = price * 0.5;
  
  document.getElementById('serviceName').textContent = `Book: ${selectedService.servicename}`;
  document.getElementById('servicePrice').textContent = `₱${price.toFixed(2)}`;
  document.getElementById('serviceDuration').textContent = `Duration: ${selectedService.duration || 'N/A'}`;
  document.getElementById('serviceDownpayment').textContent = `Downpayment: ₱${downpayment.toFixed(2)}`;
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
  
  if (isDisabled) {
    day.classList.add('disabled');
  }
  
  if (isToday) {
    day.classList.add('today');
  }
  
  if (!isDisabled && !isOtherMonth && date) {
    day.addEventListener('click', () => selectDate(date, day));
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

function generateTimeSlots() {
  // Generate time slots from 9 AM to 6 PM
  const slots = [
    '09:00 AM', '10:00 AM', '11:00 AM',
    '12:00 PM', '01:00 PM', '02:00 PM',
    '03:00 PM', '04:00 PM', '05:00 PM', '06:00 PM'
  ];
  
  availableTimeSlots = slots;
  renderTimeSlots();
}

function renderTimeSlots() {
  const timeSlotsContainer = document.getElementById('timeSlots');
  timeSlotsContainer.innerHTML = '';
  
  availableTimeSlots.forEach(time => {
    const slot = document.createElement('button');
    slot.className = 'time-slot';
    slot.textContent = time;
    
    // Initially enable all slots - they will be disabled if not available
    slot.title = 'Checking availability...';
    
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
  
  // Store booking data
  const bookingData = {
    service: selectedService,
    branch: selectedBranch,
    date: selectedDate.getFullYear() + '-' + 
         String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' + 
         String(selectedDate.getDate()).padStart(2, '0'),
    time: selectedTime,
    totalPrice: parseFloat(selectedService.price),
    downpayment: Math.max(parseFloat(selectedService.price) * 0.5, 1.00) // Ensure minimum 1.00 PHP
  };
  
  sessionStorage.setItem('bookingData', JSON.stringify(bookingData));
  
  // Redirect to payment page
  window.location.href = './payment.html';
}