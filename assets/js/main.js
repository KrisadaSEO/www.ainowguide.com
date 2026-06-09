/* AINowGuide.com - main.js */

(function () {
  'use strict';

  // Mobile nav toggle
  const header = document.querySelector('.site-header');
  const toggle = document.querySelector('.site-nav-toggle');
  if (header && toggle) {
    toggle.addEventListener('click', function () {
      const open = header.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!header.contains(e.target)) {
        header.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });

    // Close on ESC
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        header.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Card fade-in via IntersectionObserver
  const animatables = document.querySelectorAll(
    '.channel-card, .session-card, .membership-card, .directory-listing-row'
  );

  if (animatables.length && 'IntersectionObserver' in window) {
    const style = document.createElement('style');
    style.textContent = [
      '.js-fade { opacity: 0; transform: translateY(16px); transition: opacity 0.45s ease, transform 0.45s ease; }',
      '.js-fade.is-visible { opacity: 1; transform: translateY(0); }'
    ].join('');
    document.head.appendChild(style);

    animatables.forEach(function (el) { el.classList.add('js-fade'); });

    const obs = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
    );

    animatables.forEach(function (el) { obs.observe(el); });
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
