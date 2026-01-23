// PayMongo Configuration
const PAYMONGO_PUBLIC_KEY = 'put your_public_key_here(ex. sk_test_xxx)';

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
}

async function processPayment(paymentMethod) {
  const loadingOverlay = document.getElementById('loadingOverlay');
  loadingOverlay.style.display = 'flex';
  
  try {
    const { downpayment, service, branch, date, time } = bookingData;
    const amountInCentavos = Math.round(downpayment * 100);
    
    // Format description
    const description = `${service.servicename} - ${branch.name}`;
    const remarks = `Booking: ${date} at ${time}`;
    
    // Create a payment link
    const options = {
      method: 'POST',
      headers: {
        accept: 'application/json',
        'content-type': 'application/json',
        authorization: `Basic ${btoa(PAYMONGO_PUBLIC_KEY + ':')}`
      },
      body: JSON.stringify({
        data: {
          attributes: {
            amount: amountInCentavos,
            description: description,
            remarks: remarks
          }
        }
      })
    };

    const response = await fetch('https://api.paymongo.com/v1/links', options);
    const result = await response.json();
    
    if (response.ok) {
      // Store payment link ID for tracking
      sessionStorage.setItem('paymentLinkId', result.data.id);
      
      // Redirect to PayMongo payment page
      window.location.href = result.data.attributes.checkout_url;
    } else {
      console.error('PayMongo error:', result);
      throw new Error(result.errors?.[0]?.detail || 'Failed to create payment link');
    }
    
  } catch (error) {
    console.error('Payment error:', error);
    loadingOverlay.style.display = 'none';
    alert('Payment processing failed. Please try again. Error: ' + error.message);
  }
}
