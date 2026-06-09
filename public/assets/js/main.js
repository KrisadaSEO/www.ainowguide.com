/* AINowGuide.com - main.js */

(function () {
  'use strict';

  // Mobile nav toggle
  const toggle  = document.querySelector('.nav-toggle');
  const mobileNav = document.getElementById('mobile-nav');

  if (toggle && mobileNav) {
    toggle.addEventListener('click', function () {
      const open = mobileNav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true');
    });

    document.addEventListener('click', function (e) {
      if (!toggle.contains(e.target) && !mobileNav.contains(e.target)) {
        mobileNav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        mobileNav.setAttribute('aria-hidden', 'true');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        mobileNav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        mobileNav.setAttribute('aria-hidden', 'true');
      }
    });
  }

  // Card fade-in via IntersectionObserver
  if ('IntersectionObserver' in window) {
    const animatables = document.querySelectorAll('.channel-card, .session-row');

    if (animatables.length) {
      const obs = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.05, rootMargin: '0px 0px -30px 0px' }
      );

      animatables.forEach(function (el) {
        el.classList.add('fade-in');
        obs.observe(el);
      });
    }
  }

  // Admin: git push confirmation
  const gitForm = document.getElementById('admin-git-push-form');
  if (gitForm) {
    gitForm.addEventListener('submit', function (e) {
      if (!confirm('Push all local commits to origin main?')) {
        e.preventDefault();
      }
    });
  }

})();
