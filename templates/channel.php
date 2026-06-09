<?php
$page_type = 'channel';
require PARTIALS_PATH . 'head.php';
require PARTIALS_PATH . 'header.php';

$channel_slug = $data['core']['slug'] ?? '';
$channel_icon = $data['core']['icon'] ?? '';
?>
<main class="site-main">
  <div class="page-layout">
    <?php require PARTIALS_PATH . 'breadcrumbs.php'; ?>

    <!-- Channel hero (krisada.com directory-hero pattern) -->
    <header class="directory-hero">
      <div class="directory-hero__eyebrow">
        <span class="directory-hero__icon"><?= $channel_icon ?></span>
        Session Channel
      </div>
      <h1><?= e($data['core']['title'] ?? '') ?></h1>
      <?php if (!empty($data['core']['description'])): ?>
      <p class="directory-hero__intro"><?= e($data['core']['description']) ?></p>
      <?php endif; ?>
    </header>

    <?php if (!empty($data['content']['about'])): ?>
    <div class="channel-about">
      <p><?= e($data['content']['about']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Sessions in this channel (krisada.com directory-listing-row pattern) -->
    <?php if (!empty($sessions)): ?>
    <div class="directory-section">
      <h2 class="directory-section__heading">Sessions in This Channel</h2>
      <div class="directory-listing-list">
        <?php foreach ($sessions as $session): ?>
        <div class="directory-listing-row">
          <div class="directory-listing-row__meta">
            <span class="directory-listing-row__date"><?= e($session['core']['date'] ?? '') ?></span>
            <?php if (!empty($session['core']['duration'])): ?>
            <span class="directory-listing-row__duration"><?= e($session['core']['duration']) ?></span>
            <?php endif; ?>
            <?php
            $vis = $session['core']['visibility'] ?? 'public';
            if ($vis !== 'public'):
            ?>
            <span class="chip chip--amber"><?= e(ucfirst($vis)) ?></span>
            <?php endif; ?>
          </div>
          <div class="directory-listing-row__body">
            <h3 class="directory-listing-row__title">
              <a href="/sessions/<?= e($session['core']['slug'] ?? '') ?>"><?= e($session['core']['title'] ?? '') ?></a>
            </h3>
            <?php if (!empty($session['content']['summary'])): ?>
            <p class="directory-listing-row__excerpt"><?= e(truncate($session['content']['summary'], 160)) ?></p>
            <?php endif; ?>
            <?php if (!empty($session['meta']['tags'])): ?>
            <div class="directory-listing-row__tags">
              <?php foreach (array_slice($session['meta']['tags'], 0, 4) as $tag): ?>
              <span class="chip chip--default"><?= e($tag) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <p class="text-faint" style="font-style:italic;margin:var(--space-8) 0;">No sessions in this channel yet. Check back soon.</p>
    <?php endif; ?>

    <div class="cta-block">
      <div class="cta-block__eyebrow">More to watch</div>
      <h2 class="cta-block__heading">Explore other channels.</h2>
      <div class="cta-block__actions">
        <a href="/channels" class="btn btn--secondary">All Channels</a>
        <a href="/sessions" class="btn btn--secondary">All Sessions</a>
      </div>
    </div>

  </div>
</main>
<?php require PARTIALS_PATH . 'footer.php'; ?>
