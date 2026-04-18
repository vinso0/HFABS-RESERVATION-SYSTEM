// Parallax scroll effect for smoky bubbles
document.addEventListener('DOMContentLoaded', () => {
  const bubbles = document.querySelectorAll('.bubble');

  window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    
    bubbles.forEach((bubble, index) => {
      const speed = 0.15 + (index * 0.05);
      const yPos = scrolled * speed;
      bubble.style.transform = `translateY(${yPos}px)`;
    });
  });
});

// Load branches into the home page grid
async function loadBranchesSection() {
  const grid = document.getElementById('branchesGrid');
  if (!grid) return;

  try {
    const res = await fetch('/HFABS/backend/public/index.php?url=branch/index');
    const branches = await res.json();

    if (!branches.length) {
      grid.innerHTML = '<p class="branches-empty">No branches available.</p>';
      return;
    }

    grid.innerHTML = branches.map(b => `
      <div class="branch-card">
        <div class="branch-card-icon">
          <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="branch-card-body">
          <h3 class="branch-card-name">${b.branchname}</h3>
          <p class="branch-card-location">
              <i class="fas fa-location-dot"></i>
              ${makeMapLink(b.location, { cssClass: 'maps-link', showIcon: false })}
          </p>
          <p class="branch-card-hours">
            <i class="fas fa-clock"></i>
            ${formatTime(b.opening_time)} – ${formatTime(b.closing_time)}
          </p>
          <p class="branch-card-contact">
            <i class="fas fa-phone"></i> ${b.contact_number}
          </p>
        </div>
        <a href="/HFABS/frontend/views/branch-detail.html?id=${b.branchid}"
           class="branch-card-btn">
          View Branch <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `).join('');
  } catch (err) {
    grid.innerHTML = '<p class="branches-empty">Failed to load branches.</p>';
    console.error(err);
  }
}

function formatTime(timeStr) {
  if (!timeStr) return 'N/A';
  const [h, m] = timeStr.split(':');
  const hour = parseInt(h);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const hour12 = hour % 12 || 12;
  return `${hour12}:${m} ${ampm}`;
}

loadBranchesSection();


