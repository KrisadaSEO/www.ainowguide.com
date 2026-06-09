<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <?php $head_meta_desc = ($preserve_meta_desc ?? false)
      ? ($meta_desc ?? SITE_META_DESC)
      : (mb_strlen($meta_desc ?? SITE_META_DESC) > 160
          ? mb_substr($meta_desc ?? SITE_META_DESC, 0, 160) . '...'
          : ($meta_desc ?? SITE_META_DESC)); ?>

  <title><?= e($page_title ?? SITE_NAME) ?></title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="icon" href="/favicon.ico" sizes="any">

  <meta name="description" content="<?= e($head_meta_desc) ?>">

  <?php if (!empty($canonical_url)): ?>
    <link rel="canonical" href="<?= e(SITE_URL . $canonical_url) ?>">
  <?php endif; ?>

  <meta property="og:type"        content="website">
  <meta property="og:title"       content="<?= e($page_title ?? SITE_NAME) ?>">
  <meta property="og:description" content="<?= e($head_meta_desc) ?>">
  <meta property="og:site_name"   content="<?= e(SITE_NAME) ?>">
  <?php if (!empty($canonical_url)): ?>
    <meta property="og:url" content="<?= e(SITE_URL . $canonical_url) ?>">
  <?php endif; ?>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Source+Serif+4:ital,opsz,wght@0,8..60,300;0,8..60,400;1,8..60,300&display=swap" rel="stylesheet">

  <!-- Core stylesheets -->
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
  <link rel="stylesheet" href="/assets/css/cards.css">

  <?php if (!empty($page_type)): ?>
    <?php $page_css = '/assets/css/' . e($page_type) . '.css'; ?>
    <?php if (file_exists(BASE_PATH . ltrim($page_css, '/'))): ?>
      <link rel="stylesheet" href="<?= $page_css ?>">
    <?php endif; ?>
  <?php endif; ?>

  <?php if (!empty($json_ld)): ?>
  <script type="application/ld+json">
  <?= json_encode($json_ld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <?php endif; ?>
</head>
<body class="page--<?= e($page_type ?? 'default') ?>">
<div class="site-wrapper">
