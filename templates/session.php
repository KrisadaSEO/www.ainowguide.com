<?php
$page_type = 'session';
require PARTIALS_PATH . 'head.php';
require PARTIALS_PATH . 'header.php';

$vis = $data['core']['visibility'] ?? 'public';
?>
<main class="site-main">
  <div class="page-layout">
    <?php require PARTIALS_PATH . 'breadcrumbs.php'; ?>

    <!-- Session header (krisada.com directory-listing pattern) -->
    <article class="directory-listing">

      <header class="directory-listing__header">
        <div class="directory-listing__hero-topline">
          <div class="category-hero__label">
            <?php if ($channel): ?>
            <a href="/channels/<?= e($channel['core']['slug'] ?? '') ?>" style="color:inherit;text-decoration:none;">
              <?= $channel['core']['icon'] ?? '' ?> <?= e($channel['core']['title'] ?? 'Session') ?>
            </a>
            <?php else: ?>
            Session
            <?php endif; ?>
          </div>
          <span class="directory-listing__date"><?= e($data['core']['date'] ?? '') ?></span>
        </div>

        <h1 class="directory-listing__title"><?= e($data['core']['title'] ?? '') ?></h1>

        <div class="directory-listing__hero-grid">
          <div class="directory-listing__hero-copy">
            <?php if (!empty($data['content']['summary'])): ?>
            <p class="directory-listing__intro"><?= e($data['content']['summary']) ?></p>
            <?php endif; ?>

            <?php if (!empty($data['core']['video_url'])): ?>
            <div class="session-video">
              <a href="<?= e($data['core']['video_url']) ?>" class="btn btn--primary" target="_blank" rel="noopener">
                &#9654; Watch Session
              </a>
            </div>
            <?php endif; ?>
          </div>

          <aside class="directory-listing__hero-panel">
            <div class="directory-listing__hero-panel-label">Session Details</div>
            <dl class="directory-listing__hero-facts">
              <?php if (!empty($data['core']['date'])): ?>
              <div class="directory-listing__hero-fact">
                <dt>Date</dt>
                <dd><?= e($data['core']['date']) ?></dd>
              </div>
              <?php endif; ?>
              <?php if (!empty($data['core']['duration'])): ?>
              <div class="directory-listing__hero-fact">
                <dt>Duration</dt>
                <dd><?= e($data['core']['duration']) ?></dd>
              </div>
              <?php endif; ?>
              <?php if ($channel): ?>
              <div class="directory-listing__hero-fact">
                <dt>Channel</dt>
                <dd><a href="/channels/<?= e($channel['core']['slug'] ?? '') ?>"><?= e($channel['core']['title'] ?? '') ?></a></dd>
              </div>
              <?php endif; ?>
              <div class="directory-listing__hero-fact">
                <dt>Access</dt>
                <dd><?= e(ucfirst($vis)) ?></dd>
              </div>
              <?php if (!empty($data['meta']['related_property'])): ?>
              <div class="directory-listing__hero-fact">
                <dt>Property</dt>
                <dd><?= e($data['meta']['related_property']) ?></dd>
              </div>
              <?php endif; ?>
            </dl>
          </aside>
        </div>
      </header>

      <?php if (!empty($data['content']['description'])): ?>
      <section class="directory-listing__body-section">
        <h2 class="directory-listing__section-heading">About This Session</h2>
        <div class="directory-listing__body">
          <?php foreach (explode("\n\n", $data['content']['description']) as $para): ?>
          <?php if (trim($para) !== ''): ?><p><?= e(trim($para)) ?></p><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if (!empty($data['content']['build_notes'])): ?>
      <section class="directory-listing__fields-section">
        <h2 class="directory-listing__section-heading">Build Notes</h2>
        <div class="session-build-notes">
          <?php foreach (explode("\n", $data['content']['build_notes']) as $note): ?>
          <?php if (trim($note) !== ''): ?><p><?= e(trim($note)) ?></p><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if (!empty($data['meta']['tags'])): ?>
      <div class="directory-listing__tags">
        <?php foreach ($data['meta']['tags'] as $tag): ?>
        <span class="chip chip--default"><?= e($tag) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </article>

    <div class="cta-block">
      <div class="cta-block__eyebrow">Keep watching</div>
      <h2 class="cta-block__heading">More sessions in the archive.</h2>
      <div class="cta-block__actions">
        <?php if ($channel): ?>
        <a href="/channels/<?= e($channel['core']['slug'] ?? '') ?>" class="btn btn--secondary">More from this channel</a>
        <?php endif; ?>
        <a href="/sessions" class="btn btn--secondary">All Sessions</a>
      </div>
    </div>

  </div>
</main>
<?php require PARTIALS_PATH . 'footer.php'; ?>
