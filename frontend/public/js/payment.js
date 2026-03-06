// State Management
let bookingData = null;

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

        const requestData = {
            amount: downpayment,
            reservation_id: reservation_id || "TEMP_" + Date.now(), // Fallback if no ID yet
            metadata: {
                reservation_id: reservation_id || "TEMP_" + Date.now(),
                user_id: userId,
                branch_id: branch.id,
                total_price: totalPrice
            }
        };
        
        console.log('Request Data:', requestData);

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