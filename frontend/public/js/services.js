// API Configuration
const API_BASE_URL = '/api';

// State Management
let allServices = [];
let selectedServices = new Map(); // serviceid -> service object
let currentCategory = 'all';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  initializePage();
  setupEventListeners();
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

// Setup event listeners
function setupEventListeners() {
  // Category tabs
  const tabBtns = document.querySelectorAll('.tab-btn');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      // Update active state
      tabBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      
      // Filter services
      currentCategory = this.dataset.category;
      filterServices(currentCategory);
    });
  });
  
  // Continue button
  const continueBtn = document.getElementById('continueBtn');
  continueBtn.addEventListener('click', handleContinue);
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
    const dummyBranches = {
      '1': {
        branchid: 1,
        branchname: 'Happy Face & Body Spa - Caloocan',
        location: '102 Caimito Rd., Caloocan City, Unit 1D, Caimito Place'
      },
      '2': {
        branchid: 2,
        branchname: 'Happy Face & Body Spa - Quezon City',
        location: '850 Atherton, Quezon City'
      }
    };
    
    displayBranchInfo(dummyBranches[branchId] || dummyBranches['1']);
  }
}

function displayBranchInfo(branch) {
  document.getElementById('branchName').textContent = branch.branchname;
  document.getElementById('branchAddress').textContent = branch.location;
}

// Load services for selected branch
async function loadServices(branchId) {
  try {
    const response = await fetch(`${API_BASE_URL}/branches/${branchId}/services`);
    
    if (!response.ok) {
      throw new Error('Failed to fetch services');
    }
    
    const services = await response.json();
    allServices = services;
    displayServices(allServices);
    
  } catch (error) {
    console.error('Error loading services:', error);
    loadDummyServices(branchId);
  }
}

// Load dummy services
function loadDummyServices(branchId) {
  const allDummyServices = [
    {
      serviceid: 1,
      servicename: "Hair Spa Treatment",
      description: "Deep conditioning hair treatment with premium products to nourish and revitalize your hair.",
      price: 1800.00,
      duration: "5 min",
      isavailable: 1,
      category: "Hair Services"
    },
    {
      serviceid: 2,
      servicename: "Hair Rebonding",
      description: "Permanent hair straightening treatment for sleek, smooth, and manageable hair.",
      price: 3500.00,
      duration: "5 min",
      isavailable: 1,
      category: "Hair Services"
    },
    {
      serviceid: 3,
      servicename: "Classic Manicure",
      description: "Basic nail care and polish application with hand massage for perfectly groomed nails.",
      price: 350.00,
      duration: "5 min",
      isavailable: 1,
      category: "Nail Services"
    },
    {
      serviceid: 4,
      servicename: "Gel Pedicure",
      description: "Long-lasting gel nail treatment with foot spa and massage for beautiful, durable nails.",
      price: 600.00,
      duration: "5 min",
      isavailable: 1,
      category: "Nail Services"
    },
    {
      serviceid: 5,
      servicename: "Deep Cleansing Facial",
      description: "Deep pore cleansing facial that removes impurities and refreshes your skin.",
      price: 1200.00,
      duration: "5 min",
      isavailable: 1,
      category: "Facial Services"
    },
    {
      serviceid: 6,
      servicename: "Anti-Aging Facial",
      description: "Rejuvenating facial treatment designed to reduce fine lines and restore youthful glow.",
      price: 2000.00,
      duration: "5 min",
      isavailable: 1,
      category: "Facial Services"
    },
    {
      serviceid: 7,
      servicename: "Keratin Treatment",
      description: "Smoothing keratin therapy that eliminates frizz and adds shine to your hair.",
      price: 4500.00,
      duration: "5 min",
      isavailable: 1,
      category: "Hair Services"
    },
    {
      serviceid: 8,
      servicename: "Hair Botox",
      description: "Deep repair treatment that restores damaged hair and improves hair texture.",
      price: 3800.00,
      duration: "5 min",
      isavailable: 1,
      category: "Hair Services"
    },
    {
      serviceid: 9,
      servicename: "Swedish Massage",
      description: "Relaxing full body massage using gentle, flowing strokes to ease tension and stress.",
      price: 1500.00,
      duration: "5 min",
      isavailable: 1,
      category: "Massage Services"
    },
    {
      serviceid: 10,
      servicename: "Hot Stone Therapy",
      description: "Therapeutic hot stone massage that promotes deep relaxation and muscle relief.",
      price: 2000.00,
      duration: "5 min",
      isavailable: 1,
      category: "Massage Services"
    },
    {
      serviceid: 11,
      servicename: "Aromatherapy Massage",
      description: "Essential oil massage therapy that combines relaxation with therapeutic benefits.",
      price: 1600.00,
      duration: "5 min",
      isavailable: 1,
      category: "Massage Services"
    }
  ];
  
  // Filter services based on branch
  if (branchId === '1') {
    allServices = allDummyServices;
  } else if (branchId === '2') {
    allServices = allDummyServices.filter(s => [1, 2, 5, 6].includes(s.serviceid));
  } else {
    allServices = [];
  }
  
  displayServices(allServices);
}

// Filter services by category
function filterServices(category) {
  const categoryTitle = document.getElementById('categoryTitle');
  
  if (category === 'all') {
    categoryTitle.textContent = 'Featured';
    displayServices(allServices);
  } else {
    categoryTitle.textContent = category;
    const filtered = allServices.filter(s => s.category === category);
    displayServices(filtered);
  }
}

// Display services in list
function displayServices(services) {
  const servicesList = document.getElementById('servicesList');
  
  if (services.length === 0) {
    servicesList.innerHTML = '<div class="empty-state">No services available in this category.</div>';
    return;
  }
  
  servicesList.innerHTML = '';
  
  services.forEach(service => {
    const serviceItem = createServiceItem(service);
    servicesList.appendChild(serviceItem);
  });
}

// Create service item element
function createServiceItem(service) {
  const item = document.createElement('div');
  item.className = 'service-item';
  item.dataset.serviceid = service.serviceid;
  
  // Check if already selected
  if (selectedServices.has(service.serviceid)) {
    item.classList.add('selected');
  }
  
  const isAvailable = service.isavailable === 1;
  
  if (!isAvailable) {
    item.style.opacity = '0.5';
    item.style.cursor = 'not-allowed';
  }
  
  item.innerHTML = `
    <div class="service-info">
      <div class="service-header">
        <h3 class="service-name">${service.servicename}</h3>
        <span class="service-duration">${service.duration || 'N/A'}</span>
      </div>
      <p class="service-description">${service.description}</p>
      <p class="service-price">₱${parseFloat(service.price).toFixed(2)}</p>
    </div>
    <div class="service-action"></div>
  `;
  
  if (isAvailable) {
    item.addEventListener('click', () => toggleService(service, item));
  }
  
  return item;
}


// Toggle service selection (only one at a time)
function toggleService(service, itemElement) {
  // If clicking the same service that's already selected, deselect it
  if (selectedServices.has(service.serviceid)) {
    selectedServices.delete(service.serviceid);
    itemElement.classList.remove('selected');
  } else {
    // Clear all previous selections
    selectedServices.clear();
    
    // Remove 'selected' class from all service items
    document.querySelectorAll('.service-item').forEach(item => {
      item.classList.remove('selected');
    });
    
    // Add the new service
    selectedServices.set(service.serviceid, service);
    itemElement.classList.add('selected');
  }
  
  updateSummary();
}

// Update summary sidebar
function updateSummary() {
  const selectedServicesContainer = document.getElementById('selectedServices');
  const totalPrice = document.getElementById('totalPrice');
  const downpaymentPrice = document.getElementById('downpaymentPrice');
  const continueBtn = document.getElementById('continueBtn');

  if (selectedServices.size === 0) {
    selectedServicesContainer.innerHTML = '<p class="empty-selection">No services selected yet</p>';
    totalPrice.textContent = '₱0';
    if (downpaymentPrice) downpaymentPrice.textContent = '₱0';
    continueBtn.disabled = true;
  } else {
    let total = 0;
    let html = '';

    selectedServices.forEach(service => {
      total += parseFloat(service.price);
      html += `
        <div class="selected-service">
          <div class="selected-service-info">
            <h4>${service.servicename}</h4>
            <p class="selected-service-duration">${service.duration || 'N/A'}</p>
          </div>
          <span class="selected-service-price">₱${parseFloat(service.price).toFixed(2)}</span>
        </div>
      `;
    });

    selectedServicesContainer.innerHTML = html;
    totalPrice.textContent = `₱${total.toFixed(2)}`;
    if (downpaymentPrice) {
      const downpayment = total * 0.5;
      downpaymentPrice.textContent = `₱${downpayment.toFixed(2)}`;
    }
    continueBtn.disabled = false;
  }
}


// Handle continue button
// Handle continue button
function handleContinue() {
  if (selectedServices.size === 0) return;
  
  const branchId = new URLSearchParams(window.location.search).get('branch');
  const branchName = document.getElementById('branchName').textContent;
  const branchAddress = document.getElementById('branchAddress').textContent;
  
  // Store selected services and branch info
  const servicesArray = Array.from(selectedServices.values());
  sessionStorage.setItem('selectedServices', JSON.stringify(servicesArray));
  sessionStorage.setItem('selectedBranchId', branchId);
  sessionStorage.setItem('selectedBranchName', branchName);
  
  // Calculate total
  const total = Array.from(selectedServices.values())
    .reduce((sum, s) => sum + parseFloat(s.price), 0);
  sessionStorage.setItem('totalPrice', total.toFixed(2));
  
  // Redirect directly to booking page
  window.location.href = './booking.html';
}


// Error display
function showError(message) {
  const servicesList = document.getElementById('servicesList');
  servicesList.innerHTML = `
    <div class="error-state">
      <p>${message}</p>
      <button onclick="window.history.back()" style="margin-top: 1rem; padding: 0.75rem 1.5rem; background: var(--text-main); color: white; border: none; border-radius: 8px; cursor: pointer;">
        Go Back
      </button>
    </div>
  `;
}