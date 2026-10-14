document.addEventListener('DOMContentLoaded', () => {
  // Sidebar Toggle for Mobile
  const sidebar = document.getElementById('appSidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  if (sidebarToggle && sidebar && sidebarOverlay) {
    const toggleSidebar = () => {
      sidebar.classList.toggle('show');
      sidebarOverlay.classList.toggle('show');
      document.body.classList.toggle('overflow-hidden');
    };

    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', toggleSidebar);
  }

  // Smooth scroll for sidebar links (if using hash links)
  const sidebarLinks = document.querySelectorAll('.app-sidebar .nav-link[href^="#"]');
  sidebarLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const targetId = link.getAttribute('href').slice(1);
      const target = document.getElementById(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        sidebarLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        
        // Close sidebar on mobile after click
        if (window.innerWidth < 992) {
          sidebar.classList.remove('show');
          sidebarOverlay.classList.remove('show');
          document.body.classList.remove('overflow-hidden');
        }
      }
    });
  });

  // Theme Toggle Logic
  const themeToggle = document.querySelector('[data-toggle-theme="navy"]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('theme-navy');
      localStorage.setItem('theme', document.body.classList.contains('theme-navy') ? 'navy' : 'default');
    });
  }

  // Load saved theme
  if (localStorage.getItem('theme') === 'navy') {
    document.body.classList.add('theme-navy');
  }

  // Notification Panel Toggle
  const notifTriggers = document.querySelectorAll('[data-notif-trigger]');
  notifTriggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const wrap = trigger.closest('.notif-wrap');
      const panel = wrap ? wrap.querySelector('.notif-panel') : null;
      if (!panel) return;
      
      const isShown = panel.classList.contains('show');
      // Hide all panels first
      document.querySelectorAll('.notif-panel.show').forEach(p => p.classList.remove('show'));
      
      if (!isShown) {
        panel.classList.add('show');
      }
    });
  });

  // Close panels on click outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.notif-wrap')) {
      document.querySelectorAll('.notif-panel.show').forEach(p => p.classList.remove('show'));
    }
  });

  // Password Visibility Toggle
  document.querySelectorAll('[data-toggle-password]').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.closest('.input-group');
      const input = group ? group.querySelector('input') : null;
      if (!input) return;
      
      const isPw = input.getAttribute('type') === 'password';
      input.setAttribute('type', isPw ? 'text' : 'password');
      const icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
      }
    });
  });

  // Reimbursement Auto-calc
  const qtyInput = document.querySelector('input[name="qty"]');
  const priceInput = document.querySelector('input[name="unit_price"]');
  const amountInput = document.querySelector('input[name="amount"]');
  
  if (qtyInput && priceInput && amountInput) {
    const recalc = () => {
      const q = parseFloat(qtyInput.value) || 0;
      const p = parseFloat(priceInput.value) || 0;
      amountInput.value = (q * p).toFixed(2);
    };
    qtyInput.addEventListener('input', recalc);
    priceInput.addEventListener('input', recalc);
  }
});