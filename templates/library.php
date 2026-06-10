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

$glossaryTermCount = count(site_glossary_terms_all());
$libraryChannels   = array_slice(site_app()['published_channels'] ?? [], 0, 3);
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

        <div class="category-section" id="explore">
            <div class="category-section__heading">Explore the Library</div>
            <div class="pillars-grid">
                <div class="pillar-card">
                    <span class="pillar-card__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#CC9933"/><path d="M20 12 C 17 10 12 10 9 11 V 28 C 12 27 17 27 20 29 C 23 27 28 27 31 28 V 11 C 28 10 23 10 20 12 Z" fill="none" stroke="#fffaf4" stroke-width="1.5" stroke-linejoin="round"/><line x1="20" y1="12" x2="20" y2="29" stroke="#fffaf4" stroke-width="1.2"/></svg></span>
                    <h2 class="pillar-card__title"><a href="/glossary/">Glossary</a></h2>
                    <p class="pillar-card__body"><?= $glossaryTermCount ?> plain-language definitions for the build-in-public terms, channels, and AI search concepts used across this site.</p>
                </div>
                <div class="pillar-card">
                    <span class="pillar-card__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#00c9a7"/><rect x="8" y="11" width="24" height="18" rx="2" fill="none" stroke="#fffaf4" stroke-width="1.5"/><path d="M17 16 L 25 20 L 17 24 Z" fill="#fffaf4"/></svg></span>
                    <h2 class="pillar-card__title"><a href="/channels/">Video Channels</a></h2>
                    <p class="pillar-card__body">Build-in-public channels covering site rebuilds, AI search experiments, and platform migrations, recorded session by session.</p>
                </div>
                <div class="pillar-card">
                    <span class="pillar-card__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#b46c30"/><path d="M15 24 C 13.5 18 16 9 20 8.5 C 24 9 26.5 18 25 24" fill="none" stroke="#fffaf4" stroke-width="1.5" stroke-linecap="round"/><line x1="15" y1="24" x2="15" y2="28" stroke="#fffaf4" stroke-width="1.5" stroke-linecap="round"/><line x1="25" y1="24" x2="25" y2="28" stroke="#fffaf4" stroke-width="1.5" stroke-linecap="round"/><line x1="15" y1="28" x2="25" y2="28" stroke="#fffaf4" stroke-width="1.5"/><line x1="16.5" y1="30.5" x2="23.5" y2="30.5" stroke="#fffaf4" stroke-width="1.5"/><line x1="18" y1="33" x2="22" y2="33" stroke="#fffaf4" stroke-width="1.5"/></svg></span>
                    <h2 class="pillar-card__title"><a href="/article/ai-now-guide-faq/">FAQ</a></h2>
                    <p class="pillar-card__body">Common questions about how AI Now Guide is organized, including channels, sessions, and the AI Digital Karma Federation.</p>
                </div>
            </div>
        </div>

        <?php if (!empty($libraryChannels)): ?>
        <div class="category-section" id="channels">
            <div class="category-section__heading">Latest Channels</div>
            <div class="article-strip">
                <?php foreach ($libraryChannels as $channel): ?>
                <div class="article-row">
                    <div class="article-row__body">
                        <h2 class="article-row__title">
                            <a href="<?= site_e($channel['canonical_url'] ?? '#') ?>"><?= site_e($channel['title'] ?? '') ?></a>
                        </h2>
                        <?php if (!empty($channel['description'])): ?>
                            <div class="article-row__excerpt"><?= site_e($channel['description']) ?></div>
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
