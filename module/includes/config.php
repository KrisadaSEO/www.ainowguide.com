<?php
declare(strict_types=1);

// ─── Filesystem paths ────────────────────────────────────────────────────────
// __DIR__ here is /includes/ ... dirname() resolves to project root
define('BASE_PATH',      dirname(__DIR__) . '/');
define('DATA_PATH',      BASE_PATH . 'data/');
define('PARTIALS_PATH',  BASE_PATH . 'partials/');
define('TEMPLATES_PATH', BASE_PATH . 'templates/');
define('SIDEBAR_PATH',   BASE_PATH . 'sidebar/');
define('INCLUDES_PATH',  BASE_PATH . 'includes/');
define('AI_PATH',        BASE_PATH . 'ai/');

// ─── Site identity ────────────────────────────────────────────────────────────
define('SITE_NAME',        'RealSEOLife.com');
define('SITE_BRAND',       'The Content Lab');
define('SITE_URL',         'https://www.realseolife.com');
define('SITE_META_DESC',   'A content lab for documenting SEO case studies, experiments, and concepts. Research-driven. Evidence-based.');

// ─── Environment ─────────────────────────────────────────────────────────────
define('ENV', getenv('APP_ENV') ?: 'production');
define('DEBUG', ENV === 'development');
// ─── Admin access token ──────────────────────────────────────────────────────
// Change this to a strong random string. Required for /admin routes.
define('ADMIN_TOKEN', getenv('ADMIN_TOKEN') ?: '5124-realseo-admin');
// ─── Data sub-paths ───────────────────────────────────────────────────────────
define('CASE_STUDIES_PATH',  DATA_PATH . 'case-studies/');
define('CONCEPTS_PATH',      DATA_PATH . 'concepts/');
define('EXPERIMENTS_PATH',   DATA_PATH . 'experiments/');
define('PROOF_ENTRIES_PATH', DATA_PATH . 'proof-entries/');
define('ARTICLES_PATH',      DATA_PATH . 'articles/');
define('TAXONOMIES_PATH',    DATA_PATH . 'taxonomies/');
define('SIDEBAR_DATA_PATH',  DATA_PATH . 'sidebar/');
define('GLOSSARY_PATH',      DATA_PATH . 'glossary/');
define('STATS_PATH',         DATA_PATH . 'stats/');
define('TEAM_PATH',          DATA_PATH . 'team/');
define('PAGES_PATH',         DATA_PATH . 'pages/');
