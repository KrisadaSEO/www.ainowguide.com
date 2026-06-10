<?php
declare(strict_types=1);

$view               = $view ?? [];
$record             = $view['record'] ?? [];
$childCategories    = $view['child_categories'] ?? [];
$listings           = $view['listings'] ?? [];
$ancestorCategories = $view['ancestor_categories'] ?? [];

$isPortfolioRoot = ($record['path'] ?? '') === 'portfolio';
?>

<header class="directory-hero">
    <div class="category-hero__label">Directory</div>
    <h1><?= site_e($record['title'] ?? '') ?></h1>
    <?php if (!empty($record['description'])): ?>
    <p class="directory-hero__intro"><?= site_e($record['description']) ?></p>
    <?php endif; ?>
</header>

<?php if ($isPortfolioRoot && !empty($childCategories)): ?>

<?php foreach ($childCategories as $child):
    $domainListings = site_get_dir_listings_for_category($child);
    if (empty($domainListings)) continue;
    $domainCount = count($domainListings);
?>
<details class="portfolio-constellation" open>
    <summary class="portfolio-constellation__toggle">
        <h2 class="portfolio-constellation__name"><?= site_e($child['title'] ?? '') ?></h2>
        <h2 class="portfolio-constellation__label"><span class="portfolio-constellation__count"><?= $domainCount ?></span> CONSTELLATION</h2>
        <svg class="portfolio-constellation__chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </summary>
    <?php if (!empty($child['description'])): ?>
    <p class="portfolio-constellation__desc"><?= site_e($child['description']) ?></p>
    <?php endif; ?>
    <ul class="portfolio-domain-list">
        <?php foreach ($domainListings as $listing): ?>
        <li class="portfolio-domain-list__item">
            <a href="<?= site_e($listing['canonical_url'] ?? '#') ?>" class="portfolio-domain-list__link">
                <?= site_e($listing['title'] ?? '') ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</details>
<?php endforeach; ?>

<?php else: ?>

<?php if (!empty($childCategories)): ?>
<div class="directory-grid" style="margin-bottom: 2.5rem;">
    <?php foreach ($childCategories as $child): ?>
    <div class="directory-card">
        <h2 class="directory-card__title">
            <a href="<?= site_e($child['canonical_url'] ?? '/directory/') ?>"><?= site_e($child['title'] ?? '') ?></a>
        </h2>
        <?php if (!empty($child['description'])): ?>
            <div class="directory-card__desc"><?= site_e($child['description']) ?></div>
        <?php endif; ?>
        <?php
        $subCount     = count(site_get_dir_child_categories($child));
        $listingCount = count(site_get_dir_listings_for_category($child));
        if ($subCount > 0 || $listingCount > 0):
        ?>
        <div class="directory-card__meta">
            <?php if ($subCount > 0): ?><span><?= $subCount ?> sub<?= $subCount === 1 ? 'category' : 'categories' ?></span><?php endif; ?>
            <?php if ($listingCount > 0): ?><span><?= $listingCount ?> <?= $listingCount === 1 ? 'listing' : 'listings' ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($listings)): ?>
<div class="directory-listing-list">
    <?php foreach ($listings as $listing): ?>
    <div class="directory-listing-row">
        <h2 class="directory-listing-row__title">
            <a href="<?= site_e($listing['canonical_url'] ?? '#') ?>"><?= site_e($listing['title'] ?? '') ?></a>
        </h2>
        <?php if (!empty($listing['excerpt']) || !empty($listing['description'])): ?>
            <p class="directory-listing-row__excerpt"><?= site_e($listing['excerpt'] ?? $listing['description'] ?? '') ?></p>
        <?php endif; ?>
        <?php
        $website = (string) ($listing['fields']['website'] ?? '');
        if ($website !== ''):
        ?>
        <div class="directory-listing-row__meta">
            <a href="<?= site_e($website) ?>" rel="noopener noreferrer" target="_blank"><?= site_e(parse_url($website, PHP_URL_HOST) ?: $website) ?></a>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($childCategories) && empty($listings)): ?>
<p class="text-muted">No listings in this category yet.</p>
<?php endif; ?>

<?php endif; ?>
