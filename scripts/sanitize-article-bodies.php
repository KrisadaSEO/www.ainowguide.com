<?php
declare(strict_types=1);

/**
 * Scans all content JSON files and repairs unescaped double-quote characters
 * inside HTML string fields (body, excerpt, summary, intro, content).
 *
 * Safe to run repeatedly ... files that are already valid JSON are not touched.
 * Exits 0 whether or not repairs were made (deployments should never block on
 * fixable content issues). Exits 1 only if a file could not be repaired.
 */

$root = dirname(__DIR__);
$dirs = ['content/articles', 'content/pages', 'content/downloads'];
$html_fields = ['body', 'excerpt', 'summary', 'intro', 'content'];

$fixed   = [];
$failed  = [];

foreach ($dirs as $dir) {
    foreach (glob($root . '/' . $dir . '/*.json') ?: [] as $path) {
        $raw = file_get_contents($path);

        json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            continue; // Already valid ... skip
        }

        $repaired = repair_html_fields($raw, $html_fields);

        if ($repaired === null) {
            $failed[] = $path;
            continue;
        }

        file_put_contents($path, $repaired, LOCK_EX);
        $fixed[] = $path;
    }
}

if ($fixed) {
    echo "=== Sanitize: repaired " . count($fixed) . " file(s) ===" . PHP_EOL;
    foreach ($fixed as $f) {
        echo "  Fixed: " . basename(dirname($f)) . '/' . basename($f) . PHP_EOL;
    }
    echo PHP_EOL;
}

if ($failed) {
    echo "=== Sanitize: COULD NOT REPAIR " . count($failed) . " file(s) ===" . PHP_EOL;
    foreach ($failed as $f) {
        echo "  Failed: " . basename(dirname($f)) . '/' . basename($f) . PHP_EOL;
        // Show the underlying json_decode error on the raw file
        json_decode((string) file_get_contents($f));
        echo "  Error:  " . json_last_error_msg() . PHP_EOL;
    }
    exit(1);
}

if (!$fixed) {
    echo "Sanitize: all content JSON files are valid." . PHP_EOL;
}

exit(0);

// ---------------------------------------------------------------------------

/**
 * Attempts to repair unescaped double-quotes inside HTML string fields.
 * Returns the repaired JSON string on success, or null if it still cannot
 * be decoded after the repair attempt.
 */
function repair_html_fields(string $raw, array $fields): ?string
{
    $repaired = $raw;

    foreach ($fields as $field) {
        $marker = '"' . $field . '": "';
        $pos    = strpos($repaired, $marker);
        if ($pos === false) {
            continue;
        }
        $val_start = $pos + strlen($marker);
        $repaired  = escape_unquoted_in_field($repaired, $val_start);

        json_decode($repaired, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $repaired; // Fixed ... no need to check other fields
        }
    }

    json_decode($repaired, true);
    return json_last_error() === JSON_ERROR_NONE ? $repaired : null;
}

/**
 * Scans a JSON string value starting at $val_start (the first character
 * after the opening quote) and escapes any double-quote characters that
 * are not already escaped.
 *
 * Strategy: try each unescaped quote as the "closing quote" candidate,
 * working left-to-right. For each candidate, escape all earlier unescaped
 * quotes and test whether the whole document becomes valid JSON. The first
 * candidate that yields valid JSON is the real closer.
 */
function escape_unquoted_in_field(string $raw, int $val_start): string
{
    $len       = strlen($raw);
    $unescaped = [];

    $i = $val_start;
    while ($i < $len) {
        $c = $raw[$i];
        if ($c === '\\') { $i += 2; continue; }
        if ($c === '"')  { $unescaped[] = $i; }
        $i++;
    }

    // Need at least one candidate closer (the final string-ending quote)
    if (count($unescaped) < 1) {
        return $raw;
    }

    // Try each unescaped quote as the closer, left-to-right.
    // Escape all quotes that precede the candidate, then test json_decode.
    foreach (array_keys($unescaped) as $candidate_idx) {
        $closer_pos = $unescaped[$candidate_idx];
        $to_escape  = array_slice($unescaped, 0, $candidate_idx);

        // Build the candidate repaired string (right-to-left to keep positions stable)
        $attempt = $raw;
        foreach (array_reverse($to_escape) as $pos) {
            $attempt = substr($attempt, 0, $pos) . '\\' . substr($attempt, $pos);
        }

        json_decode($attempt, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $attempt;
        }
    }

    return $raw; // No candidate worked ... return unchanged
}
