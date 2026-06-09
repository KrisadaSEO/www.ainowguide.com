<?php
declare(strict_types=1);
$sessions = $view['sessions'] ?? [];
$channelsBySlug = $view['channels_by_slug'] ?? site_app()['channels_by_slug'] ?? [];
?>
<div class="page-hero">
    <h1 class="page-hero__title">Sessions</h1>
    <p class="page-hero__desc">All build-in-public recording sessions. Filter by channel or browse everything below.</p>
</div>

<div class="session-list session-list--full">
    <?php foreach ($sessions as $sess):
        $sessUrl  = (string) ($sess['canonical_url'] ?? '/sessions/' . $sess['slug'] . '/');
        $chSlug   = (string) ($sess['channel_slug'] ?? '');
        $chRecord = $channelsBySlug[$chSlug] ?? null;
        $chTitle  = $chRecord !== null ? (string) ($chRecord['title'] ?? '') : '';
        $chUrl    = $chRecord !== null ? (string) ($chRecord['canonical_url'] ?? '/channels/' . $chSlug . '/') : '';
    ?>
    <a href="<?= site_e($sessUrl) ?>" class="session-row">
        <div class="session-row__meta">
            <?php if ($chTitle !== ''): ?>
            <span class="session-row__channel"><?= site_e($chTitle) ?></span>
            <?php endif; ?>
            <?php if (!empty($sess['date'])): ?>
            <span class="session-row__date"><?= site_e((string) $sess['date']) ?></span>
            <?php endif; ?>
            <?php if (($sess['visibility'] ?? 'public') === 'public'): ?>
            <span class="session-row__vis session-row__vis--public">Public</span>
            <?php elseif (($sess['visibility'] ?? '') === 'members'): ?>
            <span class="session-row__vis session-row__vis--members">Members</span>
            <?php endif; ?>
            <?php if (!empty($sess['duration'])): ?>
            <span class="session-row__duration"><?= site_e((string) $sess['duration']) ?></span>
            <?php endif; ?>
        </div>
        <h2 class="session-row__title"><?= site_e((string) ($sess['title'] ?? '')) ?></h2>
        <?php if (!empty($sess['summary'])): ?>
        <p class="session-row__summary"><?= site_e((string) $sess['summary']) ?></p>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <?php if (empty($sessions)): ?>
    <p class="text-muted">No sessions yet. Check back soon.</p>
    <?php endif; ?>
</div>
