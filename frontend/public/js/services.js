const API_BASE_URL = '/HFABS/backend/public/index.php?url';

let allServices = [];
let allPackages = [];                         // ✅ packages state
let selectedServices = new Map();
let selectedPackage = null;                   // ✅ selected package state
let currentCategory = 'all';

document.addEventListener('DOMContentLoaded', async function () {
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
    loadServices(branchId),
    loadPackages(branchId)    // ✅ load packages in parallel
  ]);

  setupEventListeners();
}

function setupEventListeners() {
  const tabBtns = document.querySelectorAll('.tab-btn');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      tabBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      currentCategory = this.dataset.category;

      // ✅ Route to package view or service filter
      if (currentCategory === 'packages') {
        document.getElementById('categoryTitle').textContent = 'Packages';
        displayPackages(allPackages);
      } else {
        filterServices(currentCategory);
      }
    });
  });

  document.getElementById('continueBtn').addEventListener('click', handleContinue);
}

async function loadBranchInfo(branchId) {
  try {
    const response = await fetch(`${API_BASE_URL}=branch/${branchId}`);
    if (!response.ok) throw new Error('Failed to fetch branch info');
    const branch = await response.json();
    displayBranchInfo(branch);
  } catch (error) {
    console.error('Error loading branch info:', error);
    const dummyBranches = {
      '1': { branchid: 1, branchname: 'Happy Face & Body Spa - Caloocan', location: '102 Caimito Rd., Caloocan City' },
      '2': { branchid: 2, branchname: 'Happy Face & Body Spa - Quezon City', location: '850 Atherton, Quezon City' }
    };
    displayBranchInfo(dummyBranches[branchId] || dummyBranches['1']);
  }
}

function displayBranchInfo(branch) {
  document.getElementById('branchName').textContent = branch.branchname;
  document.getElementById('branchAddress').textContent = branch.location;
}

async function loadServices(branchId) {
  try {
    const response = await fetch(`${API_BASE_URL}=branch/${branchId}/services`);
    if (!response.ok) throw new Error('Failed to fetch services');
    allServices = await response.json();
    displayServices(allServices);
  } catch (error) {
    console.error('Error loading services:', error);
    loadDummyServices(branchId);
  }
}

// ✅ New: load packages for the branch
async function loadPackages(branchId) {
  try {
    const response = await fetch(`${API_BASE_URL}=packages/getPublicPackages/${branchId}`);
    if (!response.ok) throw new Error('Failed to fetch packages');
    const result = await response.json();
    allPackages = result.success ? result.data : [];
  } catch (error) {
    console.error('Error loading packages:', error);
    allPackages = [];
  }
}

function loadDummyServices(branchId) {
  const allDummyServices = [
    { serviceid: 1, servicename: "Hair Spa Treatment", description: "Deep conditioning hair treatment.", price: 1800.00, duration: "60 min", isavailable: 1, category: "Hair Services" },
    { serviceid: 2, servicename: "Hair Rebonding", description: "Permanent hair straightening.", price: 3500.00, duration: "120 min", isavailable: 1, category: "Hair Services" },
    { serviceid: 3, servicename: "Classic Manicure", description: "Basic nail care and polish.", price: 350.00, duration: "30 min", isavailable: 1, category: "Nail Services" },
    { serviceid: 4, servicename: "Gel Pedicure", description: "Long-lasting gel nail treatment.", price: 600.00, duration: "45 min", isavailable: 1, category: "Nail Services" },
    { serviceid: 5, servicename: "Deep Cleansing Facial", description: "Deep pore cleansing facial.", price: 1200.00, duration: "60 min", isavailable: 1, category: "Facial Services" },
    { serviceid: 9, servicename: "Swedish Massage", description: "Relaxing full body massage.", price: 1500.00, duration: "60 min", isavailable: 1, category: "Massage Services" },
    { serviceid: 10, servicename: "Hot Stone Therapy", description: "Therapeutic hot stone massage.", price: 2000.00, duration: "60 min", isavailable: 1, category: "Massage Services" }
  ];
  allServices = branchId === '2' ? allDummyServices.filter(s => [1, 2, 5].includes(s.serviceid)) : allDummyServices;
  displayServices(allServices);
}

function filterServices(category) {
  const categoryTitle = document.getElementById('categoryTitle');
  if (category === 'all') {
    categoryTitle.textContent = 'Featured';
    displayServices(allServices);
  } else {
    categoryTitle.textContent = category;
    displayServices(allServices.filter(s => s.category && s.category.toLowerCase().includes(category.toLowerCase())));
  }
}

function displayServices(services) {
  const servicesList = document.getElementById('servicesList');

  if (services.length === 0) {
    servicesList.innerHTML = '<div class="empty-state">No services available in this category.</div>';
    return;
  }

  servicesList.innerHTML = '';
  services.forEach(service => {
    servicesList.appendChild(createServiceItem(service));
  });
}

function createServiceItem(service) {
  const item = document.createElement('div');
  item.className = 'service-item';
  item.dataset.serviceid = service.serviceid;

  if (selectedServices.has(service.serviceid)) item.classList.add('selected');

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

  if (isAvailable) item.addEventListener('click', () => toggleService(service, item));
  return item;
}

// ✅ New: render package cards inside the services list area
function displayPackages(packages) {
  const servicesList = document.getElementById('servicesList');

  if (!packages.length) {
    servicesList.innerHTML = '<div class="empty-state">No packages available at this branch.</div>';
    return;
  }

  servicesList.innerHTML = '';
  packages.forEach(pkg => {
    servicesList.appendChild(createPackageItem(pkg));
  });
}

// ✅ New: create package item using same structure as service item
function createPackageItem(pkg) {
  const item = document.createElement('div');
  item.className = 'service-item';
  item.dataset.pkgid = pkg.package_id;

  if (selectedPackage && selectedPackage.package_id === pkg.package_id) {
    item.classList.add('selected');
  }

  const isAvailable = true; // Assume packages are always available for now
  if (!isAvailable) {
    item.style.opacity = '0.5';
    item.style.cursor = 'not-allowed';
  }

  const tags = (pkg.included_services || '')
    .split(',').map(s => s.trim()).filter(Boolean)
    .map(s => `<span class="pkg-tag">${s}</span>`).join('');

  item.innerHTML = `
    <div class="service-info">
      <div class="service-header">
        <h3 class="service-name">
          <span class="pkg-badge">Package</span> ${pkg.package_name}
        </h3>
        <span class="service-duration">${pkg.total_duration_minutes} mins</span>
      </div>
      ${pkg.description ? `<p class="service-description">${pkg.description}</p>` : ''}
      <div class="pkg-tags">${tags}</div>
      <p class="service-price">₱${parseFloat(pkg.package_price).toFixed(2)}</p>
    </div>
    <div class="service-action"></div>
  `;

  if (isAvailable) {
    item.addEventListener('click', () => selectPackage(pkg.package_id, item));
  }

  return item;
}

// ✅ New: select/deselect a package (clears any service selection)
function selectPackage(packageId, itemElement = null) {
  const pkg = allPackages.find(p => p.package_id === packageId);
  if (!pkg) return;

  if (selectedPackage && selectedPackage.package_id === packageId) {
    // Deselect
    selectedPackage = null;
    document.querySelectorAll('[data-pkgid]').forEach(el => el.classList.remove('selected'));
  } else {
    // Select this package, clear any service selections
    selectedPackage = pkg;
    selectedServices.clear();
    document.querySelectorAll('.service-item').forEach(el => el.classList.remove('selected'));
    
    if (itemElement) {
      itemElement.classList.add('selected');
    } else {
      document.querySelector(`[data-pkgid="${packageId}"]`)?.classList.add('selected');
    }
  }

  updateSummary();
}

function toggleService(service, itemElement) {
  // ✅ Selecting a service clears any package selection
  if (selectedPackage) {
    selectedPackage = null;
  }

  if (selectedServices.has(service.serviceid)) {
    selectedServices.delete(service.serviceid);
    itemElement.classList.remove('selected');
  } else {
    selectedServices.clear();
    document.querySelectorAll('.service-item').forEach(item => item.classList.remove('selected'));
    selectedServices.set(service.serviceid, service);
    itemElement.classList.add('selected');
  }

  updateSummary();
}

function isLoggedIn() {
  return !!(localStorage.getItem('token') && localStorage.getItem('userData'));
}

function updateSummary() {
  const container = document.getElementById('selectedServices');
  const totalPrice = document.getElementById('totalPrice');
  const downpaymentPrice = document.getElementById('downpaymentPrice');
  const continueBtn = document.getElementById('continueBtn');
  const loginPrompt = document.getElementById('login-prompt');

  // ✅ Handle package selected
  if (selectedPackage) {
    const price = parseFloat(selectedPackage.package_price);
    const tags = (selectedPackage.included_services || '')
      .split(',').map(s => s.trim()).filter(Boolean)
      .map(s => `<span class="pkg-tag">${s}</span>`).join('');

    container.innerHTML = `
      <div class="selected-service">
        <div class="selected-service-info">
          <h4><span class="pkg-badge">Package</span> ${selectedPackage.package_name}</h4>
          <p class="selected-service-duration">${selectedPackage.total_duration_minutes} mins</p>
          <div class="pkg-tags" style="margin-top:6px">${tags}</div>
        </div>
        <span class="selected-service-price">₱${price.toFixed(2)}</span>
      </div>
    `;

    totalPrice.textContent = `₱${price.toFixed(2)}`;
    if (downpaymentPrice) downpaymentPrice.textContent = `₱${(price * 0.5).toFixed(2)}`;
    continueBtn.disabled = !isLoggedIn();
    loginPrompt.style.display = isLoggedIn() ? 'none' : 'block';
    return;
  }

  // Handle service(s) selected
  if (selectedServices.size === 0) {
    container.innerHTML = '<p class="empty-selection">No services selected yet</p>';
    totalPrice.textContent = '₱0';
    if (downpaymentPrice) downpaymentPrice.textContent = '₱0';
    continueBtn.disabled = true;
    loginPrompt.style.display = 'none';
    return;
  }

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

  container.innerHTML = html;
  totalPrice.textContent = `₱${total.toFixed(2)}`;
  if (downpaymentPrice) downpaymentPrice.textContent = `₱${(total * 0.5).toFixed(2)}`;
  continueBtn.disabled = !isLoggedIn();
  loginPrompt.style.display = isLoggedIn() ? 'none' : 'block';
}

function handleContinue() {
  if (!isLoggedIn()) {
    alert('Please log in first to continue with your booking.');
    window.location.href = './customer-login.html';
    return;
  }

  const branchId = new URLSearchParams(window.location.search).get('branch');
  const branchName = document.getElementById('branchName').textContent;

  // ✅ Package booking path
  if (selectedPackage) {
    const packageData = {
      is_package: true,
      package_id: selectedPackage.package_id,
      servicename: selectedPackage.package_name,
      price: selectedPackage.package_price,
      duration: `${selectedPackage.total_duration_minutes} mins`,
      duration_minutes: selectedPackage.total_duration_minutes,
      category: 'Package',
      description: selectedPackage.description || ''
    };

    sessionStorage.setItem('selectedServices', JSON.stringify([packageData]));
    sessionStorage.setItem('selectedBranchId', branchId);
    sessionStorage.setItem('selectedBranchName', branchName);
    sessionStorage.setItem('totalPrice', parseFloat(selectedPackage.package_price).toFixed(2));
    window.location.href = './booking.html';
    return;
  }

  // Existing single-service path
  if (selectedServices.size === 0) return;

  const servicesArray = Array.from(selectedServices.values());
  const total = servicesArray.reduce((sum, s) => sum + parseFloat(s.price), 0);

  sessionStorage.setItem('selectedServices', JSON.stringify(servicesArray));
  sessionStorage.setItem('selectedBranchId', branchId);
  sessionStorage.setItem('selectedBranchName', branchName);
  sessionStorage.setItem('totalPrice', total.toFixed(2));
  window.location.href = './booking.html';
}

function showError(message) {
  document.getElementById('servicesList').innerHTML = `
    <div class="error-state">
      <p>${message}</p>
      <button onclick="window.history.back()" style="margin-top:1rem;padding:0.75rem 1.5rem;background:var(--text-main);color:white;border:none;border-radius:8px;cursor:pointer;">
        Go Back
      </button>
    </div>
  `;
}
