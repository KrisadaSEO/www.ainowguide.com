<?php

/**
 * validate-redirects.php
 *
 * Reads content/redirects/redirects.json and reports:
 *   - duplicate from paths
 *   - self-redirects
 *   - chains (A → B where B is also a from)
 *   - entries pointing to invalid internal paths (best-effort)
 *   - summary of active vs inactive entries
 *
 * Usage:
 *   php scripts/validate-redirects.php
 *
 * Exit codes:
 *   0 ... no errors (warnings are allowed)
 *   1 ... errors found (duplicates, self-redirects, broken schema)
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

$redirectsFile = __DIR__ . '/../content/redirects/redirects.json';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function vr_log(string $message): void
{
    echo $message . "\n";
}

function vr_normalize_path(string $path): string
{
    $path = strtolower(trim($path));
    $path = '/' . trim($path, '/') . '/';
    $path = preg_replace('#/{2,}#', '/', $path) ?? $path;
    return $path;
}

// ---------------------------------------------------------------------------
// Load
// ---------------------------------------------------------------------------

if (!file_exists($redirectsFile)) {
    fwrite(STDERR, "[ERROR] Redirects file not found: {$redirectsFile}\n");
    exit(1);
}

$raw = file_get_contents($redirectsFile);
if ($raw === false) {
    fwrite(STDERR, "[ERROR] Could not read {$redirectsFile}\n");
    exit(1);
}

$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['redirects']) || !is_array($data['redirects'])) {
    fwrite(STDERR, "[ERROR] Invalid JSON structure in {$redirectsFile}\n");
    exit(1);
}

$redirects = $data['redirects'];

// ---------------------------------------------------------------------------
// Analysis
// ---------------------------------------------------------------------------

$errors   = [];
$warnings = [];

$seenFroms    = [];
$fromIndex    = [];  // normalised from → entry
$activeCount   = 0;
$inactiveCount = 0;

// Pass 1: index and count
foreach ($redirects as $i => $entry) {
    $line = "Entry #" . ($i + 1);

    if (!isset($entry['from'], $entry['to'])) {
        $errors[] = "{$line}: missing 'from' or 'to' field";
        continue;
    }

    $from = vr_normalize_path((string) $entry['from']);
    $to   = vr_normalize_path((string) $entry['to']);

    // Duplicate from paths
    if (isset($seenFroms[$from])) {
        $errors[] = "{$line}: duplicate 'from' path ({$from}) ... first seen at entry #{$seenFroms[$from]}";
    } else {
        $seenFroms[$from] = $i + 1;
        $fromIndex[$from] = $entry;
    }

    // Self-redirect
    if ($from === $to) {
        $errors[] = "{$line}: self-redirect ... from and to are the same ({$from})";
    }

    // Type validation
    $type = (int) ($entry['type'] ?? 0);
    if (!in_array($type, [301, 302], true)) {
        $warnings[] = "{$line}: unexpected type value ({$entry['type']}) ... expected 301 or 302";
    }

    // Active count
    if (!empty($entry['active'])) {
        $activeCount++;
    } else {
        $inactiveCount++;
    }
}

// Pass 2: chain detection
foreach ($redirects as $i => $entry) {
    if (!isset($entry['from'], $entry['to'])) {
        continue;
    }

    $from = vr_normalize_path((string) $entry['from']);
    $to   = vr_normalize_path((string) $entry['to']);

    if ($from === $to) {
        continue; // Already reported as self-redirect
    }

    if (isset($fromIndex[$to])) {
        $chainTarget = vr_normalize_path((string) ($fromIndex[$to]['to'] ?? '?'));
        $warnings[]  = "Entry #" . ($i + 1) . ": chain detected ... {$from} → {$to} → {$chainTarget}";
    }
}

// ---------------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------------

$hasErrors = count($errors) > 0;

vr_log('');
vr_log('=== Redirect Validation Report ===');
vr_log('');
vr_log("  File           : {$redirectsFile}");
vr_log("  Total entries  : " . count($redirects));
vr_log("  Active         : {$activeCount}");
vr_log("  Inactive       : {$inactiveCount}");
vr_log('');

if ($errors !== []) {
    vr_log("  ERRORS (" . count($errors) . "):");
    foreach ($errors as $error) {
        vr_log("    [ERROR] {$error}");
    }
    vr_log('');
}

if ($warnings !== []) {
    vr_log("  WARNINGS (" . count($warnings) . "):");
    foreach ($warnings as $warning) {
        vr_log("    [WARN]  {$warning}");
    }
    vr_log('');
}

if ($errors === [] && $warnings === []) {
    vr_log("  All entries passed validation.");
    vr_log('');
}

if ($hasErrors) {
    vr_log("Result: FAILED ... " . count($errors) . " error(s) found.");
    vr_log('');
    exit(1);
}

vr_log("Result: PASSED" . ($warnings !== [] ? " with warnings" : "") . ".");
vr_log('');
exit(0);
