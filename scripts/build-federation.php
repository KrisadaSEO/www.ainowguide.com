<?php

/**
 * build-federation.php
 *
 * Generates all /ai/ AI machine-readable files from config/federation.json
 * plus live published content counts.
 *
 * Output files:
 *   ai/llm.txt        ... plain text site identity for LLMs
 *   ai/llm.json       ... structured site identity + content stats
 *   ai/catalog.json   ... index of all published articles and categories
 *   ai/manifest.json  ... directory of all /ai/ endpoints
 *   ai/federation.json ... Federation v7.0 node declaration
 *
 * Usage:
 *   php scripts/build-federation.php
 *
 * Safe to run multiple times (idempotent). All output files are fully
 * regenerated on each run from config/federation.json + live content.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// ---------------------------------------------------------------------------
// Load data
// ---------------------------------------------------------------------------

$app       = site_app();
$fed       = $app['federation'];
$site      = $app['site'];
$domain    = rtrim((string) ($fed['canonical_domain'] ?? $site['domain'] ?? ''), '/');
$aiDir     = site_root_path('ai');
$generated = date('c');

$publishedArticles   = $app['published_articles']   ?? [];
$publishedCategories = $app['published_categories'] ?? [];
$publishedPages      = $app['published_pages']      ?? [];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function bf_write(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "[ERROR] Could not write: {$path}\n");
        exit(1);
    }

    echo "  Written: " . basename(dirname($path)) . '/' . basename($path) . "\n";
}

function bf_json(mixed $data): string
{
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($encoded === false) {
        fwrite(STDERR, "[ERROR] JSON encoding failed\n");
        exit(1);
    }

    return $encoded . "\n";
}

// ---------------------------------------------------------------------------
// Ensure /ai/ directory exists
// ---------------------------------------------------------------------------

if (!is_dir($aiDir)) {
    mkdir($aiDir, 0755, true);
}

// ---------------------------------------------------------------------------
// ai/llm.txt ... plain text site identity
// ---------------------------------------------------------------------------

$contentTypesList = implode("\n", array_map(
    static fn(string $t) => "- {$t}",
    (array) ($fed['supported_content_types'] ?? [])
));

$relatedList = implode("\n", array_map(
    static fn(string $p) => "- {$p}",
    (array) ($fed['related_properties'] ?? [])
));

$llmTxt = <<<TXT
# {$fed['site_name']}
Role: {$fed['node_role']}
Domain: {$domain}
Summary: {$fed['machine_summary']}

## Content types
{$contentTypesList}

## Related properties
{$relatedList}

## AI machine-readable endpoints
- {$domain}/ai/llm.json      Structured site identity and content stats
- {$domain}/ai/catalog.json  Index of all published content
- {$domain}/ai/manifest.json Directory of all /ai/ endpoints
- {$domain}/ai/federation.json Federation v7.0 node declaration

Generated: {$generated}
TXT;

bf_write($aiDir . '/llm.txt', $llmTxt);

// ---------------------------------------------------------------------------
// ai/llm.json ... structured site identity + content stats
// ---------------------------------------------------------------------------

$llmJson = [
    '_generated'        => 'Do not edit manually. Run: php scripts/build-federation.php',
    'site'              => $fed['site_name']   ?? $site['name'] ?? '',
    'role'              => $fed['node_role']   ?? '',
    'domain'            => $domain,
    'version'           => $fed['version']     ?? '',
    'summary'           => $fed['machine_summary'] ?? '',
    'content_types'     => $fed['supported_content_types'] ?? [],
    'related_properties'=> $fed['related_properties'] ?? [],
    'content_stats'     => [
        'published_articles'   => count($publishedArticles),
        'published_categories' => count($publishedCategories),
        'published_pages'      => count($publishedPages),
    ],
    'generated_at' => $generated,
];

bf_write($aiDir . '/llm.json', bf_json($llmJson));

// ---------------------------------------------------------------------------
// ai/catalog.json ... index of all published articles and categories
// ---------------------------------------------------------------------------

$articleIndex = array_map(static function (array $a) use ($domain): array {
    return [
        'title'      => (string) ($a['title']            ?? ''),
        'slug'       => (string) ($a['slug']             ?? ''),
        'url'        => $domain . (string) ($a['canonical_url'] ?? ''),
        'category'   => (string) ($a['category_primary'] ?? ''),
        'excerpt'    => (string) ($a['excerpt']          ?? ''),
        'updated_at' => (string) ($a['updated_at']       ?? ''),
    ];
}, $publishedArticles);

$categoryIndex = array_map(static function (array $c) use ($domain): array {
    return [
        'title'       => (string) ($c['title']         ?? ''),
        'slug'        => (string) ($c['slug']          ?? ''),
        'url'         => $domain . (string) ($c['canonical_url'] ?? ''),
        'description' => (string) ($c['description']   ?? ''),
    ];
}, $publishedCategories);

$catalog = [
    '_generated'   => 'Do not edit manually. Run: php scripts/build-federation.php',
    'generated_at' => $generated,
    'articles'     => $articleIndex,
    'categories'   => $categoryIndex,
];

bf_write($aiDir . '/catalog.json', bf_json($catalog));

// ---------------------------------------------------------------------------
// ai/manifest.json ... endpoint directory
// ---------------------------------------------------------------------------

$manifest = [
    '_generated' => 'Do not edit manually. Run: php scripts/build-federation.php',
    'manifest' => [
        ['file' => 'llm.txt',        'type' => 'text', 'description' => 'Plain text site identity for LLMs'],
        ['file' => 'llm.json',       'type' => 'json', 'description' => 'Structured site identity and content stats'],
        ['file' => 'catalog.json',   'type' => 'json', 'description' => 'Index of all published content'],
        ['file' => 'manifest.json',  'type' => 'json', 'description' => 'Directory of /ai/ endpoints (this file)'],
        ['file' => 'federation.json','type' => 'json', 'description' => 'Federation v7.0 node declaration'],
    ],
    'generated_at' => $generated,
];

bf_write($aiDir . '/manifest.json', bf_json($manifest));

// ---------------------------------------------------------------------------
// ai/federation.json ... Federation v7.0 node declaration
// ---------------------------------------------------------------------------

$federationOut = [
    '_generated'               => 'Do not edit manually. Run: php scripts/build-federation.php',
    'version'                  => $fed['version']                  ?? '',
    'node_role'                => $fed['node_role']                ?? '',
    'site_name'                => $fed['site_name']                ?? '',
    'canonical_domain'         => $domain,
    'machine_summary'          => $fed['machine_summary']          ?? '',
    'supported_content_types'  => $fed['supported_content_types']  ?? [],
    'related_properties'       => $fed['related_properties']       ?? [],
    'compatibility_notes'      => $fed['compatibility_notes']      ?? [],
    'generated_at'             => $generated,
];

bf_write($aiDir . '/federation.json', bf_json($federationOut));

// ---------------------------------------------------------------------------
// Done
// ---------------------------------------------------------------------------

echo "\nFederation files generated successfully.\n";
echo "  Articles indexed : " . count($publishedArticles)   . "\n";
echo "  Categories indexed: " . count($publishedCategories) . "\n";
echo "  Pages indexed    : " . count($publishedPages)      . "\n\n";
