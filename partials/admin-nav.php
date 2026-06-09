<?php require PARTIALS_PATH . 'head.php'; ?>
<?php $admin_user = admin_current_user(); ?>
<header class="admin-header">
  <div class="admin-header__inner">
    <a href="/admin/dashboard" class="admin-header__brand">
      <span class="admin-header__site">AI Now Guide</span>
      <span class="admin-header__label">Admin</span>
    </a>
    <nav class="admin-nav">
      <a href="/admin/dashboard" class="admin-nav__link<?= ($page_type ?? '') === 'admin-dashboard' ? ' admin-nav__link--active' : '' ?>">Dashboard</a>
      <a href="/admin/content"   class="admin-nav__link<?= ($page_type ?? '') === 'admin-content'   ? ' admin-nav__link--active' : '' ?>">Content</a>
      <a href="/admin/settings"  class="admin-nav__link<?= ($page_type ?? '') === 'admin-settings'  ? ' admin-nav__link--active' : '' ?>">Settings</a>
      <a href="/admin/redirects" class="admin-nav__link<?= ($page_type ?? '') === 'admin-redirects' ? ' admin-nav__link--active' : '' ?>">Redirects</a>
    </nav>
    <div class="admin-header__user">
      <span class="admin-header__username"><?= e($admin_user['displayName'] ?? '') ?></span>
      <a href="/admin/profile" class="admin-nav__link">Profile</a>
      <a href="/admin/logout" class="admin-nav__link">Log out</a>
    </div>
  </div>
</header>
