<?php
$page_type = 'sessions';
require PARTIALS_PATH . 'head.php';
require PARTIALS_PATH . 'header.php';
$sessions = $data['sessions'] ?? [];
?>
<main class="site-main">
  <div class="page-layout">
    <?php require PARTIALS_PATH . 'breadcrumbs.php'; ?>

    <header class="page-header">
      <span class="page-header__eyebrow">Session Archive</span>
      <h1 class="page-header__title">All Sessions</h1>
      <p class="page-header__lead">Raw working sessions from the portfolio build. Unedited. Chronological. Real process.</p>
    </header>

    <?php if (!empty($sessions)): ?>
    <div class="directory-listing-list">
      <?php foreach ($sessions as $session): ?>
      <?php $channel_data = !empty($session['core']['channel']) ? get_channel($session['core']['channel']) : null; ?>
      <div class="directory-listing-row">
        <div class="directory-listing-row__meta">
          <span class="directory-listing-row__date"><?= e($session['core']['date'] ?? '') ?></span>
          <?php if ($channel_data): ?>
          <a href="/channels/<?= e($channel_data['core']['slug'] ?? '') ?>" class="directory-listing-row__channel">
            <?= $channel_data['core']['icon'] ?? '' ?> <?= e($channel_data['core']['title'] ?? '') ?>
          </a>
          <?php endif; ?>
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
          <h2 class="directory-listing-row__title">
            <a href="/sessions/<?= e($session['core']['slug'] ?? '') ?>"><?= e($session['core']['title'] ?? '') ?></a>
          </h2>
          <?php if (!empty($session['content']['summary'])): ?>
          <p class="directory-listing-row__excerpt"><?= e(truncate($session['content']['summary'], 160)) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-faint" style="font-style:italic;">Sessions are being added. Check back soon.</p>
    <?php endif; ?>

  </div>
</main>
<?php require PARTIALS_PATH . 'footer.php'; ?>
