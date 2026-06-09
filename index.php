<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/github-sync.php';
require_once __DIR__ . '/includes/admin-content.php';
require_once __DIR__ . '/includes/schema.php';

check_redirects($_SERVER['REQUEST_URI'] ?? '/');

require_once __DIR__ . '/includes/router.php';
