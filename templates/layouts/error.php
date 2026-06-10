<?php
// Minimal error template
$view = $view ?? [];
$record = $view['record'] ?? [];
$title = $view['title'] ?? 'Error';
$description = $view['description'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <h1><a href="/">AI Now Guide</a></h1>
    </header>
    <main style="max-width:600px;margin:2rem auto;">
        <h2><?= htmlspecialchars($title) ?></h2>
        <p><?= nl2br(htmlspecialchars($description)) ?></p>
        <a href="/">Return to homepage</a>
    </main>
    <footer>
        <small>&copy; <?= date('Y') ?> AI Now Guide</small>
    </footer>
</body>
</html>
