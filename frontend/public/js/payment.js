// State Management
let bookingData = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
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
  document.getElementById('payWithQR').addEventListener('click', () => processPayment('qrph'));

}

async function processPayment(paymentMethod) {
  const loadingOverlay = document.getElementById('loadingOverlay');
  loadingOverlay.style.display = 'flex';
  
  try {
        const { downpayment, order_id } = bookingData; 

        //  call backend
        const response = await fetch('http://localhost/HFABS/backend/public/index.php?url=payment/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                amount: downpayment,
                order_id: order_id || "TEMP_" + Date.now() // Fallback if no ID yet
            })
        });

        const result = await response.json();
        
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