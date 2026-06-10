<?php
declare(strict_types=1);

$view = $view ?? [];
$record = $view['record'] ?? [];
$mediaFiles = is_array($record['media_files'] ?? null) ? $record['media_files'] : [];
$channelsData = site_load_json_file((string) ($mediaFiles['channels'] ?? 'content/media/channels.json'));
$sessionsData = site_load_json_file((string) ($mediaFiles['sessions'] ?? 'content/media/sessions.json'));
$membershipData = site_load_json_file((string) ($mediaFiles['membership'] ?? 'content/media/membership.json'));
$timelineData = site_load_json_file((string) ($mediaFiles['timeline'] ?? 'content/media/timeline.json'));
$channels = is_array($channelsData['items'] ?? null) ? $channelsData['items'] : [];
$sessions = is_array($sessionsData['items'] ?? null) ? $sessionsData['items'] : [];
$memberships = is_array($membershipData['items'] ?? null) ? $membershipData['items'] : [];
$timelineYears = is_array($timelineData['years'] ?? null) ? $timelineData['years'] : [];
$channelNames = [];

foreach ($channels as $channel) {
    $channelNames[(string) ($channel['slug'] ?? '')] = (string) ($channel['title'] ?? '');
}
?>
<main class="ang-home" id="main-content">
    <section class="ang-hero">
        <div class="ang-shell ang-hero__content">
            <p class="ang-eyebrow"><?= site_e($record['hero_eyebrow'] ?? '') ?></p>
            <h1><?= site_e($record['hero_headline'] ?? '') ?></h1>
            <p class="ang-hero__intro"><?= site_e($record['hero_subtext'] ?? '') ?></p>
            <ul class="ang-proof-list">
                <li>80+ digital properties</li>
                <li>Raw working sessions</li>
                <li>AI workflows and SEO experiments</li>
                <li>Real builds, decisions, and mistakes</li>
            </ul>
            <div class="ang-actions">
                <a class="ang-button ang-button--primary" href="<?= site_e($record['hero_cta_1_url'] ?? '/#latest-sessions') ?>"><?= site_e($record['hero_cta_1_label'] ?? 'Watch Latest Session') ?></a>
                <a class="ang-button ang-button--secondary" href="<?= site_e($record['hero_cta_2_url'] ?? '/#channels') ?>"><?= site_e($record['hero_cta_2_label'] ?? 'Explore Channels') ?></a>
            </div>
        </div>
        <p class="ang-hero__caption">The actual build desk. Laptop, mic, coffee, window, and daily portfolio work.</p>
    </section>

    <section class="ang-section ang-section--sidebar-system">
        <div class="ang-shell ang-home-sidebar-layout">
            <div class="ang-home-sidebar-layout__main">
                <p class="ang-eyebrow">Use the archive your way</p>
                <h2>The right sidebar stays part of the system.</h2>
                <p class="ang-copy">Session navigation, founder context, featured properties, calls to action, and topic-specific resources remain assignable through JSON sidebar profiles. As the archive grows, each channel, session, guide, and directory page can use the same resolution chain without embedding sidebar copy in templates.</p>
            </div>
            <aside class="content-sidebar ang-home-sidebar" aria-label="AI Now Guide sidebar">
                <?php foreach (($view['sidebar_blocks'] ?? []) as $block):
                    $blockType = (string) ($block['type'] ?? 'block');
                    $blockStyle = (string) ($block['style'] ?? '');
                    $styleClass = $blockStyle !== '' ? 'sidebar-block--' . $blockStyle : 'sidebar-block--' . $blockType;
                    require site_root_path('templates/partials/sidebar-block.php');
                endforeach; ?>
            </aside>
        </div>
    </section>

    <section class="ang-section ang-section--workstation">
        <div class="ang-shell ang-split">
            <div>
                <p class="ang-eyebrow">Built from the real workstation</p>
                <h2>This is not a course studio.</h2>
            </div>
            <div class="ang-copy">
                <p>AI Now Guide preserves the process while the work is happening. The sessions are not polished performances. They are the actual builds, decisions, errors, fixes, experiments, and portfolio conversations behind the properties.</p>
                <p>Most creators stop working to create content. Here, the work is the content.</p>
            </div>
        </div>
    </section>

    <section class="ang-section" id="channels">
        <div class="ang-shell">
            <div class="ang-section__header">
                <div>
                    <p class="ang-eyebrow">What you can watch</p>
                    <h2>Choose a session channel.</h2>
                </div>
                <p>Follow the builds and topics that matter to you. The channel model can grow without changing the content engine.</p>
            </div>
            <div class="ang-channel-grid">
                <?php foreach ($channels as $index => $channel): ?>
                <article class="ang-channel-card">
                    <span class="ang-channel-card__number"><?= site_e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                    <h3><?= site_e($channel['title'] ?? '') ?></h3>
                    <p><?= site_e($channel['description'] ?? '') ?></p>
                    <span class="ang-visibility"><?= site_e($channel['membership_visibility'] ?? 'public') ?></span>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="ang-section ang-section--sessions" id="latest-sessions">
        <div class="ang-shell">
            <div class="ang-section__header">
                <div>
                    <p class="ang-eyebrow">Featured session archive</p>
                    <h2>Latest working sessions.</h2>
                </div>
                <p>The archive is the asset. Every session adds context to the build history.</p>
            </div>
            <div class="ang-session-grid">
                <?php foreach ($sessions as $session): ?>
                <article class="ang-session-card">
                    <a class="ang-session-card__media" href="<?= site_e($session['video_url'] ?: '/contact/?intent=session-alerts') ?>" tabindex="-1" aria-hidden="true">
                        <?php if (!empty($session['thumbnail'])): ?>
                        <img src="<?= site_e($session['thumbnail']) ?>" alt="">
                        <?php else: ?>
                        <span class="ang-session-card__placeholder">Session recording</span>
                        <?php endif; ?>
                        <span class="ang-session-card__play">Play</span>
                    </a>
                    <div class="ang-session-card__body">
                        <div class="ang-session-card__meta">
                            <span><?= site_e(site_format_date((string) ($session['date'] ?? ''))) ?></span>
                            <span><?= site_e($channelNames[(string) ($session['channel'] ?? '')] ?? '') ?></span>
                            <span><?= site_e($session['visibility'] ?? 'public') ?></span>
                        </div>
                        <h3><a href="<?= site_e($session['video_url'] ?: '/contact/?intent=session-alerts') ?>"><?= site_e($session['title'] ?? '') ?></a></h3>
                        <p><?= site_e($session['summary'] ?? '') ?></p>
                        <div class="ang-tags">
                            <?php foreach (($session['tags'] ?? []) as $tag): ?>
                            <span><?= site_e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="ang-section" id="membership">
        <div class="ang-shell">
            <div class="ang-section__header">
                <div>
                    <p class="ang-eyebrow">Membership comes later</p>
                    <h2>A media platform with memberships.</h2>
                </div>
                <p>Story, content, discovery, and archive come first. Access levels support the media platform without becoming its identity.</p>
            </div>
            <div class="ang-membership-grid">
                <?php foreach ($memberships as $membership): ?>
                <article class="ang-membership-card<?= !empty($membership['featured']) ? ' is-featured' : '' ?><?= !empty($membership['private']) ? ' is-private' : '' ?>">
                    <p class="ang-membership-card__name"><?= site_e($membership['name'] ?? '') ?></p>
                    <p class="ang-membership-card__price"><?= site_e($membership['price'] ?? '') ?><?php if (!empty($membership['period'])): ?><span> / <?= site_e($membership['period']) ?></span><?php endif; ?></p>
                    <p class="ang-membership-card__desc"><?= site_e($membership['description'] ?? '') ?></p>
                    <ul>
                        <?php foreach (($membership['features'] ?? []) as $feature): ?>
                        <li><?= site_e($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="ang-button <?= !empty($membership['featured']) ? 'ang-button--primary' : 'ang-button--secondary' ?>" href="<?= site_e($membership['cta_url'] ?? '/contact/') ?>"><?= site_e($membership['cta_label'] ?? 'Learn More') ?></a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="ang-section ang-section--timeline" id="why">
        <div class="ang-shell ang-timeline-layout">
            <div>
                <p class="ang-eyebrow">Why it exists</p>
                <h2>The chronology matters.</h2>
                <p class="ang-copy">A build log becomes more valuable with time. It shows what was tried, what changed, what survived, and how an interconnected portfolio was assembled one property at a time.</p>
            </div>
            <div class="ang-timeline">
                <?php foreach ($timelineYears as $year): ?>
                <div class="ang-timeline__year">
                    <strong><?= site_e($year['year'] ?? '') ?></strong>
                    <ol>
                        <?php foreach (($year['events'] ?? []) as $event): ?>
                        <li class="status-<?= site_e($event['status'] ?? 'planned') ?>"><?= site_e($event['title'] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="ang-federation-cta">
        <div class="ang-shell">
            <p class="ang-eyebrow">The federation is already moving</p>
            <h2>Want to watch this build happen?</h2>
            <p>Follow the sessions as websites are acquired, rebuilt, connected, measured, and turned into durable digital assets.</p>
            <div class="ang-actions">
                <a class="ang-button ang-button--primary" href="/#latest-sessions">Watch the Latest Session</a>
                <a class="ang-button ang-button--secondary" href="/directory/portfolio/">Explore the Entire Portfolio</a>
            </div>
        </div>
    </section>
</main>
