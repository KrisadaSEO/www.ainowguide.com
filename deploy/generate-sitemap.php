<?php
declare(strict_types=1);

/**
 * Deployment sitemap entry point.
 *
 * The canonical generator uses the same bootstrap collections and routing
 * rules as the live application, including the complete portfolio directory.
 */
require __DIR__ . '/../scripts/build-sitemap.php';
