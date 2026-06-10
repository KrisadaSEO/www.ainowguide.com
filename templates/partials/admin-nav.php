<?php
$_active = $admin_section ?? '';
?>
<header class="admin-nav">
  <div class="admin-nav__inner">

    <a href="/admin/" class="admin-nav__brand" aria-label="Admin dashboard">
      <span class="admin-nav__brand-site">AI Now Guide</span>
      <span class="admin-nav__brand-label">Admin</span>
    </a>

    <nav class="admin-nav__links" aria-label="Admin navigation">
      <a href="/admin/"
         class="admin-nav__link<?= $_active === 'dashboard' ? ' admin-nav__link--active' : '' ?>">
        Dashboard
      </a>
      <a href="/admin/content"
         class="admin-nav__link<?= $_active === 'content' ? ' admin-nav__link--active' : '' ?>">
        Content
      </a>
      <a href="/admin/downloads"
         class="admin-nav__link<?= $_active === 'downloads' ? ' admin-nav__link--active' : '' ?>">
        Downloads
      </a>
      <a href="/admin/redirects"
         class="admin-nav__link<?= $_active === 'redirects' ? ' admin-nav__link--active' : '' ?>">
        Redirects
      </a>
      <a href="/admin/profile"
         class="admin-nav__link<?= $_active === 'profile' ? ' admin-nav__link--active' : '' ?>">
        Profile
      </a>
      <a href="/admin/push"
         class="admin-nav__link<?= $_active === 'push' ? ' admin-nav__link--active' : '' ?>">
        Push to GitHub
      </a>
    </nav>

    <div class="admin-nav__actions">
      <a href="/" class="admin-nav__site-link" title="Back to site">← Site</a>
      <a href="/admin/logout" class="admin-nav__logout">Log out</a>
    </div>

  </div>
</header>
