<?php
declare(strict_types=1);

$view       = $view ?? [];
$record     = $view['record'] ?? [];
$site       = $view['site'] ?? [];
$type       = $view['type'] ?? 'page';
$title      = site_e($view['title'] ?? 'Krisada.com');
$description = site_e($view['description'] ?? '');
$social     = $view['social'] ?? [];
$socialTitle = site_e((string) ($social['title'] ?? $view['title'] ?? 'Krisada.com'));
$socialDescription = site_e((string) ($social['description'] ?? $view['description'] ?? ''));
$socialUrl = site_e((string) ($social['url'] ?? $view['canonical_url'] ?? ''));
$socialType = site_e((string) ($social['type'] ?? 'website'));
$socialImage = trim((string) ($social['image'] ?? ''));
$twitterCard = site_e((string) ($social['twitter_card'] ?? 'summary'));
$siteName = site_e((string) ($social['site_name'] ?? $site['name'] ?? 'Krisada.com'));
$breadcrumbs = $view['breadcrumbs'] ?? [];
$sidebarBlocks = $view['sidebar_blocks'] ?? [];
$templatePath  = $view['template_path'] ?? '';
$navigation    = $site['navigation'] ?? [];
$footerLinks   = $site['footer_links'] ?? [];
$footerAbout   = $site['footer_about_links'] ?? [];
$currentPath   = site_normalize_path($_SERVER['REQUEST_URI'] ?? '/');
$pageViews     = (int) ($view['page_views'] ?? 0);
$pageViewsText = (string) ($view['page_views_display'] ?? '');
$isPublicView  = in_array($type, ['page', 'article', 'category', 'directory', 'download', 'glossary_index', 'glossary_term'], true) && (int) ($view['status_code'] ?? 200) === 200;
$hideSidebar   = (bool) ($view['hide_sidebar'] ?? false);
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;1,400&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (!empty($view['extra_css'])): ?>
    <link rel="stylesheet" href="/assets/css/<?= site_e((string) $view['extra_css']) ?>.css">
    <?php endif; ?>
    <?php if (!empty($view['canonical_url'])): ?>
    <link rel="canonical" href="<?= site_e($view['canonical_url']) ?>">
    <?php endif; ?>
    <?php if (!empty($record['seo']['robots'])): ?>
    <meta name="robots" content="<?= site_e((string) $record['seo']['robots']) ?>">
    <?php endif; ?>
    <link rel="alternate" type="application/json" href="/ai/llm.json" title="Machine-readable site identity">
    <style>:root { <?= site_tokens_css() ?> }</style>
    <script src="/assets/js/nav.js" defer></script>
    <?php if (!empty($view['extra_js'])): ?>
    <script src="/assets/js/<?= site_e((string) $view['extra_js']) ?>.js" defer></script>
    <?php endif; ?>

        <!-- Ahrefs Analytics -->
        <script src="https://analytics.ahrefs.com/analytics.js" data-key="wVJqgTCI9vSClA4HSH2ANA" async></script>
        <!-- End Ahrefs Analytics -->
</head>
<body class="page-type-<?= site_e($type) ?>">

    <!-- ===== HEADER ===== -->
    <header class="site-header">
        <div class="site-header__inner">
            <div class="site-logo">
                <?php
                $siteName   = (string) ($site['name'] ?? 'Krisada.com');
                $dotPos     = strrpos($siteName, '.');
                $logoPrefix = $dotPos !== false ? substr($siteName, 0, $dotPos) : $siteName;
                $logoSuffix = $dotPos !== false ? '.' . substr($siteName, $dotPos + 1) : '';
                ?>
                <a href="/"><span><?= site_e($logoPrefix) ?></span><span><?= site_e($logoSuffix) ?></span></a>
            </div>
            <button class="nav-hamburger" aria-label="Toggle navigation" aria-expanded="false">
                <span></span><span></span><span></span>
                <span class="nav-hamburger__label">MENU</span>
            </button>
            <nav class="site-nav" aria-label="Primary navigation">
                <ul class="site-nav__list">
                <?php foreach ($navigation as $navItem):
                    if (!empty($navItem['hidden'])) continue;
                    $navUrl       = (string) ($navItem['url'] ?? '/');
                    $navPath      = site_normalize_path($navUrl);
                    $isActive     = str_starts_with($currentPath, $navPath === '/' ? '/_never_' : $navPath) || $navPath === $currentPath;
                    $highlight    = (string) ($navItem['highlight'] ?? '');
                    $classes      = trim(($isActive ? 'is-active' : '') . ($highlight !== '' ? ' nav-link--' . $highlight : ''));
                    $navChildren  = site_navigation_children($navItem);
                    $hasDropdown  = count($navChildren['items']) > 0;
                    $dropdownAria = trim((string) ($navChildren['aria_label'] ?? ''));
                ?>
                    <li class="site-nav__item<?= $hasDropdown ? ' site-nav__item--has-dropdown' : '' ?>">
                        <a href="<?= site_e($navUrl) ?>"
                           class="<?= $classes ?>"
                           <?= $isActive ? 'aria-current="page"' : '' ?>
                        ><?= site_e($navItem['label'] ?? '') ?><?php if ($hasDropdown): ?><span class="nav-chevron" aria-hidden="true"></span><?php endif; ?></a>
                        <?php if ($hasDropdown): ?>
                        <ul class="nav-dropdown"<?= $dropdownAria !== '' ? ' aria-label="' . site_e($dropdownAria) . '"' : '' ?>>
                            <?php foreach ($navChildren['items'] as $child): ?>
                            <?php if ($child['divider'] ?? false): ?>
                            <li class="nav-dropdown__divider"><?= site_e($child['label'] ?? '') ?></li>
                            <?php else: ?>
                            <li>
                                <a href="<?= site_e($child['url'] ?? '') ?>"><?= site_e($child['label'] ?? '') ?><?php if (($child['price'] ?? '') !== ''): ?> <span class="nav-item__price"><?= site_e($child['price']) ?></span><?php endif; ?></a>
                            </li>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- ===== MAIN ===== -->
    <?php if ($type === 'page' && ($record['slug'] ?? '') === 'home'): ?>
        <?php
        /* Homepage skips the standard wrapper and renders full-width hero.
           The page template handles its own section structure. */
        if ($templatePath !== '' && is_file($templatePath)) {
            require $templatePath;
        }
        ?>
    <?php else: ?>
        <div class="page-wrapper">
            <?php if (count($breadcrumbs) > 1): ?>
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <ol class="breadcrumbs__list">
                    <?php foreach ($breadcrumbs as $i => $crumb): ?>
                        <?php $isLast = $i === count($breadcrumbs) - 1; ?>
                        <li class="breadcrumbs__item <?= $isLast ? 'is-current' : '' ?>">
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
                <main class="content-main" id="main-content">
                    <?php
                    if ($templatePath !== '' && is_file($templatePath)) {
                        require $templatePath;
                    } else {
                        echo '<p class="text-muted">Content template not found.</p>';
                    }
                    ?>
                </main>

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

    <!-- ===== FOOTER ===== -->
    <footer class="site-footer">
        <div class="site-footer__inner">
            <div class="site-footer__top">
                <div>
                    <div class="site-footer__brand-name"><?= site_e($site['name'] ?? 'Krisada.com') ?></div>
                    <p class="site-footer__tagline"><?= site_e($site['tagline'] ?? '') ?></p><br/><br/>
                    <p class="site-footer__phone">(407) 375-9915 <span class="site-footer__phone-note">Text Only</span></p><br/>
                    <p class="site-footer__address">4554 Sunderland Rd.<br>Jacksonville, FL 32210</p>

                    <div class="site-footer__social">
                        <a href="https://www.facebook.com/RealSEOLife/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="site-footer__social-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/krisada_com" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="site-footer__social-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <a href="https://www.linkedin.com/in/krisadamarketing/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="site-footer__social-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>
                <div>
                    <div class="site-footer__col-heading">Explore</div>
                    <ul class="site-footer__links">
                        <?php foreach ($footerLinks as $link): ?>
                        <li><a href="<?= site_e($link['url'] ?? '/') ?>"><?= site_e($link['label'] ?? '') ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if (!empty($footerAbout)): ?>
                <div>
                    <div class="site-footer__col-heading">About</div>
                    <ul class="site-footer__links">
                        <?php foreach ($footerAbout as $link): ?>
                        <li><a href="<?= site_e($link['url'] ?? '/') ?>"><?= site_e($link['label'] ?? '') ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <div>
                    <div class="site-footer__col-heading">Florida SEO</div>
                    <ul class="site-footer__links site-footer__locations">
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me" target="_blank" rel="noopener noreferrer">Local Florida SEO Near Me</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/jacksonville" target="_blank" rel="noopener noreferrer">Jacksonville</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/orlando" target="_blank" rel="noopener noreferrer">Orlando</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/jacksonville/st-augustine-beach" target="_blank" rel="noopener noreferrer">St. Augustine Beach</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/pensacola" target="_blank" rel="noopener noreferrer">Pensacola</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/tampa" target="_blank" rel="noopener noreferrer">Tampa</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/tampa/st-petersburg" target="_blank" rel="noopener noreferrer">St. Pete</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/miami/pompano-beach" target="_blank" rel="noopener noreferrer">Pompano Beach</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/miami/fort-lauderdale" target="_blank" rel="noopener noreferrer">Ft. Lauderdale</a></li>
                        <li><a href="https://www.realseolife.com/best-seo/in-florida-near-me/miami" target="_blank" rel="noopener noreferrer">Miami</a></li>
                    </ul>
                </div>
                <div>
                    <div class="site-footer__col-heading">Hawaii</div>
                    <ul class="site-footer__links site-footer__locations">
                        <li>Honolulu</li>
                        <li>Waikiki</li>
                        <li>Ala Moana</li>
                        <li>Kailua</li>
                        <li>Hilo</li>
                        <li>Mountain View</li>
                        <li>Kailua-Kona</li>
                    </ul>
                </div>
            </div>
            <div class="site-footer__bottom">
                <span><a href="/admin/login" style="text-decoration:none;color:inherit;" title="Admin login">&copy;</a> <?= date('Y') ?> <?= site_e($site['name'] ?? 'Krisada.com') ?>. All rights reserved.</span>
                <?php if ($isPublicView && $pageViewsText !== ''): ?>
                <span class="page-view-counter" aria-label="Page views for this page">
                    <strong>This page:</strong> <?= site_e($pageViewsText) ?>
                </span>
                <?php endif; ?>
                <span><?= site_e($site['footer_byline'] ?? '') ?></span>
            </div>
        </div>
    </footer>

    <?php if (!empty($view['json_ld'])): ?>
    <script type="application/ld+json">
    <?= json_encode($view['json_ld'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <?php endif; ?>

        <!-- Default Statcounter code for ! - Krisada.com
        https://www.krisada.com -->
        <script type="text/javascript">
        var sc_project=703654; 
        var sc_invisible=1; 
        var sc_security="102abce9"; 
        var sc_remove_link=1; 
        </script>
        <script type="text/javascript"
        src="https://www.statcounter.com/counter/counter.js"
        async></script>
        <noscript><div class="statcounter"><img class="statcounter"
        src="https://c.statcounter.com/703654/0/102abce9/1/"
        alt="Web Analytics"
        referrerPolicy="no-referrer-when-downgrade"></div></noscript>
        <!-- End of Statcounter Code -->

</body>
</html>
