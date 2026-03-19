// State Management
let bookingData = null;

// DEBUG: Verify this file is being loaded with latest version
console.log('=== PAYMENT.JS LOADED - VERSION 202603051230 ===');
console.log('Current timestamp:', new Date().toISOString());

// Helper function to convert 12-hour time to 24-hour format for database
function convertTo24HourFormat(time12h) {
  const timeMatch = time12h.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
  if (!timeMatch) return time12h; // Return as-is if no AM/PM found
  
  let hours = parseInt(timeMatch[1]);
  const minutes = parseInt(timeMatch[2]);
  const period = timeMatch[3].toUpperCase();
  
  // Convert to 24-hour format
  if (period === 'PM' && hours !== 12) hours += 12;
  if (period === 'AM' && hours === 12) hours = 0;
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`;
}

// Helper function to calculate end time based on start time and duration
function calculateEndTime(startTime, durationMinutes) {
  console.log('calculateEndTime called with:', startTime, durationMinutes);
  
  // Parse start time (format: "01:00 PM" or "13:00")
  const timeMatch = startTime.match(/(\d{1,2}):(\d{2})\s*(AM|PM)?/i);
  console.log('Time match result:', timeMatch);
  
  if (!timeMatch) {
    console.log('No time match, returning original:', startTime);
    return startTime;
  }
  
  let hours = parseInt(timeMatch[1]);
  const minutes = parseInt(timeMatch[2]);
  const period = timeMatch[3]?.toUpperCase();
  
  console.log('Parsed time:', { hours, minutes, period });
  
  // Convert to 24-hour format
  if (period === 'PM' && hours !== 12) hours += 12;
  if (period === 'AM' && hours === 12) hours = 0;
  
  console.log('24-hour format:', hours);
  
  // Add duration
  const totalMinutes = (hours * 60 + minutes) + durationMinutes;
  let endHours = Math.floor(totalMinutes / 60);
  const endMinutes = totalMinutes % 60;
  
  // Handle times that go past 24 hours (next day)
  if (endHours >= 24) {
    endHours = endHours % 24;
  }
  
  console.log('End time calculation:', { totalMinutes, endHours, endMinutes });
  
  // Return in 24-hour format for database storage
  const result = `${endHours.toString().padStart(2, '0')}:${endMinutes.toString().padStart(2, '0')}:00`;
  console.log('Final result (24-hour format):', result);
  
  return result;
}

// DEBUG: Test the time conversion functions with different durations
console.log('Testing convertTo24HourFormat:', convertTo24HourFormat('01:00 PM')); // Should be "13:00:00"
console.log('Testing convertTo24HourFormat:', convertTo24HourFormat('11:00 PM')); // Should be "23:00:00"
console.log('Testing calculateEndTime (30 min):', calculateEndTime('01:00 PM', 30)); // Should be "13:30:00"
console.log('Testing calculateEndTime (60 min):', calculateEndTime('01:00 PM', 60)); // Should be "14:00:00"
console.log('Testing calculateEndTime (120 min):', calculateEndTime('11:00 PM', 120)); // Should be "01:00:00" (crosses midnight)
console.log('Testing calculateEndTime (90 min):', calculateEndTime('10:30 PM', 90)); // Should be "00:00:00" (crosses midnight)

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
    alert('Please log in first to access the payment page.');
    window.location.href = './customer-login.html';
    return;
  }

  loadBookingData();
  setupEventListeners();
});

function loadBookingData() {
  const storedData = sessionStorage.getItem('bookingData');
  
  if (!storedData) {
    alert('No booking data found. Redirecting to home page.');
    window.location.href = './customer-home.php';
    return;
  }
  
  bookingData = JSON.parse(storedData);
  displayBookingSummary();
}

function displayBookingSummary() {
  const { service, branch, date, time, totalPrice, downpayment } = bookingData;
  
  // Format date
  const dateObj = new Date(date);
  const formattedDate = dateObj.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
  
  // Display summary
  document.getElementById('serviceName').textContent = service.servicename;
  document.getElementById('serviceDuration').textContent = service.duration || 'N/A';
  document.getElementById('branchName').textContent = branch.name;
  document.getElementById('bookingDate').textContent = formattedDate;
  document.getElementById('bookingTime').textContent = time;
  
  // Display prices
  const remainingBalance = totalPrice - downpayment;
  document.getElementById('totalPrice').textContent = `₱${totalPrice.toFixed(2)}`;
  document.getElementById('downpaymentAmount').textContent = `₱${downpayment.toFixed(2)}`;
  document.getElementById('remainingBalance').textContent = `₱${remainingBalance.toFixed(2)}`;
}

function setupEventListeners() {
  document.getElementById('payWithGcash').addEventListener('click', () => processPayment('gcash'));
  document.getElementById('payWithMaya').addEventListener('click', () => processPayment('paymaya'));
  //document.getElementById('payWithQR').addEventListener('click', () => processPayment('qrph'));

}

async function processPayment(paymentMethod) {
  const loadingOverlay = document.getElementById('loadingOverlay');
  loadingOverlay.style.display = 'flex';
  
  try {
        const { downpayment, reservation_id, totalPrice, branch } = bookingData;
        
        // Get user data from localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const userId = userData.user_id;
        
        // DEBUG: Log the data being sent
        console.log('=== PAYMENT DEBUG ===');
        console.log('Booking Data:', bookingData);
        console.log('User Data:', userData);
        console.log('User ID:', userId);
        console.log('Branch:', branch);

        // Prepare services data for metadata
        const serviceDuration = parseInt(bookingData.service.duration) || 60; // Ensure we have a valid duration
        console.log('Service duration from booking data:', bookingData.service.duration, 'Parsed as:', serviceDuration);
        
        const servicesData = [{
            service_name: bookingData.service.servicename,
            price: bookingData.service.price || bookingData.totalPrice,
            duration_minutes: serviceDuration,
            category_name: bookingData.service.category || 'General',
            description: bookingData.service.description || '',
            default_service_id: bookingData.service.serviceid,
            branch_service_override_id: null,
            remaining_balance: bookingData.totalPrice - bookingData.downpayment
        }];

        // Prepare schedule data for metadata
        const scheduleData = {
            schedule_date: bookingData.date,
            start_time: convertTo24HourFormat(bookingData.time),
            end_time: calculateEndTime(bookingData.time, serviceDuration)
        };
        
        console.log('Schedule data:', scheduleData);

        const requestData = {
            amount: Math.max(downpayment, 1.00), // Ensure minimum 1.00 PHP for PayMongo
            reservation_id: reservation_id || "No." + Date.now(), // Fallback if no ID yet
            metadata: {
                reservation_id: reservation_id || "No." + Date.now(),
                user_id: userId,
                branch_id: branch.id,
                total_price: totalPrice,
                services: servicesData,
                schedule_date: scheduleData.schedule_date,
                start_time: scheduleData.start_time,
                end_time: scheduleData.end_time
            }
        };
        
        console.log('Request Data:', requestData);
        console.log('Services Data:', servicesData);
        console.log('Schedule Data:', scheduleData);
        console.log('Full Metadata:', requestData.metadata);

        // call backend
        const response = await fetch('https://undappled-bea-schemeful.ngrok-free.dev/HFABS/backend/public/index.php?url=payment/create&t=' + Date.now(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();
        console.log('Payment Response:', result);
        
        if (response.ok && result.checkout_url) {
            // success url
            window.location.href = result.checkout_url;
        } else {
            throw new Error(result.error || 'Failed to create session');
        }
        
    } catch (error) {
        console.error('Payment error:', error);
        loadingOverlay.style.display = 'none';
        alert('Payment failed: ' + error.message);
    }
}