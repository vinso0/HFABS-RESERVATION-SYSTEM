// Load branches for footer
document.addEventListener('DOMContentLoaded', function() {
  const branchesList = document.getElementById('branches-list');
  
  if (!branchesList) return;

  // Load branches from API
  async function loadBranches() {
    try {
      const response = await fetch('/HFABS/backend/public/index.php?url=branch/index');
      
      if (!response.ok) {
        throw new Error('Failed to fetch branches');
      }
      
      const branches = await response.json();
      
      branchesList.innerHTML = '';
      
      if (branches.length === 0) {
        branchesList.innerHTML = '<div class="branch-error">No branches available.</div>';
        return;
      }
      
      branches.forEach(branch => {
        const branchItem = createBranchItem(branch);
        branchesList.appendChild(branchItem);
      });
      
    } catch (error) {
      console.error('Error loading branches:', error);
      loadDummyBranches();
    }
  }
  
  // Load dummy branches (for development)
  function loadDummyBranches() {
    const dummyBranches = [
      {
        branch_id: 1,
        branch_name: 'Caloocan Branch',
        branch_location: '102 Caimito Rd., Caloocan City, Unit 1D, Caimito Place'
      },
      {
        branch_id: 2,
        branch_name: 'Quezon City Branch',
        branch_location: '850 Atherton, Quezon City'
      }
    ];
    
    branchesList.innerHTML = '';
    dummyBranches.forEach(branch => {
      const branchItem = createBranchItem(branch);
      branchesList.appendChild(branchItem);
    });
  }
  
  // Create branch item element
  function createBranchItem(branch) {
    const item = document.createElement('div');
    item.className = 'footer-branch-item';
    
    const branchName = document.createElement('h5');
    branchName.textContent = capitalizeFirstLetter(branch.branch_name) + ' Branch';
    
    const branchLocation = document.createElement('p');
    branchLocation.textContent = branch.branch_location;
    
    item.appendChild(branchName);
    item.appendChild(branchLocation);
    
    return item;
  }

  // Capitalize first letter of string
  function capitalizeFirstLetter(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
  }

  // Load branches when the page loads
  loadBranches();
});
