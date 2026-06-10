<?php
declare(strict_types=1);

$view               = $view ?? [];
$record             = $view['record'] ?? [];
$featuredCategories = $view['featured_categories'] ?? [];
$featuredArticles   = $view['featured_articles'] ?? [];
$isHomepage         = ($record['slug'] ?? '') === 'home';

// Homepage fields (from home.json)
$heroHeadline  = (string) ($record['hero_headline'] ?? '');
$heroSubtext   = (string) ($record['hero_subtext'] ?? '');
$heroCta1Label = (string) ($record['hero_cta_1_label'] ?? 'Explore the Library');
$heroCta1Url   = (string) ($record['hero_cta_1_url'] ?? '/library/');
$heroCta2Label = (string) ($record['hero_cta_2_label'] ?? 'Start Here');
$heroCta2Url   = (string) ($record['hero_cta_2_url'] ?? '/');
?>

<?php if ($isHomepage): ?>

    <!-- ====== HERO ====== -->
    <section class="home-hero">
        <div class="home-hero__inner">
            <span class="home-hero__eyebrow">Online Digital Life</span>
            <h1 class="home-hero__headline">
                <?php if ($heroHeadline !== ''): ?>
                    <span class="headline-breakable"><?= implode('<br>', array_map('htmlspecialchars', array_map('trim', explode("\n", $heroHeadline)))) ?></span>
                <?php else: ?>
                    Build Your <em><a href="https://www.krisada.com/library/digital-life/" style="color:inherit;text-decoration:underline;">Online Digital Life</a></em><br>With Clarity.
                <?php endif; ?>
            </h1>
            <p class="home-hero__sub" style="display: flex; align-items: center; gap: 5px; max-width: 650px; text-align: left;">
                <img src="/assets/img/150-Krisada-v04-Circle.png" alt="Krisada" style="width:100px;height:100px;border-radius:50%;margin-right:10px;margin-left:0;flex-shrink:0;" />
                <span>
                <?php if ($heroSubtext !== ''): ?>
                    <?= site_e($heroSubtext) ?>
                <?php else: ?>
                    I teach how to build digital assets you own ... through websites, content engines, AI leverage, and persistent visibility.
                <?php endif; ?>
                </span>
            </p>
            <div class="home-hero__actions">
                <div class="hero-audio-wrap">
                    <audio controls preload="metadata" class="hero-audio">
                        <source src="/assets/audio/homepage.mp3" type="audio/mpeg">
                    </audio>
                    <p class="hero-audio__byline"><em>AI-generated audio summary</em></p>
                    <?php if (!empty($record['audio_transcript'])): ?>
                    <details class="hero-audio__transcript">
                        <summary>Read transcript</summary>
                        <p><?= site_e($record['audio_transcript']) ?></p>
                    </details>
                    <?php endif; ?>
                </div>
                <a href="<?= site_e($heroCta2Url) ?>" class="btn btn-outline"><?= site_e($heroCta2Label) ?></a>
            </div>
        </div>
    </section>

    <!-- ====== STATS STRIP ====== -->
    <?php
    $statsApp        = site_app();
    $statArticles    = count($statsApp['published_articles']);
    $statTopics      = count(site_get_top_level_categories());
    $statCategories  = count($statsApp['published_categories']);
    $faqCategory     = site_find_category_by_slug('website-faqs');
    $statFaqs        = $faqCategory !== null ? count(site_get_articles_for_category($faqCategory)) : 0;
    ?>
    <div class="home-stats">
        <a href="/library/" class="home-stats__item">
            <span class="home-stats__number"><?= $statArticles ?></span>
            <span class="home-stats__label">Articles</span>
        </a>
        <a href="/library/" class="home-stats__item">
            <span class="home-stats__number"><?= $statTopics ?></span>
            <span class="home-stats__label">Topics</span>
        </a>
        <a href="/directory/" class="home-stats__item">
            <span class="home-stats__number"><?= $statCategories ?></span>
            <span class="home-stats__label">Categories</span>
        </a>
        <a href="/library/website-faqs/" class="home-stats__item">
            <span class="home-stats__number"><?= $statFaqs ?></span>
            <span class="home-stats__label">FAQs</span>
        </a>
    </div>

    <!-- ====== REST OF HOMEPAGE SECTIONS ====== -->
    <div class="page-wrapper">

        <!-- Value proposition pillars -->
        <section class="home-section">
            <div class="home-section__label">What this is about</div>
            <h2 class="home-section__heading">
                What if you could control how your investments performed?
            </h2>
            <p class="home-section__intro">
                Not hope. Not watch. Actually influence the outcome.<br/><br/>
                In traditional markets, that's called insider trading. You're not allowed.<br/><br/>
                But domain websites are a different asset class entirely. Here, the work you put in directly shapes the value. You acquire, you build, you monetize, you exit.<br/><br/>
                You're not betting on the market. You <em>are</em> the market.<br/><br/>
                Krisada.com is where we document the whole thing ... from acquisition to liquidation, live and in public.
                <br/><br/><a href="/article/you-cant-do-this-on-wall-street/">Learn more &rarr;</a>
            </p>
            <div class="pillars-grid">
                <div class="pillar-card">
                    <span class="pillar-card__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#CC9933"/><circle cx="20" cy="20" r="11" fill="none" stroke="#fffaf4" stroke-width="1.5"/><ellipse cx="20" cy="20" rx="5.5" ry="11" fill="none" stroke="#fffaf4" stroke-width="1.2"/><line x1="9" y1="20" x2="31" y2="20" stroke="#fffaf4" stroke-width="1.2"/><path d="M10.5 15.5 Q20 13.5 29.5 15.5" fill="none" stroke="#fffaf4" stroke-width="1"/><path d="M10.5 24.5 Q20 26.5 29.5 24.5" fill="none" stroke="#fffaf4" stroke-width="1"/></svg></span>
                    <h2 class="pillar-card__title">
                        <a href="https://www.krisada.com/article/what-website-ownership-actually-means/" style="color:inherit;text-decoration:underline;">Website Property&nbsp;&nbsp;Ownership</a>
                    </h2>
                    <p class="pillar-card__body">If someone else can take it away, take it down, keep you from selling it, shadow ban you, or censor you in any way...YOU don't own it. You're renting.</p>
                </div>
                <div class="pillar-card">
                    <span class="pillar-card__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#CC9933"/><path d="M7 20 C 10 13 30 13 33 20 C 30 27 10 27 7 20 Z" fill="none" stroke="#fffaf4" stroke-width="1.5"/><circle cx="20" cy="20" r="5" fill="none" stroke="#fffaf4" stroke-width="1.5"/><circle cx="20" cy="20" r="2" fill="#fffaf4"/></svg></span>
                    <h2 class="pillar-card__title">
                        <a href="https://www.krisada.com/library/visibility-search/" style="color:inherit;text-decoration:underline;">AI-Era Visibility</a>
                    </h2>
                    <p class="pillar-card__body">AI discovery is reshaping how content gets found and interpreted. Structure and AI machine-readable context are becoming the new ranking signal.</p>
                </div>
                <div class="pillar-card">
                    <span class="pillar-card__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#CC9933"/><g transform="translate(20,20)"><rect x="-2" y="-13" width="4" height="5" rx="1" fill="#fffaf4" transform="rotate(0)"/><rect x="-2" y="-13" width="4" height="5" rx="1" fill="#fffaf4" transform="rotate(60)"/><rect x="-2" y="-13" width="4" height="5" rx="1" fill="#fffaf4" transform="rotate(120)"/><rect x="-2" y="-13" width="4" height="5" rx="1" fill="#fffaf4" transform="rotate(180)"/><rect x="-2" y="-13" width="4" height="5" rx="1" fill="#fffaf4" transform="rotate(240)"/><rect x="-2" y="-13" width="4" height="5" rx="1" fill="#fffaf4" transform="rotate(300)"/><circle r="8.5" fill="#fffaf4"/><circle r="4" fill="#CC9933"/></g></svg></span>
                    <h2 class="pillar-card__title">
                        <a href="https://www.krisada.com/library/content-systems/" style="color:inherit;text-decoration:underline;">Systems Over Hacks</a>
                    </h2>
                    <p class="pillar-card__body">Most people are still being taught outdated models. Repeatable systems outlast every tactic cycle and build real digital leverage.</p>
                </div>
                <div class="pillar-card">
                    <span class="pillar-card__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#CC9933"/><path d="M15 24 C 13.5 18 16 9 20 8.5 C 24 9 26.5 18 25 24" fill="none" stroke="#fffaf4" stroke-width="1.5" stroke-linecap="round"/><line x1="15" y1="24" x2="15" y2="28" stroke="#fffaf4" stroke-width="1.5" stroke-linecap="round"/><line x1="25" y1="24" x2="25" y2="28" stroke="#fffaf4" stroke-width="1.5" stroke-linecap="round"/><line x1="15" y1="28" x2="25" y2="28" stroke="#fffaf4" stroke-width="1.5"/><line x1="16.5" y1="30.5" x2="23.5" y2="30.5" stroke="#fffaf4" stroke-width="1.5"/><line x1="18" y1="33" x2="22" y2="33" stroke="#fffaf4" stroke-width="1.5"/></svg></span>
                    <h2 class="pillar-card__title">
                        <a href="https://www.krisada.com/library/start-here/" style="color:inherit;text-decoration:underline;">Think, Not Just Click</a>
                    </h2>
                    <p class="pillar-card__body">The goal is teaching you how to think about digital strategy ... not a checklist of buttons. Long-term authority comes from understanding the machine.</p>
                </div>
            </div>
        </section>

        <!-- Featured categories / learning paths -->
        <?php if (!empty($featuredCategories)): ?>
        <section class="home-section">
            <div class="home-section__label">Learning Paths</div>
            <h2 class="home-section__heading">Where do you want to go?</h2>
            <p class="home-section__intro">The library is organized into durable themes. Pick a path and go deep.</p>
            <div class="category-grid">
                <?php foreach ($featuredCategories as $cat): ?>
                <div class="category-card">
                    <a href="<?= site_e($cat['canonical_url'] ?? '/library/') ?>">
                        <h2 class="category-card__title"><?= site_e($cat['title'] ?? '') ?></h2>
                    </a>
                    <?php if (!empty($cat['description'])): ?>
                        <div class="category-card__desc"><?= site_e($cat['description']) ?></div>
                    <?php endif; ?>
                    <a href="<?= site_e($cat['canonical_url'] ?? '/library/') ?>" class="category-card__link">Explore &rarr;</a>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="section-cta">
                <a href="/library/" class="btn btn-outline-dark">Browse the full library &rarr;</a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Fallback: show all top-level categories if no featured ones -->
        <?php
        $topCategories = site_get_top_level_categories();
        if (empty($featuredCategories) && !empty($topCategories)):
        ?>
        <section class="home-section">
            <div class="home-section__label">Learning Paths</div>
            <h2 class="home-section__heading">Explore the Library</h2>
            <p class="home-section__intro">The library is organized into durable themes. Pick a path and go deep.</p>
            <div class="category-grid">
                <?php foreach (array_slice($topCategories, 0, 6) as $cat): ?>
                <div class="category-card">
                    <a href="<?= site_e($cat['canonical_url'] ?? '/library/') ?>">
                        <h2 class="category-card__title"><?= site_e($cat['title'] ?? '') ?></h2>
                    </a>
                    <?php if (!empty($cat['description'])): ?>
                        <div class="category-card__desc"><?= site_e($cat['description']) ?></div>
                    <?php endif; ?>
                    <a href="<?= site_e($cat['canonical_url'] ?? '/library/') ?>" class="category-card__link">Explore &rarr;</a>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="section-cta">
                <a href="/library/" class="btn btn-outline-dark">See all topics &rarr;</a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Latest / cornerstone content -->
        <?php
        $articlesToShow = !empty($featuredArticles)
            ? $featuredArticles
            : array_slice(site_app()['published_articles'] ?? [], 0, 4);
        ?>
        <?php if (!empty($articlesToShow)): ?>
        <section class="home-section">
            <div class="home-section__label">From the Library</div>
            <h2 class="home-section__heading">Start reading</h2>
            <div class="article-strip">
                <?php foreach ($articlesToShow as $article): ?>
                <div class="article-row">
                    <div class="article-row__meta">
                        <?php if (!empty($article['publish_date'])): ?>
                            <?= site_e(site_format_date((string) $article['publish_date'])) ?>
                        <?php endif; ?>
                    </div>
                    <div class="article-row__body">
                        <?php if (!empty($article['is_featured'])): ?>
                            <span class="featured-badge">Featured</span>
                        <?php endif; ?>
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
        </section>
        <?php endif; ?>

        <!-- Tier 4 membership teaser -->
        <div class="tier4-teaser">
            <p><strong>Something else is coming.</strong> Inside Krisada will be a low-cost monthly membership for people building owned digital assets &mdash; monthly strategy notes, a private community, and early access to new guides. No launch date yet. <a href="/contact/?intent=guild-waitlist">Join the early-interest list</a> if you want to know when it opens.</p>
        </div>

        <!-- Proof / CTA banner -->
        <div class="proof-banner">
            <h2>An Experienced SEO Specialist Documenting a Real SEO™ System</h2>
            <p>
                Years of SEO, niche sites, hosting, and digital asset building ... now rebuilt as a structured model for the AI era.
                The portfolio is live. The system is open. The next chapter is being written in public.
            </p>
            <a href="/library/" class="btn btn-primary">Learn the System</a>
        </div>

    </div><!-- /.page-wrapper -->

<?php else: ?>

    <!-- ====== STANDARD PAGE ====== -->
    <header class="category-hero">
        <?php if (!empty($record['hero_headline'])): ?>
            <div class="category-hero__label">Library</div>
            <h1><?= $record['hero_headline'] ?></h1>
        <?php elseif (!empty($record['title'])): ?>
            <h1><?= site_e($record['title']) ?></h1>
        <?php endif; ?>
        <?php if (!empty($record['hero_subtext'])): ?>
            <p class="category-hero__intro"><?= site_e($record['hero_subtext']) ?></p>
        <?php elseif (!empty($record['intro'])): ?>
            <p class="category-hero__intro"><?= site_e($record['intro']) ?></p>
        <?php elseif (!empty($record['description'])): ?>
            <p class="category-hero__intro"><?= site_e($record['description']) ?></p>
        <?php endif; ?>
    </header>


    <?php if (!empty($record['body'])): ?>
        <div class="article-body"><?= $record['body'] ?></div>
    <?php endif; ?>

    <?php if (!empty($record['blocks']) && is_array($record['blocks'])): ?>
        <div class="article-body">
            <?php $blocks = $record['blocks']; include site_root_path('templates/partials/render-blocks.php'); ?>
        </div>
    <?php endif; ?>

    <?php require site_root_path('templates/partials/author-bio.php'); ?>

    <?php if (!empty($featuredCategories)): ?>
        <div class="category-section">
            <div class="category-section__heading">Browse by topic</div>
            <div class="category-grid">
                <?php foreach ($featuredCategories as $cat): ?>
                <div class="category-card">
                    <a href="<?= site_e($cat['canonical_url'] ?? '/library/') ?>">
                        <h2 class="category-card__title"><?= site_e($cat['title'] ?? '') ?></h2>
                    </a>
                    <?php if (!empty($cat['description'])): ?>
                        <div class="category-card__desc"><?= site_e($cat['description']) ?></div>
                    <?php endif; ?>
                    <a href="<?= site_e($cat['canonical_url'] ?? '/library/') ?>" class="category-card__link">Explore &rarr;</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($featuredArticles)): ?>
        <div class="category-section">
            <div class="category-section__heading">Featured articles</div>
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

<?php endif; ?>
