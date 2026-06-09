<?php if (!empty($breadcrumbs)): ?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <?php foreach ($breadcrumbs as $crumb): ?>
  <div class="breadcrumbs__item">
    <?php if (!empty($crumb['url'])): ?>
      <a href="<?= e($crumb['url']) ?>" class="breadcrumbs__link"><?= e($crumb['label']) ?></a>
    <?php else: ?>
      <span class="breadcrumbs__current"><?= e($crumb['label']) ?></span>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
