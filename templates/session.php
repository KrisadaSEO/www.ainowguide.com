<?php
declare(strict_types=1);
$channel = $view['channel'] ?? null;
$relatedSessions = $view['related_sessions'] ?? [];
$videoUrl = (string) ($record['video_url'] ?? '');
$thumbnail = (string) ($record['thumbnail'] ?? '');
$visibility = (string) ($record['visibility'] ?? 'public');
?>
<div class="session-detail">
    <div class="session-detail__meta">
        <?php if ($channel !== null): ?>
        <a href="<?= site_e((string) ($channel['canonical_url'] ?? '/channels/')) ?>" class="session-detail__channel">
            &larr; <?= site_e((string) ($channel['title'] ?? 'Channel')) ?>
        </a>
        <?php endif; ?>
        <?php if (!empty($record['date'])): ?>
        <span class="session-detail__date"><?= site_e((string) $record['date']) ?></span>
        <?php endif; ?>
        <?php if ($visibility === 'public'): ?>
        <span class="session-detail__vis session-detail__vis--public">Public</span>
        <?php elseif ($visibility === 'members'): ?>
        <span class="session-detail__vis session-detail__vis--members">Members</span>
        <?php endif; ?>
        <?php if (!empty($record['duration'])): ?>
        <span class="session-detail__duration"><?= site_e((string) $record['duration']) ?></span>
        <?php endif; ?>
    </div>

    <h1 class="session-detail__title"><?= site_e((string) ($record['title'] ?? '')) ?></h1>

    <?php if (!empty($record['summary'])): ?>
    <p class="session-detail__summary"><?= site_e((string) $record['summary']) ?></p>
    <?php endif; ?>

    <?php if ($videoUrl !== ''): ?>
    <div class="session-player">
        <div class="session-player__embed">
            <?php if (str_contains($videoUrl, 'youtube.com') || str_contains($videoUrl, 'youtu.be')): ?>
            <iframe src="<?= site_e($videoUrl) ?>" frameborder="0" allowfullscreen title="Session video"></iframe>
            <?php elseif (str_contains($videoUrl, 'vimeo.com')): ?>
            <iframe src="<?= site_e($videoUrl) ?>" frameborder="0" allowfullscreen title="Session video"></iframe>
            <?php else: ?>
            <a href="<?= site_e($videoUrl) ?>" class="btn btn--primary" target="_blank" rel="noopener">Watch Session &rarr;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif ($thumbnail !== ''): ?>
    <div class="session-thumb">
        <img src="<?= site_e($thumbnail) ?>" alt="<?= site_e((string) ($record['title'] ?? '')) ?>" loading="lazy">
        <div class="session-thumb__overlay">
            <span class="session-thumb__coming-soon">Video Coming Soon</span>
        </div>
    </div>
    <?php else: ?>
    <div class="session-no-video">
        <div class="session-no-video__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        </div>
        <p>Video coming soon.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($record['description'])): ?>
    <div class="session-detail__body">
        <h2>About This Session</h2>
        <p><?= nl2br(site_e((string) $record['description'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($record['tags']) && is_array($record['tags'])): ?>
    <div class="session-detail__tags">
        <?php foreach ($record['tags'] as $tag): ?>
        <span class="tag"><?= site_e((string) $tag) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($relatedSessions)): ?>
<aside class="session-related">
    <h2 class="session-related__heading">More from <?= site_e((string) ($channel['title'] ?? 'this channel')) ?></h2>
    <div class="session-list">
        <?php foreach ($relatedSessions as $sess):
            $sessUrl = (string) ($sess['canonical_url'] ?? '/sessions/' . $sess['slug'] . '/');
        ?>
        <a href="<?= site_e($sessUrl) ?>" class="session-row">
            <div class="session-row__meta">
                <?php if (!empty($sess['date'])): ?>
                <span class="session-row__date"><?= site_e((string) $sess['date']) ?></span>
                <?php endif; ?>
            </div>
            <h3 class="session-row__title"><?= site_e((string) ($sess['title'] ?? '')) ?></h3>
            <?php if (!empty($sess['summary'])): ?>
            <p class="session-row__summary"><?= site_e((string) $sess['summary']) ?></p>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</aside>
<?php endif; ?>
