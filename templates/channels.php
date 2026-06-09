<?php
$page_type = 'channels';
require PARTIALS_PATH . 'head.php';
require PARTIALS_PATH . 'header.php';
$channels = $data['channels'] ?? [];
?>
<main class="site-main">
  <div class="page-layout">
    <?php require PARTIALS_PATH . 'breadcrumbs.php'; ?>

    <header class="page-header">
      <span class="page-header__eyebrow">Session Channels</span>
      <h1 class="page-header__title">Choose a Channel to Follow</h1>
      <p class="page-header__lead">Each channel covers a distinct domain of the portfolio build. Watch what's relevant to your work.</p>
    </header>

    <?php if (!empty($channels)): ?>
    <div class="cards-grid cards-grid--3col">
      <?php foreach ($channels as $channel): ?>
      <?php $session_count = count(get_sessions_for_channel($channel['core']['slug'] ?? '')); ?>
      <a href="/channels/<?= e($channel['core']['slug'] ?? '') ?>" class="channel-card channel-card--large">
        <div class="channel-card__top">
          <div class="channel-card__icon"><?= $channel['core']['icon'] ?? '' ?></div>
          <div class="channel-card__count"><?= $session_count ?> session<?= $session_count === 1 ? '' : 's' ?></div>
        </div>
        <div class="channel-card__title"><?= e($channel['core']['title'] ?? '') ?></div>
        <p class="channel-card__desc"><?= e($channel['core']['description'] ?? '') ?></p>
        <div class="channel-card__link">Browse channel &rarr;</div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-faint" style="font-style:italic;">Channels are being set up. Check back soon.</p>
    <?php endif; ?>

    <div class="cta-block">
      <div class="cta-block__eyebrow">Build in Public</div>
      <h2 class="cta-block__heading">New sessions posted regularly.</h2>
      <p class="cta-block__body">Raw working sessions from the actual build desk. No editing. No polish. Just the process.</p>
      <div class="cta-block__actions">
        <a href="/sessions" class="btn btn--primary">Browse All Sessions</a>
      </div>
    </div>

  </div>
</main>
<?php require PARTIALS_PATH . 'footer.php'; ?>
