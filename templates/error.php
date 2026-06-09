<?php
declare(strict_types=1);
$statusCode  = (int) ($view['status_code'] ?? 404);
$title       = (string) ($record['title'] ?? 'Page Not Found');
$description = (string) ($record['description'] ?? 'The page you were looking for is not available here yet.');
?>
<div class="error-page">
    <div class="error-page__code"><?= $statusCode ?></div>
    <h1 class="error-page__title"><?= site_e($title) ?></h1>
    <p class="error-page__desc"><?= site_e($description) ?></p>
    <div class="error-page__actions">
        <a href="/" class="btn btn--primary">Go Home</a>
        <a href="/channels/" class="btn btn--ghost">Browse Channels</a>
    </div>
</div>
