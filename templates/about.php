<?php
$page_type = 'about';
require PARTIALS_PATH . 'head.php';
require PARTIALS_PATH . 'header.php';
$hero    = $data['hero']    ?? [];
$content = $data['content'] ?? [];
?>
<main class="site-main">
  <div class="page-layout page-layout--narrow">
    <?php require PARTIALS_PATH . 'breadcrumbs.php'; ?>

    <header class="page-header">
      <span class="page-header__eyebrow">About</span>
      <h1 class="page-header__title"><?= e($hero['headline'] ?? 'About AI Now Guide') ?></h1>
      <p class="page-header__lead"><?= e($hero['subheadline'] ?? '') ?></p>
    </header>

    <?php if (!empty($content['body'])): ?>
    <div class="prose">
      <?php foreach (explode("\n\n", $content['body']) as $para): ?>
      <?php if (trim($para) !== ''): ?><p><?= e(trim($para)) ?></p><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($content['about_krisada'])): ?>
    <div class="about-card panel">
      <div class="section-label section-label--violet">About Krisada</div>
      <p><?= e($content['about_krisada']) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($content['contact_note'])): ?>
    <div class="about-card panel panel--raised" style="margin-top:var(--space-6);">
      <div class="section-label">Private / Platinum</div>
      <p><?= e($content['contact_note']) ?></p>
    </div>
    <?php endif; ?>

    <div class="cta-block">
      <div class="cta-block__eyebrow">Start here</div>
      <h2 class="cta-block__heading">Watch the build happen.</h2>
      <div class="cta-block__actions">
        <a href="/sessions" class="btn btn--primary">Browse Sessions</a>
        <a href="/channels" class="btn btn--secondary">Explore Channels</a>
      </div>
    </div>

  </div>
</main>
<?php require PARTIALS_PATH . 'footer.php'; ?>
