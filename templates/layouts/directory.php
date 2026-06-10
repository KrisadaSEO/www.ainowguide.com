<?php
// Minimal directory template
$view = $view ?? [];
$topCategories = $view['top_level_categories'] ?? [];
$focusCategory = $view['focus_category'] ?? null;
?>
<div>
    <h3>Directory</h3>
    <?php if ($focusCategory): ?>
        <h4><?= htmlspecialchars($focusCategory['title']) ?></h4>
        <p><?= nl2br(htmlspecialchars($focusCategory['description'] ?? '')) ?></p>
    <?php endif; ?>
    <ul>
        <?php foreach ($topCategories as $cat): ?>
            <li><a href="/directory/<?= htmlspecialchars($cat['slug']) ?>/"> <?= htmlspecialchars($cat['title']) ?> </a></li>
        <?php endforeach; ?>
    </ul>
</div>
