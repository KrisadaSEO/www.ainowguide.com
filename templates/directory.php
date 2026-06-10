<?php
declare(strict_types=1);

$view            = $view ?? [];
$record          = $view['record'] ?? [];
$topCategories   = $view['top_level_categories'] ?? [];
$focusCategory   = $view['focus_category'] ?? null;
$app             = site_app();
?>

<!-- Directory hero -->
<header class="directory-hero">
    <div class="category-hero__label">Site Map</div>
    <h1><?= site_e($record['title'] ?? 'Directory') ?></h1>
    <p class="directory-hero__intro">
        <?= site_e($record['description'] ?? 'A bird\'s-eye view of everything on this site ... topics, categories, and what to read first.') ?>
    </p>
</header>

<?php if ($focusCategory !== null): ?>
    <!-- Focused section view -->
    <div class="category-section">
        <h2 class="category-section__heading"><?= site_e($focusCategory['title'] ?? '') ?></h2>
        <?php if (!empty($focusCategory['intro']) || !empty($focusCategory['description'])): ?>
            <p style="margin-bottom: 1.5rem; color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.65;">
                <?= site_e($focusCategory['intro'] ?? $focusCategory['description'] ?? '') ?>
            </p>
        <?php endif; ?>
        <?php
        $focusChildren  = site_get_child_categories($focusCategory);
        $focusArticles  = site_get_articles_for_category($focusCategory, 8);
        ?>
        <?php if (!empty($focusChildren)): ?>
        <div class="directory-grid" style="margin-bottom: 2rem;">
            <?php foreach ($focusChildren as $child): ?>
            <div class="directory-card">
                <h2 class="directory-card__title">
                    <a href="<?= site_e($child['canonical_url'] ?? '/library/') ?>"><?= site_e($child['title'] ?? '') ?></a>
                </h2>
                <?php if (!empty($child['description'])): ?>
                    <div class="directory-card__desc"><?= site_e($child['description']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($focusArticles)): ?>
        <div class="article-strip">
            <?php foreach ($focusArticles as $article): ?>
            <div class="article-row">
                <div class="article-row__meta"><?= !empty($article['publish_date']) ? site_e(site_format_date((string) $article['publish_date'])) : '' ?></div>
                <div class="article-row__body">
                    <h2 class="article-row__title"><a href="<?= site_e($article['canonical_url'] ?? '#') ?>"><?= site_e($article['title'] ?? '') ?></a></h2>
                    <?php if (!empty($article['excerpt'])): ?>
                        <div class="article-row__excerpt"><?= site_e($article['excerpt']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>

    <!-- Full directory overview -->
    <?php foreach ($topCategories as $category): ?>
    <div class="category-section">
        <h2 class="category-section__heading">
            <a href="/directory/<?= site_e($category['slug'] ?? '') ?>/" style="color: inherit; text-decoration: none;">
                <?= site_e($category['title'] ?? '') ?>
            </a>
        </h2>
        <?php if (!empty($category['description'])): ?>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 1.25rem; line-height: 1.6;">
                <?= site_e($category['description']) ?>
            </p>
        <?php endif; ?>
        <?php
        $children = site_get_child_categories($category);
        $articles = site_get_articles_for_category($category, 3);
        ?>
        <?php if (!empty($children)): ?>
        <div class="directory-grid" style="margin-bottom: 1rem;">
            <?php foreach ($children as $child): ?>
            <div class="directory-card">
                <h2 class="directory-card__title">
                    <a href="<?= site_e($child['canonical_url'] ?? '/library/') ?>"><?= site_e($child['title'] ?? '') ?></a>
                </h2>
                <?php if (!empty($child['description'])): ?>
                    <div class="directory-card__desc"><?= site_e($child['description']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($articles)): ?>
        <ul class="directory-link-list">
            <?php foreach ($articles as $article): ?>
            <li class="directory-link-list__item">
                <a class="directory-link-list__link" href="<?= site_e($article['canonical_url'] ?? '#') ?>"><?= site_e($article['title'] ?? '') ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($topCategories)): ?>
        <p class="text-muted">The directory is being built. Check back soon.</p>
    <?php endif; ?>

<?php endif; ?>
