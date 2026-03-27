const API_BASE_URL = '/HFABS/backend/public/index.php?url';

let allServices = [];
let allPackages = [];
let allCategories = [];                       // ✅ NEW: holds active branch categories
let selectedServices = new Map();
let selectedPackage = null;
let currentCategory = 'all';
let branchDownpaymentRate = 0.5;

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
    loadCategories(branchId),                 // ✅ NEW: load categories first
    loadServices(branchId),
    loadPackages(branchId)
  ]);

  renderCategoryTabs();                       // ✅ NEW: build tabs after data is ready
  setupEventListeners();
}

// ============================================================
// ✅ NEW: Load active categories for this branch from the DB
// ============================================================
async function loadCategories(branchId) {
  try {
    const response = await fetch(`${API_BASE_URL}=branch/${branchId}/categories`);
    if (!response.ok) throw new Error('Failed to fetch categories');
    const result = await response.json();

    // The endpoint returns a plain array (see BranchController::categories)
    const raw = Array.isArray(result) ? result : (result.data || []);

    // Only keep categories where isactive == 1
    allCategories = raw.filter(cat => parseInt(cat.isactive) === 1);
  } catch (error) {
    console.error('Error loading categories:', error);
    allCategories = [];
  }
}

// ============================================================
// ✅ NEW: Dynamically render category tab buttons from DB data
// ============================================================
function renderCategoryTabs() {
  const tabsContainer = document.getElementById('categoryTabs');
  if (!tabsContainer) return;

  // Clear any static/hardcoded tabs from HTML
  tabsContainer.innerHTML = '';

  // Always add "All" tab first
  const allBtn = document.createElement('button');
  allBtn.className = 'tab-btn active';
  allBtn.dataset.category = 'all';
  allBtn.dataset.categoryId = 'all';
  allBtn.textContent = 'All';
  tabsContainer.appendChild(allBtn);

  // Add one tab per active DB category
  allCategories.forEach(cat => {
    const btn = document.createElement('button');
    btn.className = 'tab-btn';
    // Use the formatted display name (already ucfirst + " Services" from model)
    btn.dataset.category = cat.categoryname;
    btn.dataset.categoryId = cat.default_category_id;  // ✅ use ID for precise matching
    btn.textContent = cat.categoryname;
    tabsContainer.appendChild(btn);
  });

  // Always add "Packages" tab last
  const pkgBtn = document.createElement('button');
  pkgBtn.className = 'tab-btn';
  pkgBtn.dataset.category = 'packages';
  pkgBtn.dataset.categoryId = 'packages';
  pkgBtn.textContent = 'Packages';
  tabsContainer.appendChild(pkgBtn);
}

function setupEventListeners() {
  // Re-query tab buttons since they are now dynamically rendered
  const tabsContainer = document.getElementById('categoryTabs');
  if (!tabsContainer) return;

  // Category toggle button (mobile)
  const categoryToggle = document.getElementById('categoryToggle');
  if (categoryToggle) {
    categoryToggle.addEventListener('click', function () {
      this.classList.toggle('expanded');
      tabsContainer.classList.toggle('expanded');
    });
  }

  tabsContainer.addEventListener('click', function (e) {
    const btn = e.target.closest('.tab-btn');
    if (!btn) return;

    tabsContainer.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    currentCategory = btn.dataset.category;
    const categoryId = btn.dataset.categoryId;

    // Update toggle button text with selected category
    if (categoryToggle) {
      const toggleText = categoryToggle.querySelector('span');
      if (toggleText) {
        toggleText.textContent = btn.textContent;
      }
      // Collapse the categories after selection on mobile
      categoryToggle.classList.remove('expanded');
      tabsContainer.classList.remove('expanded');
    }

    if (currentCategory === 'packages') {
      document.getElementById('categoryTitle').textContent = 'Packages';
      displayPackages(allPackages);
    } else if (currentCategory === 'all') {
      document.getElementById('categoryTitle').textContent = 'Featured';
      displayServices(allServices);
    } else {
      // ✅ Filter by category_id (integer match) — precise, no string fragility
      document.getElementById('categoryTitle').textContent = currentCategory;
      filterServicesByCategoryId(parseInt(categoryId));
    }
  });

  document.getElementById('continueBtn').addEventListener('click', handleContinue);
}

// ============================================================
// ✅ NEW: Filter services by category_id from DB
// ============================================================
function filterServicesByCategoryId(categoryId) {
  const filtered = allServices.filter(s => parseInt(s.category_id) === categoryId);
  displayServices(filtered);
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

  if (branch.down_payment_rate !== undefined && branch.down_payment_rate !== null) {
    branchDownpaymentRate = parseFloat(branch.down_payment_rate);
  }
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
    { serviceid: 1, servicename: "Hair Spa Treatment", description: "Deep conditioning hair treatment.", price: 1800.00, duration: "60 min", isavailable: 1, category: "Hair Services", category_id: 1 },
    { serviceid: 2, servicename: "Hair Rebonding", description: "Permanent hair straightening.", price: 3500.00, duration: "120 min", isavailable: 1, category: "Hair Services", category_id: 1 },
    { serviceid: 3, servicename: "Classic Manicure", description: "Basic nail care and polish.", price: 350.00, duration: "30 min", isavailable: 1, category: "Nail Services", category_id: 2 },
    { serviceid: 4, servicename: "Gel Pedicure", description: "Long-lasting gel nail treatment.", price: 600.00, duration: "45 min", isavailable: 1, category: "Nail Services", category_id: 2 },
    { serviceid: 5, servicename: "Deep Cleansing Facial", description: "Deep pore cleansing facial.", price: 1200.00, duration: "60 min", isavailable: 1, category: "Facial Services", category_id: 3 },
    { serviceid: 9, servicename: "Swedish Massage", description: "Relaxing full body massage.", price: 1500.00, duration: "60 min", isavailable: 1, category: "Massage Services", category_id: 4 },
    { serviceid: 10, servicename: "Hot Stone Therapy", description: "Therapeutic hot stone massage.", price: 2000.00, duration: "60 min", isavailable: 1, category: "Massage Services", category_id: 4 }
  ];
  allServices = branchId === '2' ? allDummyServices.filter(s => [1, 2, 5].includes(s.serviceid)) : allDummyServices;
  displayServices(allServices);
}

// ============================================================
// Legacy filterServices kept for "all" tab fallback
// ============================================================
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

function createPackageItem(pkg) {
  const item = document.createElement('div');
  item.className = 'service-item';
  item.dataset.pkgid = pkg.package_id;

  if (selectedPackage && selectedPackage.package_id === pkg.package_id) {
    item.classList.add('selected');
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

  item.addEventListener('click', () => selectPackage(pkg.package_id, item));
  return item;
}

function selectPackage(packageId, itemElement = null) {
  const pkg = allPackages.find(p => p.package_id === packageId);
  if (!pkg) return;

  if (selectedPackage && selectedPackage.package_id === packageId) {
    selectedPackage = null;
    document.querySelectorAll('[data-pkgid]').forEach(el => el.classList.remove('selected'));
  } else {
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

  const rate = branchDownpaymentRate;
  const ratePercent = Math.round(rate * 100);

  const downpaymentLabel = document.getElementById('downpaymentLabel');
  if (downpaymentLabel) {
    downpaymentLabel.textContent = `Downpayment Amount (${ratePercent}%):`;
  }

  if (selectedPackage) {
    const price = parseFloat(selectedPackage.package_price);
    const downpayment = price * rate;
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
    if (downpaymentPrice) downpaymentPrice.textContent = `₱${downpayment.toFixed(2)}`;
    continueBtn.disabled = !isLoggedIn();
    loginPrompt.style.display = isLoggedIn() ? 'none' : 'block';
    return;
  }

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

  const downpayment = total * rate;
  container.innerHTML = html;
  totalPrice.textContent = `₱${total.toFixed(2)}`;
  if (downpaymentPrice) downpaymentPrice.textContent = `₱${downpayment.toFixed(2)}`;
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
    sessionStorage.setItem('branchDownpaymentRate', branchDownpaymentRate);
    window.location.href = './booking.html';
    return;
  }

  if (selectedServices.size === 0) return;

  const servicesArray = Array.from(selectedServices.values());
  const total = servicesArray.reduce((sum, s) => sum + parseFloat(s.price), 0);

  sessionStorage.setItem('selectedServices', JSON.stringify(servicesArray));
  sessionStorage.setItem('selectedBranchId', branchId);
  sessionStorage.setItem('selectedBranchName', branchName);
  sessionStorage.setItem('totalPrice', total.toFixed(2));
  sessionStorage.setItem('branchDownpaymentRate', branchDownpaymentRate);
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
