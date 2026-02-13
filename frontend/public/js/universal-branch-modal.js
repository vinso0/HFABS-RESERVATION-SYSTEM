// API Configuration
// Updated base URL to match the backend API location
const API_BASE_URL = '/HFABS/backend/public/index.php?url';

// Universal Branch Modal - Works from any page location
document.addEventListener('DOMContentLoaded', function() {
  console.log('Universal Branch Modal script loaded');
  
  const modal = document.getElementById('branchModal');
  
  // Check if modal exists on this page
  if (!modal) {
    console.log('Modal not found on this page');
    return;
  }
  
  const closeBtn = document.querySelector('.modal-close');
  const serviceButtons = document.querySelectorAll('.service-btn, .cta-btn');
  
  console.log('Number of service buttons found:', serviceButtons.length);
  
  // Detect current directory structure
  const currentPath = window.location.pathname;
  const isInViewsFolder = currentPath.includes('/views/');
  
  // Open modal when any "View Services" button is clicked
  serviceButtons.forEach(button => {
    console.log('Adding click event listener to button:', button.textContent);
    button.addEventListener('click', function() {
      console.log('Button clicked');
      const serviceCard = this.closest('.service-card');
      const serviceTitle = serviceCard ? serviceCard.querySelector('.service-title').textContent : '';
      
      // Store selected service category
      if (serviceTitle) {
        sessionStorage.setItem('selectedServiceCategory', serviceTitle);
      }
      
      openModal();
      loadBranches();
    });
  });
  
  // Close modal when X is clicked
  if (closeBtn) {
    closeBtn.addEventListener('click', window.closeModal);
  }
  
  // Close modal when clicking outside
  window.addEventListener('click', function(event) {
    if (event.target === modal) {
      window.closeModal();
    }
  });
  
  // Close modal on Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modal.style.display === 'block') {
      window.closeModal();
    }
  });
  
  // Make functions globally accessible for inline event handlers
  window.openModal = function() {
    console.log('openModal() called');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    loadBranches();
  };
  
  window.closeModal = function() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
  };
  
  // Load branches from API
  async function loadBranches() {
    const branchList = document.getElementById('branchList');
    
    if (!branchList) return;
    
    try {
      const response = await fetch(`${API_BASE_URL}=branch`);
      
      if (!response.ok) {
        throw new Error('Failed to fetch branches');
      }
      
      const branches = await response.json();
      
      branchList.innerHTML = '';
      
      if (branches.length === 0) {
        branchList.innerHTML = '<div class="branch-error">No branches available.</div>';
        return;
      }
      
      branches.forEach(branch => {
        const branchItem = createBranchItem(branch);
        branchList.appendChild(branchItem);
      });
      
    } catch (error) {
      console.error('Error loading branches:', error);
      loadDummyBranches();
    }
  }
  
  // Load dummy branches (for development)
  function loadDummyBranches() {
    const branchList = document.getElementById('branchList');
    
    if (!branchList) return;
    
    const dummyBranches = [
      {
        branchid: 1,
        branchname: 'Caloocan Branch'
      },
      {
        branchid: 2,
        branchname: 'Quezon City Branch'
      }
    ];
    
    branchList.innerHTML = '';
    dummyBranches.forEach(branch => {
      const branchItem = createBranchItem(branch);
      branchList.appendChild(branchItem);
    });
  }
  
  // Create branch item element
  function createBranchItem(branch) {
    const item = document.createElement('div');
    item.className = 'branch-item';
    item.textContent = branch.branchname;
    
    item.addEventListener('click', function() {
      sessionStorage.setItem('selectedBranchId', branch.branchid);
      sessionStorage.setItem('selectedBranchName', branch.branchname);
      
      // Determine the correct path to services.html based on current location
      let servicesPath;
      
      if (isInViewsFolder) {
        // Currently in views folder (e.g., customer-home.html)
        servicesPath = `services.html?branch=${branch.branchid}`;
      } else {
        // Currently in root folder (index.html)
        servicesPath = `views/services.html?branch=${branch.branchid}`;
      }
      
      window.location.href = servicesPath;
    });
    
    return item;
  }
});
