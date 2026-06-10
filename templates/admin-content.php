<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Content Editor ... AI Now Guide Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<?php $content_types = admin_content_type_configs(); ?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Content Editor</h1>
      <p class="admin-page-desc">Edit live copy and SEO metadata while preserving each file's existing JSON structure.</p>
    </div>

    <?php if (!empty($content_success)): ?>
      <div class="admin-alert admin-alert--success" role="alert"><?= e($content_success) ?></div>
    <?php endif; ?>

    <?php if (!empty($content_error)): ?>
      <div class="admin-alert admin-alert--error" role="alert"><?= e($content_error) ?></div>
    <?php endif; ?>

    <section class="admin-panel">
      <div class="admin-content-toolbar">
        <div class="admin-content-filters">
          <a href="/admin/content"
             class="admin-filter-pill<?= empty($content_filter) ? ' admin-filter-pill--active' : '' ?>">
            All
          </a>
          <?php foreach ($content_types as $type_slug => $type_config): ?>
            <a href="/admin/content?type=<?= e($type_slug) ?>"
               class="admin-filter-pill<?= ($content_filter ?? '') === $type_slug ? ' admin-filter-pill--active' : '' ?>">
              <?= e($type_config['label']) ?>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="admin-content-toolbar__meta">
          <?= count($content_items ?? []) ?> item<?= count($content_items ?? []) === 1 ? '' : 's' ?>
        </div>
      </div>

      <?php if (empty($content_items)): ?>
        <p class="admin-empty">No content items matched this filter.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Status</th>
                <th class="admin-hide-mobile">Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($content_items as $item): ?>
                <tr>
                  <td>
                    <div class="admin-content-title"><?= e($item['title'] ?? '') ?></div>
                    <div class="admin-table__muted admin-table__mono"><?= e($item['slug'] ?? '') ?></div>
                  </td>
                  <td><?= e($item['type_label'] ?? '') ?></td>
                  <td>
                    <span class="admin-chip admin-chip--<?= in_array($item['status'] ?? '', ['published', 'live'], true) ? 'green' : 'amber' ?>">
                      <?= e(ucfirst((string) ($item['status'] ?? 'draft'))) ?>
                    </span>
                  </td>
                  <td class="admin-table__muted admin-hide-mobile"><?= e($item['updated_at'] ?? '...') ?></td>
                  <td class="admin-table__actions">
                    <a href="/admin/content/<?= e($item['type'] ?? '') ?>/<?= e($item['slug'] ?? '') ?>"
                       class="btn btn--secondary btn--xs">Edit</a>
                    <?php if (!empty($item['public_url'])): ?>
                      <a href="<?= e($item['public_url']) ?>"
                         class="btn btn--ghost btn--xs"
                         target="_blank" rel="noopener">View</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  </div>
</main>

</body>
</html>
