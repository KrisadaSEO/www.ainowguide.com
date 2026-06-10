<?php
// scripts/validate-blocks.php
// Validates all block-based content files for schema compliance

function validate_block($block, $index, $file) {
    $errors = [];
    if (!is_array($block)) {
        $errors[] = "Block #$index is not an object";
        return $errors;
    }
    if (empty($block['type'])) {
        $errors[] = "Block #$index missing 'type' field";
        return $errors;
    }
    $type = strtolower($block['type']);
    switch ($type) {
        case 'heading':
            if (!isset($block['level']) || $block['level'] < 1 || $block['level'] > 6) {
                $errors[] = "Block #$index (heading) missing or invalid 'level' (1-6)";
            }
            if (empty($block['text'])) {
                $errors[] = "Block #$index (heading) missing 'text'";
            }
            break;
        case 'paragraph':
            if (empty($block['text'])) {
                $errors[] = "Block #$index (paragraph) missing 'text'";
            }
            break;
        case 'image':
            if (empty($block['src'])) {
                $errors[] = "Block #$index (image) missing 'src'";
            }
            break;
        case 'list':
            if (!isset($block['items']) || !is_array($block['items'])) {
                $errors[] = "Block #$index (list) missing or invalid 'items' array";
            }
            break;
        case 'quote':
            if (empty($block['text'])) {
                $errors[] = "Block #$index (quote) missing 'text'";
            }
            break;
        case 'code':
            if (empty($block['code'])) {
                $errors[] = "Block #$index (code) missing 'code'";
            }
            break;
        case 'callout':
            if (empty($block['text'])) {
                $errors[] = "Block #$index (callout) missing 'text'";
            }
            break;
        case 'embed':
            if (empty($block['url'])) {
                $errors[] = "Block #$index (embed) missing 'url'";
            }
            break;
        case 'html':
            if (!array_key_exists('content', $block)) {
                $errors[] = "Block #$index (html) missing 'content'";
            }
            break;
        default:
            $errors[] = "Block #$index has unknown type '$type'";
            break;
    }
    return $errors;
}

function validate_file($file) {
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ["File is not valid JSON."];
    }
    if (empty($data['blocks']) || !is_array($data['blocks'])) {
        return [];
    }
    $errors = [];
    foreach ($data['blocks'] as $i => $block) {
        foreach (validate_block($block, $i, $file) as $err) {
            $errors[] = $err;
        }
    }
    return $errors;
}

function find_content_files($dir) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (preg_match('/\.json$/i', $file->getFilename())) {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

$base = __DIR__ . '/../content/pages';
$files = find_content_files($base);
$failures = 0;
foreach ($files as $file) {
    $errs = validate_file($file);
    if ($errs) {
        echo "\n$file\n";
        foreach ($errs as $err) {
            echo "  - $err\n";
        }
        $failures++;
    }
}
if ($failures === 0) {
    echo "All block content valid.\n";
    exit(0);
} else {
    echo "\n$failures file(s) with errors.\n";
    exit(1);
}
