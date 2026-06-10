<?php
declare(strict_types=1);

$view            = $view ?? [];
$record          = $view['record'] ?? [];
$primaryCategory = $view['primary_category'] ?? null;
$relatedArticles = $view['related_articles'] ?? [];
$articleBody     = (string) ($record['body'] ?? '');
$articleCategory = (string) ($record['category_primary'] ?? '');

if ($articleBody !== '' && $articleCategory === 'website-faqs') {
    $articleBody = preg_replace_callback(
        '/(<h2 class="faq-answer-heading">\s*What most people get wrong\s*<\/h2>\s*<p\b[^>]*>)(.*?)(<\/p>\s*<h2 class="faq-answer-heading">\s*Download solution\s*<\/h2>)/is',
        static function (array $matches): string {
            if (str_contains($matches[2], 'id="download"')) {
                return $matches[0];
            }

            return $matches[1] . $matches[2] . '<span id="download" aria-hidden="true"></span>' . $matches[3];
        },
        $articleBody,
        1
    ) ?? $articleBody;
}
?>

<article>
    <!-- Header -->
    <header class="article-header">
        <?php if ($primaryCategory !== null): ?>
            <div class="article-header__eyebrow">
                <a href="<?= site_e($primaryCategory['canonical_url'] ?? '/library/') ?>">
                    <?= site_e($primaryCategory['title'] ?? 'Library') ?>
                </a>
            </div>
        <?php endif; ?>

        <h1><?= site_e($record['title'] ?? '') ?></h1>

        <div class="article-header__meta">
            <?php
            // Resolve authors for byline display
            // contributors config maps slug -> display data
            $siteConfig     = site_app()['site'];
            $contributorsCfg = $siteConfig['contributors'] ?? [];
            $authorSlugs    = array_map('strtolower', (array) ($record['authors'] ?? []));
            if (empty($authorSlugs)) {
                // backward compat: build from legacy author + co_author fields
                $authorSlugs = ['krisada'];
                foreach (array_map('strtolower', (array) ($record['co_author'] ?? [])) as $_s) {
                    if ($_s !== 'krisada') {
                        $authorSlugs[] = $_s;
                    }
                }
            }
            $bylineParts = [];
            foreach ($authorSlugs as $_slug) {
                if ($_slug === 'krisada') {
                    $bylineParts[] = site_e($record['author'] ?? ($contributorsCfg['krisada']['name'] ?? 'Krisada'));
                } elseif (isset($contributorsCfg[$_slug])) {
                    $cfg = $contributorsCfg[$_slug];
                    $label = site_e($cfg['name'] ?? $_slug);
                    $title = ($cfg['type'] ?? '') === 'ai'
                        ? htmlspecialchars($cfg['description'] ?? 'AI contributor', ENT_QUOTES, 'UTF-8')
                        : '';
                    $bylineParts[] = $title
                        ? '<span class="byline-ai-author" title="' . $title . '">' . $label . '</span>'
                        : $label;
                }
            }
            if (!empty($bylineParts)):
        ?>
            <span>By <?= implode(' with ', $bylineParts) ?></span>
        <?php endif; ?>
            <?php if (!empty($record['publish_date'])): ?>
                <span><?= site_e(site_format_date((string) $record['publish_date'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($record['updated_at']) && $record['updated_at'] !== $record['publish_date']): ?>
                <span>Updated <?= site_e(site_format_date((string) $record['updated_at'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($record['reading_time'])): ?>
                <span><?= site_e((string) $record['reading_time']) ?> min read</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($record['audio_summary_url'])): ?>
        <div class="article-audio-wrap">
            <audio controls preload="metadata" class="article-audio">
                <source src="<?= site_e($record['audio_summary_url']) ?>" type="audio/mpeg">
            </audio>
            <p class="article-audio__byline"><em>AI-generated audio summary</em></p>
            <?php if (!empty($record['audio_transcript'])): ?>
            <details class="article-audio__transcript">
                <summary>Read transcript</summary>
                <p><?= site_e($record['audio_transcript']) ?></p>
            </details>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($record['excerpt'])): ?>
            <p class="article-header__excerpt"><?= site_e($record['excerpt']) ?></p>
        <?php endif; ?>
    </header>

    <!-- Body -->
    <?php if ($articleBody !== ''): ?>
        <div class="article-body">
            <?= $articleBody ?>
        </div>
    <?php elseif (!empty($record['intro'])): ?>
        <div class="article-body">
            <p><?= site_e($record['intro']) ?></p>
        </div>
    <?php endif; ?>

    <!-- Email opt-in inline CTA -->
    <?php require site_root_path('templates/partials/email-optin.php'); ?>

    <!-- Guild inline CTA ... shown on content-systems, digital-independence, ai-website-systems, digital-life, visibility-search -->
    <?php
    $guildCat  = (string) ($record['category_primary'] ?? '');
    $guildShow = false;
    foreach (['content-systems', 'digital-independence', 'ai-website-systems', 'digital-life', 'visibility-search'] as $_gc) {
        if ($guildCat === $_gc || str_starts_with($guildCat, $_gc . '/')) {
            $guildShow = true;
            break;
        }
    }
    if ($guildShow): ?>
        <?php require site_root_path('templates/partials/guild-cta.php'); ?>
    <?php endif; ?>

    <!-- Tags -->
    <?php if (!empty($record['tags'])): ?>
        <div style="margin-top: 2rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <?php foreach ($record['tags'] as $tag): ?>
                <span style="font-size: 0.8rem; background: var(--color-panel, #efe5d4); color: var(--color-text-muted, #6d5d50); padding: 0.2rem 0.65rem; border-radius: 999px; font-weight: 500;">
                    <?= site_e($tag) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Author bio -->
    <?php require site_root_path('templates/partials/author-bio.php'); ?>

    <!-- Related articles -->
    <?php if (!empty($relatedArticles)): ?>
        <div class="related-articles">
            <h2>Continue Reading</h2>
            <ul class="related-articles__list">
                <?php foreach ($relatedArticles as $article): ?>
                    <li>
                        <a href="<?= site_e($article['canonical_url'] ?? '#') ?>"><?= site_e($article['title'] ?? '') ?></a>
                        <?php if (!empty($article['excerpt'])): ?>
                            <span class="text-muted" style="display:block; font-size:0.85rem; margin-top:0.2rem;"><?= site_e($article['excerpt']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</article>
