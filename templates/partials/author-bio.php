<?php
declare(strict_types=1);

// Resolve author slugs: prefer 'authors' array, fall back to legacy 'author' string
if (!empty($record['authors']) && is_array($record['authors'])) {
    $_author_slugs = array_map('strtolower', $record['authors']);
} elseif (!empty($record['author']) && is_string($record['author'])) {
    $_author_slugs = [strtolower(trim($record['author']))];
} else {
    $_author_slugs = [];
}

$_authors = [];
foreach ($_author_slugs as $_slug) {
    $_a = site_get_author($_slug);
    if ($_a !== null) {
        $_authors[] = $_a;
    }
}

if (empty($_authors)) return;
$_label = count($_authors) > 1 ? 'About the Authors' : 'About the Author';
?>
<div class="author-bio-group">
<p class="author-bio-group__eyebrow"><?= site_e($_label) ?></p>
<?php foreach ($_authors as $_author): ?>
<div class="author-bio">
    <?php if (!empty($_author['avatar'])): ?>
    <img
        src="<?= site_e($_author['avatar']) ?>"
        alt="<?= site_e($_author['name'] ?? '') ?>"
        class="author-bio__avatar"
        loading="lazy"
    >
    <?php endif; ?>
    <div class="author-bio__content">
        <div class="author-bio__header">
            <?php if (!empty($_author['url'])): ?>
            <a href="<?= site_e($_author['url']) ?>" class="author-bio__name"><?= site_e($_author['name'] ?? '') ?></a>
            <?php else: ?>
            <span class="author-bio__name"><?= site_e($_author['name'] ?? '') ?></span>
            <?php endif; ?>
            <?php if (!empty($_author['role'])): ?>
            <span class="author-bio__role"><?= site_e($_author['role']) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($_author['bio'])): ?>
        <p class="author-bio__bio"><?= site_e($_author['bio']) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
