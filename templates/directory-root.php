<?php
declare(strict_types=1);

$view           = $view ?? [];
$record         = $view['record'] ?? [];
$topCategories  = $view['top_categories'] ?? [];
?>

<header class="directory-hero">
    <div class="category-hero__label">Directory</div>
    <h1><?= site_e($record['title'] ?? 'Directory') ?></h1>
    <p class="directory-hero__intro">
        <?= site_e($record['description'] ?? '') ?>
    </p>
</header>

<?php if (!empty($topCategories)): ?>
<div class="directory-grid">
    <?php foreach ($topCategories as $category): ?>
    <div class="directory-card">
        <h2 class="directory-card__title">
            <a href="<?= site_e($category['canonical_url'] ?? '/directory/') ?>"><?= site_e($category['title'] ?? '') ?></a>
        </h2>
        <?php if (!empty($category['description'])): ?>
            <div class="directory-card__desc"><?= site_e($category['description']) ?></div>
        <?php endif; ?>
        <?php
        $childCount   = count(site_get_dir_child_categories($category));
        $listingCount = count(site_get_dir_listings_for_category($category));
        if ($childCount > 0 || $listingCount > 0):
        ?>
        <div class="directory-card__meta">
            <?php if ($childCount > 0): ?>
                <span><?= $childCount ?> <?= $childCount === 1 ? 'subcategory' : 'subcategories' ?></span>
            <?php endif; ?>
            <?php if ($listingCount > 0): ?>
                <span><?= $listingCount ?> <?= $listingCount === 1 ? 'listing' : 'listings' ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="directory-empty">
    <p>Directory listings coming soon.</p>
</div>
<?php endif; ?>
