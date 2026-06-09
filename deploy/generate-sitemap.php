<?php
/**
 * generate-sitemap.php
 * Reads channel/session JSON data and writes sitemap.xml to project root.
 * Run via GitHub Actions before rsync deploy.
 */

declare(strict_types=1);

define('SITE_URL',       'https://www.ainowguide.com');
define('CONTENT_PATH',   __DIR__ . '/../content/');
define('CHANNELS_PATH',  CONTENT_PATH . 'channels/');
define('SESSIONS_PATH',  CONTENT_PATH . 'sessions/');

function sm_load_json(string $path): array {
    if (!file_exists($path)) { return []; }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function sm_url(string $loc, string $lastmod = '', string $changefreq = 'weekly', string $priority = '0.7'): string {
    $loc  = htmlspecialchars(rtrim(SITE_URL, '/') . '/' . ltrim($loc, '/'), ENT_XML1);
    $lm   = $lastmod ? "\n    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1) . "</lastmod>" : '';
    return "  <url>\n    <loc>{$loc}</loc>{$lm}\n    <changefreq>{$changefreq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>";
}

$urls = [];

// Static pages
$urls[] = sm_url('/', '', 'daily',   '1.0');
$urls[] = sm_url('/channels/', '', 'weekly', '0.9');
$urls[] = sm_url('/sessions/', '', 'daily',  '0.9');
$urls[] = sm_url('/about/',    '', 'monthly', '0.5');

// Channels
$channel_files = glob(CHANNELS_PATH . '*.json') ?: [];
foreach ($channel_files as $file) {
    $data = sm_load_json($file);
    if (($data['status'] ?? '') !== 'published') { continue; }
    $slug = $data['slug'] ?? '';
    if ($slug) {
        $urls[] = sm_url('/channels/' . $slug . '/', '', 'weekly', '0.8');
    }
}

// Sessions
$session_files = glob(SESSIONS_PATH . '*.json') ?: [];
foreach ($session_files as $file) {
    $data = sm_load_json($file);
    if (($data['status'] ?? '') !== 'published') { continue; }
    if (($data['visibility'] ?? 'public') !== 'public') { continue; }
    $slug    = $data['slug'] ?? '';
    $updated = $data['date'] ?? '';
    if ($slug) {
        $urls[] = sm_url('/sessions/' . $slug . '/', $updated, 'weekly', '0.7');
    }
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
     . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
     . implode("\n", $urls) . "\n"
     . '</urlset>' . "\n";

$out = __DIR__ . '/../sitemap.xml';
file_put_contents($out, $xml);
echo 'Sitemap written: ' . realpath($out) . ' (' . count($urls) . " URLs)\n";
