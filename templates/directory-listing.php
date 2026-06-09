<?php
declare(strict_types=1);

$view           = $view ?? [];
$record         = $view['record'] ?? [];
$schema         = $view['dir_schema'] ?? [];
$fields         = (array) ($record['fields'] ?? []);
$schemaFields   = (array) ($schema['fields'] ?? []);
$parentCategory = $view['parent_category'] ?? null;
$websiteUrl     = trim((string) ($fields['website'] ?? ''));
$websiteLabel   = $websiteUrl !== '' ? (string) (parse_url($websiteUrl, PHP_URL_HOST) ?: $websiteUrl) : '';
$elevatorPitch  = trim((string) ($fields['elevator_pitch'] ?? ''));
$heroFacts      = array_values(array_filter([
    ['key' => 'status', 'label' => 'Status', 'value' => trim((string) ($fields['status'] ?? ''))],
    ['key' => 'sale-channel', 'label' => 'Marketplace', 'value' => trim((string) ($fields['sale_channel'] ?? ''))],
    ['key' => 'asking-price', 'label' => 'Asking Price', 'value' => trim((string) ($fields['asking_price'] ?? ''))],
    ['key' => 'tier', 'label' => 'Tier', 'value' => trim((string) ($fields['tier'] ?? ''))],
    ['key' => 'monetization-status', 'label' => 'Current Monetization', 'value' => trim((string) ($fields['monetization_status'] ?? ''))],
], static fn(array $fact): bool => $fact['value'] !== ''));
?>

<article class="directory-listing">

    <header class="directory-listing__header">
        <div class="directory-listing__hero-topline">
            <div class="category-hero__label"><?= site_e($schema['label'] ?? 'Directory') ?></div>
            <?php if ($parentCategory !== null): ?>
            <a class="directory-listing__parent-link" href="<?= site_e((string) ($parentCategory['canonical_url'] ?? '/directory/')) ?>">
                <?= site_e((string) ($parentCategory['title'] ?? 'Back to Directory')) ?>
            </a>
            <?php endif; ?>
        </div>

        <h1 class="directory-listing__title"><?= site_e($record['title'] ?? '') ?></h1>

        <div class="directory-listing__hero-grid">
            <div class="directory-listing__hero-copy">
                <?php if (!empty($record['excerpt']) || !empty($record['description'])): ?>
                <p class="directory-listing__intro"><?= site_e($record['excerpt'] ?? $record['description'] ?? '') ?></p>
                <?php endif; ?>

                <?php if ($elevatorPitch !== ''): ?>
                <div class="directory-listing__elevator">
                    <div class="directory-listing__elevator-label">10-Second Pitch</div>
                    <p class="directory-listing__elevator-copy"><?= nl2br(site_e($elevatorPitch)) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($websiteUrl !== ''): ?>
                <a class="directory-listing__primary-link" href="<?= site_e($websiteUrl) ?>" rel="noopener noreferrer" target="_blank">
                    Visit <?= site_e($websiteLabel) ?>
                </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($heroFacts)): ?>
            <aside class="directory-listing__hero-panel">
                <div class="directory-listing__hero-panel-label">Reference Snapshot</div>
                <dl class="directory-listing__hero-facts">
                    <?php foreach ($heroFacts as $fact): ?>
                    <div class="directory-listing__hero-fact directory-listing__hero-fact--<?= site_e((string) ($fact['key'] ?? 'detail')) ?>">
                        <dt><?= site_e($fact['label']) ?></dt>
                        <dd><?= site_e($fact['value']) ?></dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
            </aside>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($schemaFields)): ?>
    <section class="directory-listing__fields-section">
        <h2 class="directory-listing__section-heading">Property Details</h2>
        <div class="directory-listing__fields">
            <?php foreach ($schemaFields as $fieldDef): ?>
            <?php
            $key    = (string) ($fieldDef['key'] ?? '');
            $label  = (string) ($fieldDef['label'] ?? $key);
            $type   = (string) ($fieldDef['type'] ?? 'text');
            $public = ($fieldDef['public'] ?? true) !== false;
            $value  = (string) ($fields[$key] ?? '');
            if (!$public || $value === '') continue;
            $fieldClasses = ['directory-listing__field'];
            if ($type === 'textarea') {
                $fieldClasses[] = 'directory-listing__field--prose';
            }
            ?>
            <div class="<?= site_e(implode(' ', $fieldClasses)) ?>">
                <span class="directory-listing__field-label"><?= site_e($label) ?></span>
                <span class="directory-listing__field-sep" aria-hidden="true"></span>
                <?php if ($type === 'url'): ?>
                    <a class="directory-listing__field-value" href="<?= site_e($value) ?>" rel="noopener noreferrer" target="_blank"><?= site_e(parse_url($value, PHP_URL_HOST) ?: $value) ?></a>
                <?php elseif ($type === 'email'): ?>
                    <a class="directory-listing__field-value" href="mailto:<?= site_e($value) ?>"><?= site_e($value) ?></a>
                <?php elseif ($type === 'textarea'): ?>
                    <div class="directory-listing__field-value directory-listing__field-value--prose"><?= nl2br(site_e($value)) ?></div>
                <?php else: ?>
                    <span class="directory-listing__field-value"><?= site_e($value) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($record['body'])): ?>
    <section class="directory-listing__body-section">
        <h2 class="directory-listing__section-heading">About This Property</h2>
        <div class="directory-listing__body">
            <?= $record['body'] ?>
        </div>
    </section>
    <?php endif; ?>

</article>
