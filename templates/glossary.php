<?php
declare(strict_types=1);

$view          = $view ?? [];
$record        = $view['record'] ?? [];
$glossary      = $view['glossary'] ?? [];
$allTerms      = $view['glossary_terms'] ?? [];
$subGlossaries = $view['sub_glossaries'] ?? [];

$glossarySlug  = (string) ($glossary['slug'] ?? 'main');
$isMain        = $glossarySlug === 'main';
$pageTitle     = (string) ($glossary['title'] ?? 'Glossary');
$pageDesc      = (string) ($glossary['description'] ?? '');

// Sort A-Z and group by first letter
usort($allTerms, static fn($a, $b) => strcmp(
    strtolower((string) ($a['term'] ?? '')),
    strtolower((string) ($b['term'] ?? ''))
));

$byLetter = [];
foreach ($allTerms as $term) {
    $letter = strtoupper(mb_substr((string) ($term['term'] ?? '#'), 0, 1));
    if (!ctype_alpha($letter)) {
        $letter = '#';
    }
    $byLetter[$letter][] = $term;
}
ksort($byLetter);

$lettersPresent = array_keys($byLetter);
$termCount      = count($allTerms);
?>

<header class="glossary-header">
    <?php if ($isMain): ?>
    <div class="glossary-header__eyebrow">Reference</div>
    <?php else: ?>
    <div class="glossary-header__eyebrow"><a href="/glossary/">Glossary</a></div>
    <?php endif; ?>
    <h1 class="glossary-header__title"><?= site_e($pageTitle) ?></h1>
    <?php if ($pageDesc !== ''): ?>
    <p class="glossary-header__lead"><?= site_e($pageDesc) ?></p>
    <?php endif; ?>
    <div class="glossary-header__meta">
        <span class="glossary-chip"><?= $termCount ?> <?= $termCount === 1 ? 'Term' : 'Terms' ?></span>
    </div>
</header>

<?php if (!empty($subGlossaries)): ?>
<nav class="glossary-collections" aria-label="Sub-glossaries">
    <div class="glossary-collections__heading">Collections</div>
    <div class="glossary-collections__list">
        <?php foreach ($subGlossaries as $sub):
            $subSlug  = (string) ($sub['slug'] ?? '');
            $subCount = count(site_glossary_terms_for($subSlug));
        ?>
        <a href="/glossary/<?= site_e($subSlug) ?>/" class="glossary-collection-card">
            <span class="glossary-collection-card__title"><?= site_e($sub['title'] ?? $subSlug) ?></span>
            <?php if (!empty($sub['description'])): ?>
            <span class="glossary-collection-card__desc"><?= site_e($sub['description']) ?></span>
            <?php endif; ?>
            <span class="glossary-collection-card__count"><?= $subCount ?> <?= $subCount === 1 ? 'term' : 'terms' ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>
<?php endif; ?>

<?php if ($termCount > 0): ?>

<details class="glossary-toc" open>
    <summary class="glossary-toc__toggle">
        <div class="glossary-toc__heading">
            <span class="glossary-toc__label">All Terms</span>
            <span class="glossary-toc__count"><?= $termCount ?></span>
        </div>
        <svg class="glossary-toc__chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </summary>
    <ul class="glossary-toc__list">
        <?php foreach ($allTerms as $tocTerm):
            $tocSlug = (string) ($tocTerm['slug'] ?? '');
            $tocUrl  = $isMain ? '/glossary/' . $tocSlug . '/' : '/glossary/' . $glossarySlug . '/' . $tocSlug . '/';
        ?>
        <li class="glossary-toc__item">
            <a href="<?= site_e($tocUrl) ?>" class="glossary-toc__link"><?= site_e($tocTerm['term'] ?? '') ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
</details>

<nav class="glossary-alpha" aria-label="Jump to letter">
    <?php foreach (range('A', 'Z') as $letter): ?>
        <?php if (in_array($letter, $lettersPresent, true)): ?>
            <a href="#glossary-<?= $letter ?>" class="glossary-alpha__letter glossary-alpha__letter--active"><?= $letter ?></a>
        <?php else: ?>
            <span class="glossary-alpha__letter glossary-alpha__letter--dim"><?= $letter ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (in_array('#', $lettersPresent, true)): ?>
        <a href="#glossary-hash" class="glossary-alpha__letter glossary-alpha__letter--active">#</a>
    <?php endif; ?>
</nav>

<?php foreach ($byLetter as $letter => $terms): ?>
<section class="glossary-section" id="glossary-<?= $letter === '#' ? 'hash' : site_e($letter) ?>">
    <h2 class="glossary-section__letter"><?= site_e($letter) ?></h2>
    <div class="glossary-list">
        <?php foreach ($terms as $term):
            $tSlug   = (string) ($term['slug'] ?? '');
            $termUrl = $isMain ? '/glossary/' . $tSlug . '/' : '/glossary/' . $glossarySlug . '/' . $tSlug . '/';
        ?>
        <a href="<?= site_e($termUrl) ?>" class="glossary-entry">
            <div class="glossary-entry__main">
                <div class="glossary-entry__term"><?= site_e($term['term'] ?? '') ?></div>
                <?php if (!empty($term['short_def'])): ?>
                <div class="glossary-entry__def"><?= site_e($term['short_def']) ?></div>
                <?php endif; ?>
                <?php if (!empty($term['also_known_as'])): ?>
                <div class="glossary-entry__aka">Also: <?= site_e(implode(', ', (array) $term['also_known_as'])) ?></div>
                <?php endif; ?>
            </div>
            <span class="glossary-entry__arrow" aria-hidden="true">&#8594;</span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>

<?php else: ?>
<div class="glossary-empty">
    <p>No terms are published yet. Check back as this glossary grows.</p>
</div>
<?php endif; ?>
