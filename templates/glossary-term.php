<?php
declare(strict_types=1);

$view         = $view ?? [];
$record       = $view['record'] ?? [];
$glossary     = $view['glossary'] ?? [];
$relatedTerms = $view['related_terms'] ?? [];

$glossarySlug = (string) ($record['glossary'] ?? 'main');
$isMain       = $glossarySlug === 'main';
$glossaryUrl  = $isMain ? '/glossary/' : '/glossary/' . $glossarySlug . '/';
$glossaryTitle = (string) ($glossary['title'] ?? 'Glossary');

$term        = (string) ($record['term'] ?? '');
$shortDef    = (string) ($record['short_def'] ?? '');
$definition  = (string) ($record['definition'] ?? '');
$example     = (string) ($record['example'] ?? '');
$nuance      = (string) ($record['nuance'] ?? '');
$alsoKnownAs = (array) ($record['also_known_as'] ?? []);
$images      = (array) ($record['images'] ?? []);
?>

<header class="glossary-term-header">
    <div class="glossary-term-header__eyebrow">
        <a href="<?= site_e($glossaryUrl) ?>"><?= site_e($glossaryTitle) ?></a>
    </div>
    <h1 class="glossary-term-header__term"><?= site_e($term) ?></h1>
    <?php if (!empty($alsoKnownAs)): ?>
    <p class="glossary-term-header__aka">Also: <?= site_e(implode(', ', $alsoKnownAs)) ?></p>
    <?php endif; ?>
</header>

<?php if ($shortDef !== ''): ?>
<div class="glossary-callout">
    <p><?= site_e($shortDef) ?></p>
</div>
<?php endif; ?>

<?php if ($definition !== ''): ?>
<section class="glossary-body-section">
    <h2 class="glossary-body-section__label">Definition of &ldquo;<?= site_e($term) ?>&rdquo;</h2>
    <div class="glossary-body-section__body">
        <?php foreach (array_filter(array_map('trim', explode("\n\n", $definition))) as $para): ?>
        <p><?= $para ?></p>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($images)): ?>
<div class="glossary-term-images">
    <?php foreach ($images as $img):
        $imgSrc     = (string) ($img['src'] ?? '');
        $imgAlt     = (string) ($img['alt'] ?? '');
        $imgCaption = (string) ($img['caption'] ?? '');
        if ($imgSrc === '') continue;
    ?>
    <figure class="glossary-term-figure">
        <img src="<?= site_e($imgSrc) ?>" alt="<?= site_e($imgAlt) ?>" loading="lazy" class="glossary-term-figure__img">
        <?php if ($imgCaption !== ''): ?>
        <figcaption class="glossary-term-figure__caption"><?= site_e($imgCaption) ?></figcaption>
        <?php endif; ?>
    </figure>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($example !== ''): ?>
<section class="glossary-body-section">
    <h2 class="glossary-body-section__label glossary-body-section__label--accent">&ldquo;<?= site_e($term) ?>&rdquo; In Practice</h2>
    <div class="glossary-body-section__body">
        <?php foreach (array_filter(array_map('trim', explode("\n\n", $example))) as $para): ?>
        <p><?= $para ?></p>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($nuance !== ''): ?>
<section class="glossary-body-section">
    <h2 class="glossary-body-section__label">Worth Knowing About &ldquo;<?= site_e($term) ?>&rdquo;</h2>
    <div class="glossary-body-section__body glossary-body-section__body--nuance">
        <?php foreach (array_filter(array_map('trim', explode("\n\n", $nuance))) as $para): ?>
        <p><?= $para ?></p>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($relatedTerms)): ?>
<section class="glossary-body-section">
    <h2 class="glossary-body-section__label">Related Terms</h2>
    <ul class="glossary-related-list">
        <?php foreach ($relatedTerms as $rt):
            $rtSlug = (string) ($rt['slug'] ?? '');
            $rtGlossary = (string) ($rt['glossary'] ?? 'main');
            $rtUrl = $rtGlossary === 'main' ? '/glossary/' . $rtSlug . '/' : '/glossary/' . $rtGlossary . '/' . $rtSlug . '/';
        ?>
        <li>
            <a href="<?= site_e($rtUrl) ?>" class="glossary-related-link">
                <span class="glossary-related-link__name"><?= site_e($rt['term'] ?? '') ?></span>
                <?php if (!empty($rt['short_def'])): ?>
                <span class="glossary-related-link__def"><?= site_e(mb_substr((string) $rt['short_def'], 0, 90)) ?><?= mb_strlen((string) $rt['short_def']) > 90 ? '...' : '' ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php require site_root_path('templates/partials/author-bio.php'); ?>

<div class="glossary-term-back">
    <a href="<?= site_e($glossaryUrl) ?>" class="glossary-back-link">&#8592; Back to <?= site_e($glossaryTitle) ?></a>
</div>
