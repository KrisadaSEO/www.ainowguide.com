/* nav.js ... hamburger toggle for mobile nav */
(function () {
    'use strict';

    var hamburger = document.querySelector('.nav-hamburger');
    var nav       = document.querySelector('.site-nav');

    if (!hamburger || !nav) return;

    hamburger.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('is-open');
        hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close nav when any link inside it is clicked (mobile UX)
    nav.addEventListener('click', function (e) {
        if (e.target.tagName === 'A') {
            nav.classList.remove('is-open');
            hamburger.setAttribute('aria-expanded', 'false');
        }
    });
}());
