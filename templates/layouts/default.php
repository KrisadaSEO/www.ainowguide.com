<?php
declare(strict_types=1);

$view       = $view ?? [];
$record     = $view['record'] ?? [];
$site       = $view['site'] ?? [];
$type       = $view['type'] ?? 'page';
$title      = site_e($view['title'] ?? 'AI Now Guide');
$description = site_e($view['description'] ?? '');
$social     = $view['social'] ?? [];
$socialTitle = site_e((string) ($social['title'] ?? $view['title'] ?? 'AI Now Guide'));
$socialDescription = site_e((string) ($social['description'] ?? $view['description'] ?? ''));
$socialUrl = site_e((string) ($social['url'] ?? $view['canonical_url'] ?? ''));
$socialType = site_e((string) ($social['type'] ?? 'website'));
$socialImage = trim((string) ($social['image'] ?? ''));
$twitterCard = site_e((string) ($social['twitter_card'] ?? 'summary'));
$siteName = site_e((string) ($social['site_name'] ?? $site['name'] ?? 'AI Now Guide'));
$breadcrumbs = $view['breadcrumbs'] ?? [];
$sidebarBlocks = $view['sidebar_blocks'] ?? [];
$templatePath  = $view['template_path'] ?? '';
$navigation    = $site['navigation'] ?? [];
$footerLinks   = $site['footer_links'] ?? [];
$footerAbout   = $site['footer_about_links'] ?? [];
$currentPath   = site_normalize_path($_SERVER['REQUEST_URI'] ?? '/');
$pageViews     = (int) ($view['page_views'] ?? 0);
$pageViewsText = (string) ($view['page_views_display'] ?? '');
$hideSidebar   = (bool) ($view['hide_sidebar'] ?? false);
$isHomePage    = ($type === 'page' && ($record['slug'] ?? '') === 'home');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <meta name="description" content="<?= $description ?>">
    <meta property="og:title" content="<?= $socialTitle ?>">
    <meta property="og:description" content="<?= $socialDescription ?>">
    <meta property="og:url" content="<?= $socialUrl ?>">
    <meta property="og:type" content="<?= $socialType ?>">
    <meta property="og:site_name" content="<?= $siteName ?>">
    <meta name="twitter:card" content="<?= $twitterCard ?>">
    <meta name="twitter:title" content="<?= $socialTitle ?>">
    <meta name="twitter:description" content="<?= $socialDescription ?>">
    <?php if ($socialImage !== ''): ?>
    <meta property="og:image" content="<?= site_e($socialImage) ?>">
    <meta name="twitter:image" content="<?= site_e($socialImage) ?>">
    <?php endif; ?>
    <?php if (!empty($record['seo']['robots'])): ?>
    <meta name="robots" content="<?= site_e((string) $record['seo']['robots']) ?>">
    <?php endif; ?>
    <?php if (!empty($view['canonical_url'])): ?>
    <link rel="canonical" href="<?= site_e($view['canonical_url']) ?>">
    <?php endif; ?>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/sidebar.css">
    <?php if (!empty($view['extra_css'])): ?>
    <link rel="stylesheet" href="/assets/css/<?= site_e((string) $view['extra_css']) ?>.css">
    <?php endif; ?>
    <style>:root { <?= site_tokens_css() ?> }</style>
    <?php if (!empty($view['json_ld'])): ?>
    <script type="application/ld+json"><?= json_encode($view['json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
</head>
<body class="page-type-<?= site_e($type) ?><?= $isHomePage ? ' is-home' : '' ?>">

<!-- ===== HEADER ===== -->
<header class="site-header" id="site-header">
    <div class="site-header__inner">
        <a href="/" class="site-logo">
            <span class="site-logo__mark">AI</span><span class="site-logo__text">Now<span class="site-logo__accent">Guide</span></span>
        </a>
        <nav class="site-nav" aria-label="Primary navigation">
            <ul class="site-nav__list">
            <?php foreach ($navigation as $navItem):
                if (!empty($navItem['hidden'])) continue;
                $navUrl   = (string) ($navItem['url'] ?? '/');
                $navPath  = site_normalize_path($navUrl);
                $isActive = $navPath !== '/' && str_starts_with($currentPath, $navPath);
            ?>
                <li class="site-nav__item">
                    <a href="<?= site_e($navUrl) ?>"
                       class="site-nav__link<?= $isActive ? ' is-active' : '' ?>"
                       <?= $isActive ? 'aria-current="page"' : '' ?>
                    ><?= site_e($navItem['label'] ?? '') ?></a>
                </li>
            <?php endforeach; ?>
            </ul>
        </nav>
        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="mobile-nav">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<!-- Mobile nav overlay -->
<div class="mobile-nav" id="mobile-nav" aria-hidden="true">
    <ul class="mobile-nav__list">
    <?php foreach ($navigation as $navItem):
        if (!empty($navItem['hidden'])) continue;
        $navUrl   = (string) ($navItem['url'] ?? '/');
        $navPath  = site_normalize_path($navUrl);
        $isActive = $navPath !== '/' && str_starts_with($currentPath, $navPath);
    ?>
        <li>
            <a href="<?= site_e($navUrl) ?>" class="mobile-nav__link<?= $isActive ? ' is-active' : '' ?>"><?= site_e($navItem['label'] ?? '') ?></a>
        </li>
    <?php endforeach; ?>
    </ul>
</div>

<!-- ===== MAIN ===== -->
<main class="site-main" id="main-content">
    <?php if ($isHomePage): ?>
        <?php if ($templatePath !== '' && is_file($templatePath)) require $templatePath; ?>
    <?php else: ?>
        <div class="page-wrap">
            <?php if (count($breadcrumbs) > 1): ?>
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <ol class="breadcrumbs__list">
                    <?php foreach ($breadcrumbs as $i => $crumb):
                        $isLast = $i === count($breadcrumbs) - 1;
                    ?>
                    <li class="breadcrumbs__item<?= $isLast ? ' is-current' : '' ?>">
                        <?php if (!$isLast): ?>
                            <a href="<?= site_e($crumb['url']) ?>"><?= site_e($crumb['label']) ?></a>
                        <?php else: ?>
                            <span><?= site_e($crumb['label']) ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <?php endif; ?>

            <div class="content-layout<?= $hideSidebar ? ' content-layout--single' : '' ?>">
                <div class="content-main">
                    <?php if ($templatePath !== '' && is_file($templatePath)): ?>
                        <?= require $templatePath ?>
                    <?php else: ?>
                        <p class="text-muted">Template not found.</p>
                    <?php endif; ?>
                </div>

                <?php if (!$hideSidebar): ?>
                <aside class="content-sidebar" aria-label="Sidebar">
                    <?php foreach ($sidebarBlocks as $block):
                        $blockType  = (string) ($block['type'] ?? 'block');
                        $blockStyle = (string) ($block['style'] ?? '');
                        $styleClass = $blockStyle !== '' ? 'sidebar-block--' . $blockStyle : 'sidebar-block--' . $blockType;
                        require site_root_path('templates/partials/sidebar-block.php');
                    endforeach; ?>
                </aside>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- ===== FOOTER ===== -->
<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__top">
            <div class="site-footer__brand">
                <div class="site-footer__logo">AI<span>Now</span>Guide</div>
                <p class="site-footer__tagline"><?= site_e($site['tagline'] ?? '') ?></p>
            </div>
            <?php if (!empty($footerLinks)): ?>
            <div class="site-footer__col">
                <div class="site-footer__col-heading">Explore</div>
                <ul class="site-footer__links">
                    <?php foreach ($footerLinks as $link): ?>
                    <li><a href="<?= site_e($link['url'] ?? '/') ?>"><?= site_e($link['label'] ?? '') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if (!empty($footerAbout)): ?>
            <div class="site-footer__col">
                <div class="site-footer__col-heading">Federation</div>
                <ul class="site-footer__links">
                    <?php foreach ($footerAbout as $link): ?>
                    <li><a href="<?= site_e($link['url'] ?? '/') ?>" target="_blank" rel="noopener"><?= site_e($link['label'] ?? '') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <div class="site-footer__bottom">
            <span><a href="/admin/login" class="site-footer__admin-link" title="Admin">&copy;</a> <?= date('Y') ?> <?= site_e($site['name'] ?? 'AI Now Guide') ?>. All rights reserved.</span>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js" defer></script>
</body>
</html>
