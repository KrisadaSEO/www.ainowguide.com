<?php
declare(strict_types=1);

$view              = $view ?? [];
$record            = $view['record'] ?? [];
$childCategories   = $view['child_categories'] ?? [];
$featuredArticles  = $view['featured_articles'] ?? [];
$recentArticles    = $view['recent_articles'] ?? [];
$relatedCategories = $view['related_categories'] ?? [];
?>

<!-- Category hero -->
<header class="category-hero">
    <div class="category-hero__label">Library</div>
    <h1><?= site_e($record['title'] ?? '') ?></h1>
    <?php if (!empty($record['intro'])): ?>
        <p class="category-hero__intro"><?= site_e($record['intro']) ?></p>
    <?php elseif (!empty($record['description'])): ?>
        <p class="category-hero__intro"><?= site_e($record['description']) ?></p>
    <?php endif; ?>
    <?php if (!empty($record['featured_image'])): ?>
        <img src="<?= site_e($record['featured_image']) ?>"
             alt="<?= site_e($record['title'] ?? '') ?>"
             style="max-width:100%; margin-top:1.5rem;">
    <?php endif; ?>
</header>

<!-- Table of contents -->
<?php
$recordPath     = trim((string) ($record['path'] ?? ''), '/');
$tocAllArticles = $recentArticles;

if (!empty($childCategories)) {
    $tocGroups = [];
    foreach ($childCategories as $child) {
        $childPath     = trim((string) ($child['path'] ?? ''), '/');
        $childArticles = array_values(array_filter($tocAllArticles, function ($a) use ($childPath) {
            $ap = trim((string) ($a['category_primary'] ?? ''), '/');
            return $ap === $childPath || str_starts_with($ap, $childPath . '/');
        }));
        if (!empty($childArticles)) {
            $tocGroups[] = ['category' => $child, 'articles' => $childArticles];
        }
    }
    $tocDirect = array_values(array_filter($tocAllArticles, function ($a) use ($recordPath) {
        return trim((string) ($a['category_primary'] ?? ''), '/') === $recordPath;
    }));
} else {
    $tocGroups = [];
    $tocDirect = $tocAllArticles;
}
$tocHasContent = !empty($tocGroups) || !empty($tocDirect);
?>
<?php if ($tocHasContent): ?>
<div class="category-section">
    <div class="category-section__heading">Topics in this section</div>
    <div class="toc-container">
        <?php foreach ($tocGroups as $tocGroup): ?>
        <div class="toc-group">
            <div class="toc-group__heading">
                <a href="<?= site_e($tocGroup['category']['canonical_url'] ?? '/library/') ?>"><?= site_e($tocGroup['category']['title'] ?? '') ?></a>
            </div>
            <ul class="toc-list">
                <?php foreach ($tocGroup['articles'] as $tocArticle): ?>
                <li class="toc-list__item">
                    <a href="<?= site_e($tocArticle['canonical_url'] ?? '#') ?>"><?= site_e($tocArticle['title'] ?? '') ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
        <?php if (!empty($tocDirect)): ?>
        <div class="toc-group">
            <ul class="toc-list">
                <?php foreach ($tocDirect as $tocArticle): ?>
                <li class="toc-list__item">
                    <a href="<?= site_e($tocArticle['canonical_url'] ?? '#') ?>"><?= site_e($tocArticle['title'] ?? '') ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Featured articles -->
<?php if (!empty($featuredArticles)): ?>
<div class="category-section">
    <div class="category-section__heading">Cornerstone reading</div>
    <div class="article-strip">
        <?php foreach ($featuredArticles as $article): ?>
        <div class="article-row">
            <div class="article-row__meta">
                <?= !empty($article['publish_date']) ? site_e(site_format_date((string) $article['publish_date'])) : '' ?>
            </div>
            <div class="article-row__body">
                <span class="featured-badge">Featured</span>
                <h2 class="article-row__title">
                    <a href="<?= site_e($article['canonical_url'] ?? '#') ?>"><?= site_e($article['title'] ?? '') ?></a>
                </h2>
                <?php if (!empty($article['excerpt'])): ?>
                    <div class="article-row__excerpt"><?= site_e($article['excerpt']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Related categories -->
<?php if (!empty($relatedCategories)): ?>
<div class="category-section">
    <div class="category-section__heading">Related topics</div>
    <div class="category-grid">
        <?php foreach ($relatedCategories as $related): ?>
        <div class="category-card">
            <a href="<?= site_e($related['canonical_url'] ?? '/library/') ?>">
                <h2 class="category-card__title"><?= site_e($related['title'] ?? '') ?></h2>
            </a>
            <?php if (!empty($related['description'])): ?>
                <div class="category-card__desc"><?= site_e($related['description']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Empty state -->
<?php if (!$tocHasContent && empty($featuredArticles)): ?>
<div style="padding: 3rem 0; text-align: center; color: var(--color-text-muted);">
    <p>Content for this section is being added. Check back soon.</p>
    <a href="/library/" class="btn btn-outline-dark mt-sm">Browse the Library</a>
</div>
<?php endif; ?>
