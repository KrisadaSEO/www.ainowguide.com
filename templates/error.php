<?php
declare(strict_types=1);

$view        = $view ?? [];
$record      = $view['record'] ?? [];
$statusCode  = (int) ($view['status_code'] ?? 404);
$title       = (string) ($record['title'] ?? 'Page Not Found');
$description = (string) ($record['description'] ?? 'The page you were looking for is not available here yet.');
?>
<div class="error-page">
    <div class="error-page__code"><?= $statusCode ?></div>
    <h1><?= site_e($title) ?></h1>
    <p><?= site_e($description) ?></p>
    <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
        <a href="/" class="btn btn-primary">Return Home</a>
        <a href="/library/" class="btn btn-outline-dark">Browse the Library</a>
    </div>
</div>
