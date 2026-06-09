<?php
declare(strict_types=1);

// ── Filesystem paths ──────────────────────────────────────────────────────────
define('BASE_PATH',      dirname(__DIR__) . '/');
define('DATA_PATH',      BASE_PATH . 'data/');
define('PARTIALS_PATH',  BASE_PATH . 'partials/');
define('TEMPLATES_PATH', BASE_PATH . 'templates/');
define('INCLUDES_PATH',  BASE_PATH . 'includes/');

// ── Content data paths ────────────────────────────────────────────────────────
define('CHANNELS_PATH',  DATA_PATH . 'channels/');
define('SESSIONS_PATH',  DATA_PATH . 'sessions/');
define('PAGES_PATH',     DATA_PATH . 'pages/');
define('UPLOADS_PATH',   BASE_PATH . 'assets/uploads/');

// ── Site identity ─────────────────────────────────────────────────────────────
define('SITE_NAME',      'AI Now Guide');
define('SITE_BRAND',     'Build in Public');
define('SITE_URL',       'https://www.ainowguide.com');
define('SITE_META_DESC', 'Watch a digital asset federation get built in public. Raw AI workflow sessions, website builds, SEO experiments, and portfolio decisions as they happen.');

// ── Environment ───────────────────────────────────────────────────────────────
define('ENV',   getenv('APP_ENV') ?: 'production');
define('DEBUG', ENV === 'development');

// ── CMS database (optional MySQL for redirects/404 log) ──────────────────────
define('CMS_DB_HOST',    trim((string) (getenv('CMS_DB_HOST') ?: '')));
define('CMS_DB_PORT',    (int) (getenv('CMS_DB_PORT') ?: 3306));
define('CMS_DB_NAME',    trim((string) (getenv('CMS_DB_NAME') ?: '')));
define('CMS_DB_USER',    trim((string) (getenv('CMS_DB_USER') ?: '')));
define('CMS_DB_PASS',    (string) (getenv('CMS_DB_PASS') ?: ''));
define('CMS_DB_CHARSET', trim((string) (getenv('CMS_DB_CHARSET') ?: 'utf8mb4')));

// ── GitHub sync ───────────────────────────────────────────────────────────────
if (file_exists(__DIR__ . '/github-token.php')) {
    require_once __DIR__ . '/github-token.php';
}
define('GITHUB_TOKEN',  defined('GITHUB_TOKEN')  ? GITHUB_TOKEN  : (getenv('GITHUB_TOKEN')  ?: ''));
define('GITHUB_OWNER',  'KrisadaSEO');
define('GITHUB_REPO',   'www.ainowguide.com');
define('GITHUB_BRANCH', 'main');

// ── Git repository on VPS ─────────────────────────────────────────────────────
define('GIT_REPO',   '/home/webserver005/public_html/ainowguide.com');
define('GIT_REMOTE', 'git@github.com:KrisadaSEO/www.ainowguide.com.git');
