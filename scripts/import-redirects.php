<?php

/**
 * import-redirects.php
 *
 * Imports redirects from a CSV file into content/redirects/redirects.json.
 *
 * Usage:
 *   php scripts/import-redirects.php path/to/redirects.csv
 *   php scripts/import-redirects.php path/to/redirects.csv --dry-run
 *
 * CSV format (header row required):
 *   from,to,type,note
 *   /old-path/,/new-path/,301,migrated from CMS
 *
 * Supported type values: 301, 302 (defaults to 301 if omitted or invalid)
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

$redirectsFile = __DIR__ . '/../content/redirects/redirects.json';
$today         = date('Y-m-d');
$dryRun        = in_array('--dry-run', $argv, true);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function ri_normalize_path(string $path): string
{
    $path = strtolower(trim($path));
    $path = '/' . trim($path, '/') . '/';
    // Collapse double slashes
    $path = preg_replace('#/{2,}#', '/', $path) ?? $path;
    return $path;
}

function ri_abort(string $message): void
{
    fwrite(STDERR, "[ERROR] {$message}\n");
    exit(1);
}

function ri_log(string $message): void
{
    echo $message . "\n";
}

// ---------------------------------------------------------------------------
// Argument handling
// ---------------------------------------------------------------------------

$args    = array_values(array_filter($argv, static fn($a) => $a !== '--dry-run'));
$csvFile = $args[1] ?? null;

if ($csvFile === null) {
    ri_abort("No CSV file specified.\nUsage: php scripts/import-redirects.php path/to/redirects.csv [--dry-run]");
}

if (!file_exists($csvFile)) {
    ri_abort("CSV file not found: {$csvFile}");
}

// ---------------------------------------------------------------------------
// Load existing redirects
// ---------------------------------------------------------------------------

if (!file_exists($redirectsFile)) {
    ri_abort("Redirects file not found: {$redirectsFile}");
}

$raw = file_get_contents($redirectsFile);
if ($raw === false) {
    ri_abort("Could not read {$redirectsFile}");
}

$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['redirects'])) {
    ri_abort("Invalid JSON structure in {$redirectsFile} ... expected {\"redirects\": [...]}");
}

// Index existing entries by normalised `from` for fast lookup
$existing = [];
foreach ($data['redirects'] as $entry) {
    $key             = ri_normalize_path((string) ($entry['from'] ?? ''));
    $existing[$key]  = $entry;
}

// ---------------------------------------------------------------------------
// Parse CSV
// ---------------------------------------------------------------------------

$handle = fopen($csvFile, 'r');
if ($handle === false) {
    ri_abort("Could not open CSV file: {$csvFile}");
}

$header = fgetcsv($handle);
if ($header === false) {
    ri_abort("CSV file is empty.");
}

// Normalise header names
$header = array_map('strtolower', array_map('trim', $header));

$requiredColumns = ['from', 'to'];
foreach ($requiredColumns as $col) {
    if (!in_array($col, $header, true)) {
        ri_abort("CSV is missing required column: \"{$col}\"");
    }
}

$fromIdx = array_search('from', $header, true);
$toIdx   = array_search('to',   $header, true);
$typeIdx = array_search('type', $header, true);
$noteIdx = array_search('note', $header, true);

// ---------------------------------------------------------------------------
// Process rows
// ---------------------------------------------------------------------------

$imported = 0;
$skipped  = 0;
$reasons  = [];

$lineNumber = 1;

while (($row = fgetcsv($handle)) !== false) {
    $lineNumber++;

    $rawFrom = (string) ($row[$fromIdx] ?? '');
    $rawTo   = (string) ($row[$toIdx]   ?? '');
    $rawType = (string) ($row[$typeIdx] ?? '301');
    $note    = (string) ($row[$noteIdx] ?? '');

    if ($rawFrom === '' || $rawTo === '') {
        $reasons[] = "Line {$lineNumber}: skipped ... empty from or to";
        $skipped++;
        continue;
    }

    $from = ri_normalize_path($rawFrom);
    $to   = ri_normalize_path($rawTo);
    $type = in_array((int) $rawType, [301, 302], true) ? (int) $rawType : 301;

    // Self-redirect check
    if ($from === $to) {
        $reasons[] = "Line {$lineNumber}: skipped ... self-redirect ({$from})";
        $skipped++;
        continue;
    }

    // Duplicate check
    if (isset($existing[$from])) {
        $reasons[] = "Line {$lineNumber}: skipped ... duplicate from path ({$from})";
        $skipped++;
        continue;
    }

    // Chain detection: is `to` itself a `from` in existing redirects?
    if (isset($existing[$to])) {
        $chainTarget = $existing[$to]['to'] ?? '?';
        $reasons[] = "Line {$lineNumber}: warning ... chain detected ({$from} → {$to} → {$chainTarget}), importing anyway";
    }

    $entry = [
        'from'       => $from,
        'to'         => $to,
        'type'       => $type,
        'active'     => true,
        'note'       => $note,
        'source'     => 'csv-import',
        'created_at' => $today,
        'updated_at' => $today,
    ];

    $existing[$from] = $entry;
    $imported++;
    $reasons[] = "Line {$lineNumber}: imported ({$from} → {$to})";
}

fclose($handle);

// ---------------------------------------------------------------------------
// Chain detection across new entries
// ---------------------------------------------------------------------------

$allFroms = array_keys($existing);
foreach ($existing as $from => $entry) {
    $to = ri_normalize_path((string) ($entry['to'] ?? ''));
    if (in_array($to, $allFroms, true) && $to !== $from) {
        // Only warn if we haven't already flagged it
        $alreadyFlagged = false;
        foreach ($reasons as $r) {
            if (str_contains($r, "chain detected ({$from}")) {
                $alreadyFlagged = true;
                break;
            }
        }
        if (!$alreadyFlagged) {
            $chainTarget = $existing[$to]['to'] ?? '?';
            $reasons[]   = "Chain detected in existing data: {$from} → {$to} → {$chainTarget}";
        }
    }
}

// ---------------------------------------------------------------------------
// Write output
// ---------------------------------------------------------------------------

$output = [
    'redirects' => array_values($existing),
];

$json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    ri_abort("Failed to encode JSON output.");
}

// ---------------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------------

ri_log('');
ri_log('=== Redirect Import Report ===');
ri_log('');
foreach ($reasons as $line) {
    ri_log("  {$line}");
}
ri_log('');
ri_log("  Total imported : {$imported}");
ri_log("  Total skipped  : {$skipped}");
ri_log("  Total in file  : " . count($output['redirects']));
ri_log('');

if ($dryRun) {
    ri_log('[DRY RUN] No changes written.');
    ri_log('');
    exit(0);
}

if (file_put_contents($redirectsFile, $json . "\n") === false) {
    ri_abort("Could not write to {$redirectsFile}");
}

ri_log("Written to {$redirectsFile}");
ri_log('');
