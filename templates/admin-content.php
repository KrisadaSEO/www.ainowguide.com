<?php require PARTIALS_PATH . 'admin-nav.php'; ?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Content</h1>
      <div class="admin-type-filters">
        <a href="/admin/content" class="admin-chip<?= $filter_type === '' ? ' admin-chip--active' : '' ?>">All</a>
        <?php foreach ($content_types as $type_key => $type_cfg): ?>
        <a href="/admin/content?type=<?= e($type_key) ?>" class="admin-chip<?= $filter_type === $type_key ? ' admin-chip--active' : '' ?>">
          <?= e($type_cfg['label']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!empty($content_items)): ?>
    <div class="admin-content-list">
      <?php foreach ($content_items as $item): ?>
      <div class="admin-content-row">
        <div class="admin-content-row__info">
          <a href="/admin/content/<?= e($item['type']) ?>/<?= e($item['slug']) ?>" class="admin-content-row__title">
            <?= e($item['title'] ?? $item['slug']) ?>
          </a>
          <div class="admin-content-row__meta">
            <span class="admin-chip admin-chip--violet"><?= e($item['type_label'] ?? '') ?></span>
            <?php if (!($item['published'] ?? false)): ?>
            <span class="admin-chip admin-chip--amber">Draft</span>
            <?php endif; ?>
            <span class="admin-chip"><?= e($item['updated_at'] ?? '') ?></span>
          </div>
        </div>
        <div class="admin-content-row__actions">
          <?php if (!empty($item['public_url'])): ?>
          <a href="<?= e($item['public_url']) ?>" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">View &rarr;</a>
          <?php endif; ?>
          <a href="/admin/content/<?= e($item['type']) ?>/<?= e($item['slug']) ?>" class="btn btn--secondary btn--sm">Edit</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-faint" style="font-style:italic;">No content items found.</p>
    <?php endif; ?>

  </div>
</main>
</div>
</body>
</html>
