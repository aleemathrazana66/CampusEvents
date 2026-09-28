/**
 * CampusEvents - Main JavaScript Utilities & UI Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initAnimatedCounters();
  initModals();
  initPasswordToggles();
  initStarRatings();
  initCharCounters();
  initSidebarToggle();
  initFlashMessages();
});

/* Mobile Menu */
function initMobileMenu() {
  const toggle = document.querySelector('.mobile-toggle');
  const navLinks = document.querySelector('.nav-links');
  if (toggle && navLinks) {
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('active');
      const icon = toggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-xmark');
      }
    });
  }
}

/* Dashboard Sidebar Toggle */
function initSidebarToggle() {
  const toggleBtn = document.querySelector('.sidebar-toggle-btn');
  const sidebar = document.querySelector('.dashboard-sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });

    // Close when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 1024 && 
          sidebar.classList.contains('active') && 
          !sidebar.contains(e.target) && 
          !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('active');
      }
    });
  }
}

/* Animated Statistics Counter */
function initAnimatedCounters() {
  const counters = document.querySelectorAll('.impact-number[data-target]');
  if (!counters.length) return;

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const target = +entry.target.getAttribute('data-target');
        const duration = 1500;
        const startTime = performance.now();
        const suffix = entry.target.getAttribute('data-suffix') || '';

        function updateCounter(currentTime) {
          const elapsed = currentTime - startTime;
          const progress = Math.min(elapsed / duration, 1);
          // easeOutQuad
          const easeProgress = 1 - (1 - progress) * (1 - progress);
          const currentVal = Math.floor(easeProgress * target);
          entry.target.textContent = currentVal.toLocaleString() + suffix;

          if (progress < 1) {
            requestAnimationFrame(updateCounter);
          } else {
            entry.target.textContent = target.toLocaleString() + suffix;
          }
        }

        requestAnimationFrame(updateCounter);
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.25 });

  counters.forEach(counter => observer.observe(counter));
}

/* Toast Notifications */
function showToast(type, title, message, duration = 4000) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = {
    success: 'fa-solid fa-circle-check',
    error: 'fa-solid fa-circle-xmark',
    warning: 'fa-solid fa-triangle-exclamation',
    info: 'fa-solid fa-circle-info'
  };

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <i class="${icons[type] || icons.info} toast-icon"></i>
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close" type="button" aria-label="Close">&times;</button>
  `;

  container.appendChild(toast);

  const removeToast = () => {
    toast.classList.add('toast-hiding');
    setTimeout(() => toast.remove(), 300);
  };

  toast.querySelector('.toast-close').addEventListener('click', removeToast);
  setTimeout(removeToast, duration);
}

/* Initialize Session Flash Messages */
function initFlashMessages() {
  const flashData = document.getElementById('flash-messages-data');
  if (flashData) {
    try {
      const messages = JSON.parse(flashData.textContent);
      messages.forEach(msg => {
        const title = msg.type.charAt(0).toUpperCase() + msg.type.slice(1);
        showToast(msg.type, title, msg.message);
      });
    } catch (e) {
      // Ignored
    }
  }
}

/* Modal Helper */
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

function initModals() {
  document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal(modal.id);
      }
    });

    const closeBtns = modal.querySelectorAll('[data-dismiss="modal"], .modal-close');
    closeBtns.forEach(btn => {
      btn.addEventListener('click', () => closeModal(modal.id));
    });
  });
}

/* Password Show/Hide */
function initPasswordToggles() {
  document.querySelectorAll('.toggle-password-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.toggle('fa-eye', !isPassword);
          icon.classList.toggle('fa-eye-slash', isPassword);
        }
      }
    });
  });
}

/* Interactive Star Rating */
function initStarRatings() {
  document.querySelectorAll('.star-rating').forEach(group => {
    const inputs = group.querySelectorAll('input[type="radio"]');
    inputs.forEach(input => {
      input.addEventListener('change', () => {
        const label = group.parentElement.querySelector('.rating-value-display');
        if (label) {
          label.textContent = `${input.value} Star${input.value > 1 ? 's' : ''}`;
        }
      });
    });
  });
}

/* Character Counter */
function initCharCounters() {
  document.querySelectorAll('textarea[data-max-chars]').forEach(textarea => {
    const max = parseInt(textarea.getAttribute('data-max-chars'), 10);
    const counterDisplay = document.querySelector(textarea.getAttribute('data-counter-target'));
    
    const update = () => {
      const current = textarea.value.length;
      if (counterDisplay) {
        counterDisplay.textContent = `${current} / ${max}`;
        if (current > max) {
          counterDisplay.style.color = 'var(--danger)';
        } else {
          counterDisplay.style.color = 'var(--muted)';
        }
      }
    };

    textarea.addEventListener('input', update);
    update();
  });
}
