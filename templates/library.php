<?php
declare(strict_types=1);

$view               = $view ?? [];
$record             = $view['record'] ?? [];
$featuredCategories = $view['featured_categories'] ?? [];
$featuredArticles   = $view['featured_articles'] ?? [];

$heroArticleSlug = trim((string) ($record['hero_article'] ?? ''));
$heroArticle     = $heroArticleSlug !== '' ? site_find_article_by_slug($heroArticleSlug) : null;
if ($heroArticle !== null && !site_is_published($heroArticle)) {
    $heroArticle = null;
}

$allArticles = site_app()['published_articles'] ?? [];
?>

<header class="category-hero">
    <div class="category-hero__label">Library</div>
    <h1><?= !empty($record['hero_headline']) ? site_e($record['hero_headline']) : site_e($record['title'] ?? 'The Library') ?></h1>
    <?php if (!empty($record['hero_subtext'])): ?>
        <p class="category-hero__intro"><?= site_e($record['hero_subtext']) ?></p>
    <?php elseif (!empty($record['description'])): ?>
        <p class="category-hero__intro"><?= site_e($record['description']) ?></p>
    <?php endif; ?>
</header>

<?php if (!empty($record['body'])): ?>
    <div class="article-body"><?= $record['body'] ?></div>
<?php endif; ?>

<div class="library-layout">

    <div class="library-layout__main">

        <?php if ($heroArticle !== null): ?>
        <div class="library-hero-article">
            <?php if (!empty($heroArticle['is_featured'])): ?><span class="featured-badge">Featured</span><?php endif; ?>
            <h2 class="library-hero-article__title">
                <a href="<?= site_e($heroArticle['canonical_url'] ?? '#') ?>"><?= site_e($heroArticle['title'] ?? '') ?></a>
            </h2>
            <?php if (!empty($heroArticle['excerpt'])): ?>
                <p class="library-hero-article__excerpt"><?= site_e($heroArticle['excerpt']) ?></p>
            <?php endif; ?>
            <a href="<?= site_e($heroArticle['canonical_url'] ?? '#') ?>" class="library-hero-article__cta">Read &rarr;</a>
        </div>
        <?php endif; ?>

        <?php if (!empty($allArticles)): ?>
        <details class="library-toc" open>
            <summary class="library-toc__toggle">All Articles (<?= count($allArticles) ?>)</summary>
            <div class="library-toc__body">
                <ul class="library-toc__list">
                    <?php foreach ($allArticles as $a): ?>
                    <li><a href="<?= site_e($a['canonical_url'] ?? '#') ?>"><?= site_e($a['title'] ?? '') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </details>
        <?php endif; ?>

        <?php if (!empty($featuredArticles)): ?>
        <div class="category-section" id="featured-articles">
            <div class="category-section__heading">Featured Articles</div>
            <div class="article-strip">
                <?php foreach ($featuredArticles as $article): ?>
                <div class="article-row">
                    <div class="article-row__meta"><?= !empty($article['publish_date']) ? site_e(site_format_date((string) $article['publish_date'])) : '' ?></div>
                    <div class="article-row__body">
                        <?php if (!empty($article['is_featured'])): ?><span class="featured-badge">Featured</span><?php endif; ?>
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

    </div><!-- /.library-layout__main -->

    <?php if (!empty($featuredCategories)): ?>
    <aside class="library-layout__topics">
        <div class="library-topics-panel">
            <div class="library-topics-panel__heading">Browse by Topic</div>
            <ul class="library-topics-panel__list">
                <?php foreach ($featuredCategories as $cat): ?>
                <li>
                    <a href="<?= site_e($cat['canonical_url'] ?? '/library/') ?>"><?= site_e($cat['title'] ?? '') ?></a>
                    <?php if (!empty($cat['description'])): ?>
                        <span class="library-topics-panel__desc"><?= site_e($cat['description']) ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
    <?php endif; ?>

</div><!-- /.library-layout -->
