/**
 * wishlist.js — Reusable Wishlist Module
 * Happy Face & Body Spa Reservation System
 *
 * Usage: included in branch-detail.html and customer-home.php
 * Depends on: toast.js (already loaded in both pages)
 */

const WishlistManager = (() => {

  // ── Config ──────────────────────────────────────────────────
  const API_BASE = '/HFABS/backend/public/index.php?url=';

  // Local state: track which items are wishlisted
  let wishlistedServices = new Set(); // branch_service_override_id values
  let wishlistedPackages = new Set(); // package_id values
  let currentBranchId   = null;
  let isLoggedIn        = false;

  // ── Init: load wishlist state from API ──────────────────────
  async function init(branchId, loggedIn) {
    currentBranchId = branchId;
    isLoggedIn      = !!loggedIn;

    if (!isLoggedIn || !branchId) return;

    try {
      const res  = await fetch(`${API_BASE}wishlist/status&branch_id=${branchId}`);
      const data = await res.json();
      if (data.success) {
        wishlistedServices = new Set(data.service_ids.map(Number));
        wishlistedPackages = new Set(data.package_ids.map(Number));
      }
    } catch (err) {
      console.warn('[Wishlist] Could not load wishlist state:', err);
    }

    // Apply active state to any already-rendered buttons
    syncAllButtons();
  }

  // ── Create a wishlist button element ────────────────────────
  function createButton(type, id, extraData = {}) {
    const btn = document.createElement('button');
    btn.className  = 'wishlist-btn' + (isActive(type, id) ? ' active' : '');
    btn.setAttribute('aria-label', isActive(type, id) ? 'Remove from wishlist' : 'Add to wishlist');
    btn.setAttribute('title', isActive(type, id) ? 'Remove from wishlist' : 'Add to wishlist');
    btn.dataset.type = type;
    btn.dataset.id   = id;
    Object.entries(extraData).forEach(([k, v]) => (btn.dataset[k] = v));

    btn.innerHTML = `<i class="${isActive(type, id) ? 'fas' : 'far'} fa-heart"></i>`;

    btn.addEventListener('click', (e) => {
      e.stopPropagation(); // don't bubble to card click handlers
      handleToggle(btn, type, id, extraData);
    });

    return btn;
  }

  // ── Check if an item is currently wishlisted ────────────────
  function isActive(type, id) {
    id = Number(id);
    if (type === 'service') return wishlistedServices.has(id);
    if (type === 'package') return wishlistedPackages.has(id);
    return false;
  }

  // ── Sync all rendered buttons with current state ────────────
  function syncAllButtons() {
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
      const type = btn.dataset.type;
      const id   = Number(btn.dataset.id);
      const active = isActive(type, id);
      setButtonState(btn, active);
    });
  }

  // ── Set visual state of a button ────────────────────────────
  function setButtonState(btn, active) {
    btn.classList.toggle('active', active);
    btn.querySelector('i').className = active ? 'fas fa-heart' : 'far fa-heart';
    btn.setAttribute('aria-label', active ? 'Remove from wishlist' : 'Add to wishlist');
    btn.setAttribute('title',      active ? 'Remove from wishlist' : 'Add to wishlist');
  }

  // ── Handle the toggle AJAX call ─────────────────────────────
  async function handleToggle(btn, type, id, extraData) {
    if (!isLoggedIn) {
      // Prompt login
      if (typeof showToast === 'function') {
        showToast('Please log in to save items to your wishlist.', 'info');
      } else {
        alert('Please log in to save items to your wishlist.');
      }
      return;
    }

    if (!currentBranchId) {
      console.warn('[Wishlist] No branchId set.');
      return;
    }

    // Optimistic UI update
    const willBeActive = !isActive(type, id);
    btn.classList.add('loading');
    setButtonState(btn, willBeActive);

    const payload = {
      branch_id:    currentBranchId,
      wishlist_type: type,
    };

    if (type === 'service') {
      payload.branch_service_override_id = Number(id);
      payload.default_service_id         = Number(extraData.defaultServiceId || 0);
    } else if (type === 'package') {
      payload.package_id = Number(id);
    }

    try {
      const res  = await fetch(`${API_BASE}wishlist/toggle`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload)
      });
      const data = await res.json();

      btn.classList.remove('loading');

      if (data.success) {
        // Update local state
        const numId = Number(id);
        if (type === 'service') {
          data.action === 'added'
            ? wishlistedServices.add(numId)
            : wishlistedServices.delete(numId);
        } else {
          data.action === 'added'
            ? wishlistedPackages.add(numId)
            : wishlistedPackages.delete(numId);
        }

        // Trigger heart pop animation
        btn.querySelector('i').style.animation = 'none';
        void btn.querySelector('i').offsetWidth; // reflow
        btn.querySelector('i').style.animation = '';

        if (typeof showToast === 'function') {
          showToast(data.message, data.action === 'added' ? 'success' : 'info');
        }
      } else {
        // Revert UI on failure
        setButtonState(btn, !willBeActive);
        if (typeof showToast === 'function') {
          showToast(data.error || 'Something went wrong.', 'error');
        }
      }
    } catch (err) {
      btn.classList.remove('loading');
      setButtonState(btn, !willBeActive); // revert
      console.error('[Wishlist] Toggle error:', err);
      if (typeof showToast === 'function') {
        showToast('Network error. Please try again.', 'error');
      }
    }
  }

  // ── Public API ──────────────────────────────────────────────
  return { init, createButton, isActive, syncAllButtons };

})();