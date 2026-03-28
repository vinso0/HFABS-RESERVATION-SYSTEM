document.addEventListener('DOMContentLoaded', () => {
  loadAboutContent();
  loadPolicies();
});

// ── Load About Content ────────────────────────────────────────────────────────
function loadAboutContent() {
  fetch('/HFABS/backend/public/index.php?url=about/getAbout')
    .then(res => res.json())
    .then(({ success, data }) => {
      if (!success || !data) return;
      document.getElementById('aboutTitle').textContent       = data.title       || 'About Happy Face & Body Spa';
      document.getElementById('aboutDescription').textContent = data.description || '';
      document.getElementById('aboutVision').textContent      = data.vision      || '';
      document.getElementById('aboutMission').textContent     = data.mission     || '';
    })
    .catch(err => console.error('Failed to load about content:', err));
}

// ── Load Policies & Build Accordion ──────────────────────────────────────────
function loadPolicies() {
  const accordion = document.getElementById('policiesAccordion');

  fetch('/HFABS/backend/public/index.php?url=about/getPolicies')
    .then(res => res.json())
    .then(({ success, data }) => {
      accordion.innerHTML = '';

      if (!success || !data || data.length === 0) {
        accordion.innerHTML = '<p class="no-policies">No policies available at this time.</p>';
        return;
      }

      data.forEach((policy, index) => {
        const item = document.createElement('div');
        item.className = 'accordion-item';
        item.innerHTML = `
          <button class="accordion-header" aria-expanded="false" data-index="${index}">
            <span class="accordion-title">
              <i class="fas fa-chevron-right accordion-icon"></i>
              ${escapeHtml(policy.title)}
            </span>
          </button>
          <div class="accordion-body" role="region">
            <div class="accordion-content">
              <p>${escapeHtml(policy.content)}</p>
            </div>
          </div>
        `;
        accordion.appendChild(item);
      });

      initAccordion();
    })
    .catch(err => {
      accordion.innerHTML = '<p class="no-policies">Failed to load policies.</p>';
      console.error('Failed to load policies:', err);
    });
}

// ── Accordion Behaviour (one open at a time) ──────────────────────────────────
function initAccordion() {
  const headers = document.querySelectorAll('.accordion-header');

  headers.forEach(header => {
    header.addEventListener('click', () => {
      const isOpen    = header.classList.contains('open');
      const body      = header.nextElementSibling;
      const icon      = header.querySelector('.accordion-icon');

      // Close all
      headers.forEach(h => {
        h.classList.remove('open');
        h.setAttribute('aria-expanded', 'false');
        const b = h.nextElementSibling;
        const i = h.querySelector('.accordion-icon');
        b.style.maxHeight = null;
        b.classList.remove('open');
        i.style.transform = 'rotate(0deg)';
      });

      // Open clicked (if it was closed)
      if (!isOpen) {
        header.classList.add('open');
        header.setAttribute('aria-expanded', 'true');
        body.style.maxHeight = body.scrollHeight + 'px';
        body.classList.add('open');
        icon.style.transform = 'rotate(90deg)';
      }
    });
  });
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(text));
  return div.innerHTML;
}