<?php
$page_type = '404';
require PARTIALS_PATH . 'head.php';
require PARTIALS_PATH . 'header.php';
?>
<main class="site-main">
  <div class="page-layout page-layout--narrow" style="text-align:center;padding-top:var(--space-24);">
    <div class="section-label" style="text-align:center;">404</div>
    <h1 style="font-size:var(--text-4xl);font-weight:var(--weight-extralight);margin:var(--space-4) 0;">Page Not Found</h1>
    <p class="text-muted" style="margin-bottom:var(--space-8);">That URL doesn't exist. It may have moved or you may have the wrong link.</p>
    <div style="display:flex;gap:var(--space-3);justify-content:center;flex-wrap:wrap;">
      <a href="/" class="btn btn--primary">Go Home</a>
      <a href="/sessions" class="btn btn--secondary">Browse Sessions</a>
      <a href="/channels" class="btn btn--secondary">Channels</a>
    </div>
  </div>
</main>
<?php require PARTIALS_PATH . 'footer.php'; ?>
