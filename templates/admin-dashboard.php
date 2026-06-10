<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard ... AI Now Guide Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<main class="admin-main">
  <div class="admin-container">

    <?php $current_user = admin_current_user(); ?>
    <div class="admin-page-header">
      <h1 class="admin-page-title">Dashboard</h1>
      <p class="admin-page-desc">
        Signed in as <strong><?= e($current_user['displayName'] ?? $current_user['username'] ?? 'Admin') ?></strong>
        - Content overview and recent activity.
      </p>
    </div>

    <div class="admin-stats-grid">
      <a href="/admin/content?type=article" class="admin-stat-card">
        <div class="admin-stat-card__label">Articles</div>
        <div class="admin-stat-card__num"><?= (int) $dash_counts['articles'] ?></div>
        <div class="admin-stat-card__link">Edit content -></div>
      </a>

      <a href="/admin/content?type=page" class="admin-stat-card">
        <div class="admin-stat-card__label">Pages</div>
        <div class="admin-stat-card__num"><?= (int) $dash_counts['pages'] ?></div>
        <div class="admin-stat-card__link">Edit content -></div>
      </a>

      <a href="/admin/content?type=dir_listing" class="admin-stat-card">
        <div class="admin-stat-card__label">Directory Listings</div>
        <div class="admin-stat-card__num"><?= (int) $dash_counts['dir_listings'] ?></div>
        <div class="admin-stat-card__link">Edit listings -></div>
      </a>

      <a href="/admin/downloads" class="admin-stat-card">
        <div class="admin-stat-card__label">Downloads</div>
        <div class="admin-stat-card__num"><?= (int) $dash_counts['downloads'] ?></div>
        <div class="admin-stat-card__link">Manage protected files -></div>
      </a>

      <a href="/admin/redirects" class="admin-stat-card<?= count($log_entries) > 0 ? ' admin-stat-card--alert' : '' ?>">
        <div class="admin-stat-card__label">Active Redirects</div>
        <div class="admin-stat-card__num"><?= (int) $dash_counts['redirects'] ?></div>
        <div class="admin-stat-card__link">Manage -></div>
      </a>

      <div class="admin-stat-card<?= count($log_entries) > 0 ? ' admin-stat-card--alert' : '' ?>">
        <div class="admin-stat-card__label">404 Errors</div>
        <div class="admin-stat-card__num admin-stat-card__num--red"><?= count($log_entries) ?></div>
        <a href="/admin/redirects" class="admin-stat-card__link">
          <?= count($log_entries) > 0 ? 'Review errors ->' : 'All clear' ?>
        </a>
      </div>
    </div>

    <section class="admin-panel">
      <h2 class="admin-panel__heading">
        Recent 404 Errors
        <?php if (!empty($log_entries)): ?>
          <span class="admin-badge admin-badge--red"><?= count($log_entries) ?></span>
        <?php endif; ?>
      </h2>
      <p class="admin-panel__desc">URLs visitors tried but did not find. Create a redirect from the Redirect Manager.</p>

      <?php if (empty($log_entries)): ?>
        <p class="admin-empty">No 404 errors logged. Clean slate.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>URL</th>
                <th>Hits</th>
                <th class="admin-hide-mobile">Last Hit</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($log_entries as $entry): ?>
                <tr>
                  <td class="admin-table__mono admin-table__url"><?= e($entry['url'] ?? '') ?></td>
                  <td>
                    <span class="admin-chip admin-chip--<?= ($entry['hits'] ?? 0) >= 10 ? 'danger' : (($entry['hits'] ?? 0) >= 3 ? 'amber' : 'muted') ?>">
                      <?= (int) ($entry['hits'] ?? 0) ?>
                    </span>
                  </td>
                  <td class="admin-table__muted admin-hide-mobile"><?= e($entry['last_hit'] ?? '') ?></td>
                  <td>
                    <a href="/admin/redirects" class="btn btn--ghost btn--xs">Fix -></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="admin-panel__footer">
          <a href="/admin/redirects" class="admin-panel__footer-link">View all errors and manage redirects -></a>
        </div>
      <?php endif; ?>
    </section>

    <section class="admin-panel">
      <h2 class="admin-panel__heading">Quick Actions</h2>
      <div class="admin-quick-actions">
        <a href="/admin/content" class="admin-quick-btn">
          <span class="admin-quick-btn__icon">E</span>
          <span class="admin-quick-btn__label">Edit Content</span>
          <span class="admin-quick-btn__desc">Edit articles, pages, and SEO metadata</span>
        </a>
        <a href="/admin/content?type=dir_listing" class="admin-quick-btn">
          <span class="admin-quick-btn__icon">D</span>
          <span class="admin-quick-btn__label">Directory Listings</span>
          <span class="admin-quick-btn__desc">Edit portfolio entries and directory fields</span>
        </a>
        <a href="/admin/downloads" class="admin-quick-btn">
          <span class="admin-quick-btn__icon">D</span>
          <span class="admin-quick-btn__label">Manage Downloads</span>
          <span class="admin-quick-btn__desc">Protect files, bundles, and gated delivery links</span>
        </a>
        <a href="/admin/redirects" class="admin-quick-btn">
          <span class="admin-quick-btn__icon">R</span>
          <span class="admin-quick-btn__label">Manage Redirects</span>
          <span class="admin-quick-btn__desc">Add redirect rules and dismiss 404 errors</span>
        </a>
        <a href="/articles" target="_blank" rel="noopener" class="admin-quick-btn">
          <span class="admin-quick-btn__icon">V</span>
          <span class="admin-quick-btn__label">View Articles</span>
          <span class="admin-quick-btn__desc">Browse published articles on-site</span>
        </a>
        <a href="/admin/profile" class="admin-quick-btn">
          <span class="admin-quick-btn__icon">P</span>
          <span class="admin-quick-btn__label">Profile</span>
          <span class="admin-quick-btn__desc">Change password and account settings</span>
        </a>
      </div>
    </section>

  </div>
</main>

</body>
</html>
