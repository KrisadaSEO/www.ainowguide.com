<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Downloads ... AI Now Guide Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Protected Downloads</h1>
      <p class="admin-page-desc">Manage gated bundle pages, repo-backed zip delivery, and uploaded assets from one place.</p>
    </div>

    <?php if (!empty($download_success)): ?>
      <div class="admin-alert admin-alert--success" role="alert"><?= e($download_success) ?></div>
    <?php endif; ?>

    <?php if (!empty($download_error)): ?>
      <div class="admin-alert admin-alert--error" role="alert"><?= e($download_error) ?></div>
    <?php endif; ?>

    <section class="admin-panel">
      <div class="admin-panel__intro">
        <div>
          <h2 class="admin-panel__heading">Download Library</h2>
          <p class="admin-panel__desc">Public landing pages live under <code>/download/{slug}/</code>. The real files stay protected behind email-gated access links.</p>
        </div>
        <a href="/admin/downloads/new" class="btn btn--gold admin-downloads__cta">Create Download</a>
      </div>

      <?php if (empty($download_items)): ?>
        <p class="admin-empty">No downloads created yet.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Source</th>
                <th>Claims</th>
                <th>Downloads</th>
                <th class="admin-hide-mobile">Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($download_items as $item): ?>
                <tr>
                  <td>
                    <div class="admin-content-title"><?= e($item['title'] ?? '') ?></div>
                    <div class="admin-table__muted admin-table__mono"><?= e($item['slug'] ?? '') ?></div>
                  </td>
                  <td>
                    <span class="admin-chip admin-chip--<?= ($item['status'] ?? '') === 'published' ? 'green' : 'amber' ?>">
                      <?= e(ucfirst((string) ($item['status'] ?? 'draft'))) ?>
                    </span>
                  </td>
                  <td>
                    <span class="admin-chip admin-chip--<?= !empty($item['source_exists']) ? 'green' : 'danger' ?>">
                      <?= !empty($item['source_exists']) ? 'Ready' : 'Broken' ?>
                    </span>
                    <div class="admin-table__muted admin-table__mono">
                      <?= e((string) ($item['source_kind'] ?? 'repo')) ?> / <?= e((string) ($item['delivery_type'] ?? 'file')) ?>
                    </div>
                  </td>
                  <td><?= (int) ($item['claims'] ?? 0) ?></td>
                  <td><?= (int) ($item['downloads'] ?? 0) ?></td>
                  <td class="admin-table__muted admin-hide-mobile"><?= e((string) ($item['updated_at'] ?? '')) ?></td>
                  <td class="admin-table__actions">
                    <a href="/admin/downloads/<?= e((string) ($item['slug'] ?? '')) ?>" class="btn btn--secondary btn--xs">Edit</a>
                    <?php if (!empty($item['canonical_url'])): ?>
                      <a href="<?= e((string) ($item['canonical_url'] ?? '#')) ?>" class="btn btn--ghost btn--xs" target="_blank" rel="noopener">View</a>
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
