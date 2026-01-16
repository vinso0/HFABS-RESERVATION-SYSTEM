// API Configuration
const API_BASE_URL = '/api'; // Replace with your actual API base URL

document.addEventListener('DOMContentLoaded', function() {
  // Smart back button that detects where user came from
  const backBtn = document.querySelector('.back-btn');
  
  if (backBtn) {
    backBtn.onclick = function() {
      // Check if there's a referrer
      if (document.referrer) {
        window.history.back();
      } else {
        // Fallback to home if no history
        const currentPath = window.location.pathname;
        const isInViewsFolder = currentPath.includes('/views/');
        
        if (isInViewsFolder) {
          window.location.href = '../index.html';
        } else {
          window.location.href = 'index.html';
        }
      }
    };
  }
  
  initializePage();
});

async function initializePage() {
  const urlParams = new URLSearchParams(window.location.search);
  const branchId = urlParams.get('branch');
  
  if (!branchId) {
    showError('No branch selected. Please go back and select a branch.');
    return;
  }
  
  await Promise.all([
    loadBranchInfo(branchId),
    loadServices(branchId)
  ]);
}

// Load branch information
async function loadBranchInfo(branchId) {
  try {
    const response = await fetch(`${API_BASE_URL}/branches/${branchId}`);
    
    if (!response.ok) {
      throw new Error('Failed to fetch branch info');
    }
    
    const branch = await response.json();
    displayBranchInfo(branch);
    
  } catch (error) {
    console.error('Error loading branch info:', error);
    
    // Fallback to dummy data
    const dummyBranch = {
      branchid: branchId,
      branchname: branchId === '1' ? 'Caloocan Branch' : 'Quezon City Branch'
    };
    displayBranchInfo(dummyBranch);
  }
}

function displayBranchInfo(branch) {
  const branchNameElement = document.getElementById('branchName');
  branchNameElement.textContent = branch.branchname;
}

// Load services for selected branch
async function loadServices(branchId) {
  const grid = document.getElementById('servicesGrid');
  
  try {
    const response = await fetch(`${API_BASE_URL}/branches/${branchId}/services`);
    
    if (!response.ok) {
      throw new Error('Failed to fetch services');
    }
    
    const services = await response.json();
    displayServices(services);
    
  } catch (error) {
    console.error('Error loading services:', error);
    loadDummyServices(branchId);
  }
}

// Load dummy services (for development)
function loadDummyServices(branchId) {
  const allServices = [
    {
      serviceid: 1,
      servicename: 'Hair Spa Treatment',
      description: 'Deep conditioning hair treatment with premium products to nourish and revitalize your hair.',
      price: 1800.00,
      isavailable: 1,
      icon: '💇'
    },
    {
      serviceid: 2,
      servicename: 'Hair Rebonding',
      description: 'Permanent hair straightening treatment for sleek, smooth, and manageable hair.',
      price: 3500.00,
      isavailable: 1,
      icon: '💇'
    },
    {
      serviceid: 3,
      servicename: 'Classic Manicure',
      description: 'Basic nail care and polish application with hand massage for perfectly groomed nails.',
      price: 350.00,
      isavailable: 1,
      icon: '💅'
    },
    {
      serviceid: 4,
      servicename: 'Gel Pedicure',
      description: 'Long-lasting gel nail treatment with foot spa and massage for beautiful, durable nails.',
      price: 600.00,
      isavailable: 1,
      icon: '💅'
    },
    {
      serviceid: 5,
      servicename: 'Deep Cleansing Facial',
      description: 'Deep pore cleansing facial that removes impurities and refreshes your skin.',
      price: 1200.00,
      isavailable: 1,
      icon: '✨'
    },
    {
      serviceid: 6,
      servicename: 'Anti-Aging Facial',
      description: 'Rejuvenating facial treatment designed to reduce fine lines and restore youthful glow.',
      price: 2000.00,
      isavailable: 1,
      icon: '✨'
    },
    {
      serviceid: 7,
      servicename: 'Keratin Treatment',
      description: 'Smoothing keratin therapy that eliminates frizz and adds shine to your hair.',
      price: 4500.00,
      isavailable: 1,
      icon: '💇'
    },
    {
      serviceid: 8,
      servicename: 'Hair Botox',
      description: 'Deep repair treatment that restores damaged hair and improves hair texture.',
      price: 3800.00,
      isavailable: 1,
      icon: '💇'
    },
    {
      serviceid: 9,
      servicename: 'Swedish Massage',
      description: 'Relaxing full body massage using gentle, flowing strokes to ease tension and stress.',
      price: 1500.00,
      isavailable: 1,
      icon: '💆'
    },
    {
      serviceid: 10,
      servicename: 'Hot Stone Therapy',
      description: 'Therapeutic hot stone massage that promotes deep relaxation and muscle relief.',
      price: 2000.00,
      isavailable: 1,
      icon: '💆'
    },
    {
      serviceid: 11,
      servicename: 'Aromatherapy Massage',
      description: 'Essential oil massage therapy that combines relaxation with therapeutic benefits.',
      price: 1600.00,
      isavailable: 1,
      icon: '💆'
    }
  ];
  
  // Filter services based on branch
  let branchServices;
  if (branchId === '1') {
    branchServices = allServices;
  } else if (branchId === '2') {
    branchServices = allServices.filter(s => [1, 2, 5, 6].includes(s.serviceid));
  } else {
    branchServices = [];
  }
  
  displayServices(branchServices);
}

// Display services in grid
function displayServices(services) {
  const grid = document.getElementById('servicesGrid');
  
  if (services.length === 0) {
    grid.innerHTML = '<div class="empty-state">No services available at this branch.</div>';
    return;
  }
  
  grid.innerHTML = '';
  
  services.forEach(service => {
    const serviceCard = createServiceCard(service);
    grid.appendChild(serviceCard);
  });
}

// Create service card element
function createServiceCard(service) {
  const card = document.createElement('div');
  card.className = 'service-card';
  
  const isAvailable = service.isavailable === 1;
  
  card.innerHTML = `
    <div class="service-image">
      ${service.icon || '🌸'}
    </div>
    <div class="service-content">
      <h3 class="service-title">${service.servicename}</h3>
      <p class="service-description">${service.description}</p>
      <p class="service-price">₱${parseFloat(service.price).toFixed(2)}</p>
      <button 
        class="service-btn" 
        ${!isAvailable ? 'disabled' : ''}
        onclick="bookService(${service.serviceid}, '${service.servicename}', ${service.price})"
      >
        ${isAvailable ? 'Book Now' : 'Unavailable'}
      </button>
    </div>
  `;
  
  return card;
}

// Book service function
function bookService(serviceId, serviceName, price) {
  const branchId = new URLSearchParams(window.location.search).get('branch');
  const branchName = document.getElementById('branchName').textContent;
  
  // Store service info
  sessionStorage.setItem('selectedServiceId', serviceId);
  sessionStorage.setItem('selectedServiceName', serviceName);
  sessionStorage.setItem('selectedServicePrice', price);
  sessionStorage.setItem('selectedBranchId', branchId);
  sessionStorage.setItem('selectedBranchName', branchName);
  
  // Check if user is logged in
  const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';
  
  if (isLoggedIn) {
    window.location.href = './booking.html';
  } else {
    window.location.href = './customer-login.html?return=booking';
  }
}

// Error display function
function showError(message) {
  const grid = document.getElementById('servicesGrid');
  grid.innerHTML = `
    <div class="error-state">
      <p>${message}</p>
      <button class="service-btn" onclick="window.history.back()" style="max-width: 200px; margin: 1rem auto 0;">
        Go Back
      </button>
    </div>
  `;
}
// Note: Remember to replace the API_BASE_URL with your actual API endpoint.