<?php
// scripts/build-sitemap.php
// GENERATED FILE - do not edit manually. Run: php scripts/build-sitemap.php
require_once __DIR__ . '/../bootstrap.php';

$entries = site_collect_sitemap_entries();

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

$urlset = $dom->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
$dom->appendChild($urlset);

foreach ($entries as $entry) {
    $url = $dom->createElement('url');
    $urlset->appendChild($url);

    $loc = $dom->createElement('loc', htmlspecialchars(site_absolute_url((string) ($entry['loc'] ?? '/')), ENT_XML1, 'UTF-8'));
    $url->appendChild($loc);

    $rawDate = (string) ($entry['lastmod'] ?? date('Y-m-d'));
    // Normalise to W3C date-only format (YYYY-MM-DD) which GSC accepts
    $lastmod = $dom->createElement('lastmod', substr($rawDate, 0, 10));
    $url->appendChild($lastmod);
}

$sitemapPath = site_root_path('sitemap.xml');
file_put_contents($sitemapPath, $dom->saveXML());

$legacySitemapPath = site_root_path('public/sitemap.xml');
if (is_file($legacySitemapPath)) {
    unlink($legacySitemapPath);
}

echo "Sitemap generated at sitemap.xml\n";
