<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login ... AI Now Guide</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<?php
// Load site config for navigation
$site = site_load_json_file('config/site.json');
$navigation = $site['navigation'] ?? [];
$currentPath = site_normalize_path($_SERVER['REQUEST_URI'] ?? '/');
?>
<body class="admin-login-body">

<header class="site-header">
  <div class="site-header__inner">
    <div class="site-logo">
      <a href="/">AI Now <span>Guide</span></a>
    </div>
    <nav class="site-nav" aria-label="Primary navigation">
      <ul class="site-nav__list">
        <?php foreach ($navigation as $navItem):
          if (!empty($navItem['hidden'])) continue;
          $navUrl       = (string) ($navItem['url'] ?? '/');
          $navPath      = site_normalize_path($navUrl);
          $isActive     = str_starts_with($currentPath, $navPath === '/' ? '/_never_' : $navPath) || $navPath === $currentPath;
          $classes      = $isActive ? 'is-active' : '';
          $navChildren  = site_navigation_children($navItem);
          $hasDropdown  = count($navChildren['items']) > 0;
          $dropdownAria = trim((string) ($navChildren['aria_label'] ?? ''));
        ?>
        <li class="site-nav__item<?= $hasDropdown ? ' site-nav__item--has-dropdown' : '' ?>">
          <a href="<?= site_e($navUrl) ?>"
             class="<?= $classes ?>"
             <?= $isActive ? 'aria-current="page"' : '' ?>
          ><?= site_e($navItem['label'] ?? '') ?><?php if ($hasDropdown): ?><span class="nav-chevron" aria-hidden="true"></span><?php endif; ?></a>
          <?php if ($hasDropdown): ?>
          <ul class="nav-dropdown"<?= $dropdownAria !== '' ? ' aria-label="' . site_e($dropdownAria) . '"' : '' ?>>
            <?php foreach ($navChildren['items'] as $child): ?>
            <li>
              <a href="<?= site_e($child['url'] ?? '') ?>"><?= site_e($child['label'] ?? '') ?></a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>

<main class="admin-login-wrap">
  <div class="admin-login-card">

    <div class="admin-login-brand">
      <div class="admin-login-brand__site">AI Now Guide</div>
      <div class="admin-login-brand__label">Admin Panel</div>
    </div>

    <?php if (!empty($login_error)): ?>
      <div class="admin-login-error" role="alert"><?= e($login_error) ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/login" class="admin-login-form" autocomplete="on">
      <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">

      <div class="admin-login-field">
        <label for="admin-username" class="admin-login-label">Username</label>
        <input
          type="text"
          id="admin-username"
          name="username"
          placeholder="username"
          required
          autocomplete="username"
          class="admin-login-input"
          autofocus
          value="<?= e($_POST['username'] ?? '') ?>"
        >
      </div>

      <div class="admin-login-field">
        <label for="admin-password" class="admin-login-label">Password</label>
        <input
          type="password"
          id="admin-password"
          name="password"
          placeholder="••••••••"
          required
          autocomplete="current-password"
          class="admin-login-input"
        >
      </div>

      <button type="submit" class="admin-login-btn">Sign In →</button>
    </form>

  </div>
</main>

</body>
</html>
