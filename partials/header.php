<?php
$settings    = get_site_settings();
$nav_links   = $settings['navigation']['primary'] ?? [];
$cta         = $settings['navigation']['cta'] ?? [];
$site_name   = $settings['site']['name'] ?? SITE_NAME;
$current_url = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>
<header class="site-header" id="site-header">
  <div class="site-header__inner">

    <a href="/" class="site-header__brand" aria-label="<?= e($site_name) ?> home">
      <span class="site-header__brand-name">AI<span class="site-header__brand-accent">Now</span>Guide</span>
      <span class="site-header__brand-sub">Build in Public</span>
    </a>

    <?php if (!empty($nav_links)): ?>
    <nav id="site-nav" class="site-nav" aria-label="Primary navigation">
      <?php foreach ($nav_links as $link): ?>
        <?php
        $is_active = rtrim($current_url, '/') === rtrim($link['url'] ?? '', '/');
        $class     = 'site-nav__link' . ($is_active ? ' site-nav__link--active' : '');
        ?>
        <a href="<?= e($link['url'] ?? '#') ?>" class="<?= $class ?>"><?= e($link['label'] ?? '') ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <div class="site-header__actions">
      <?php if (!empty($cta['label']) && !empty($cta['url'])): ?>
      <a href="<?= e($cta['url']) ?>" class="btn btn--primary btn--sm"><?= e($cta['label']) ?></a>
      <?php endif; ?>
      <?php if (!empty($nav_links)): ?>
      <button
        class="site-nav-toggle"
        type="button"
        aria-expanded="false"
        aria-controls="site-nav"
        aria-label="Open navigation"
      >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
          <rect x="1" y="3.5" width="16" height="1.5" rx="0.75" fill="currentColor"/>
          <rect x="1" y="8.25" width="16" height="1.5" rx="0.75" fill="currentColor"/>
          <rect x="1" y="13" width="16" height="1.5" rx="0.75" fill="currentColor"/>
        </svg>
        <span class="site-nav-toggle__label" aria-hidden="true">MENU</span>
      </button>
      <?php endif; ?>
    </div>

  </div>
</header>
