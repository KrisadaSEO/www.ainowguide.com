<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$extensions = [
    'md',
    'php',
    'json',
    'txt',
    'xml',
    'yml',
    'yaml',
    'css',
    'js',
];

$specialFiles = [
    '.htaccess',
    '.editorconfig',
    '.gitattributes',
    'robots.txt',
    'sitemap.xml',
];

$ignoreDirectories = [
    '.git',
    'vendor',
    'node_modules',
];

$needles = [
    'â€”',
    'â€“',
    'â€',
    'â†',
    'âœ',
    'â”',
    'Ã',
    '�',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $current) use ($ignoreDirectories): bool {
            if ($current->isDir()) {
                return !in_array($current->getFilename(), $ignoreDirectories, true);
            }

            return true;
        }
    )
);

$failures = [];

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $basename = $file->getFilename();
    $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

    if ($relativePath === 'scripts/check-mojibake.php') {
        continue;
    }

    $shouldScan = in_array($extension, $extensions, true) || in_array($basename, $specialFiles, true);

    if (!$shouldScan) {
        continue;
    }

    $contents = file_get_contents($file->getPathname());

    if ($contents === false) {
        $failures[] = $relativePath . ': could not read file';
        continue;
    }

    foreach ($needles as $needle) {
        $position = strpos($contents, $needle);
        if ($position === false) {
            continue;
        }

        $line = substr_count(substr($contents, 0, $position), "\n") + 1;
        $failures[] = sprintf('%s:%d contains suspicious mojibake pattern "%s"', $relativePath, $line, $needle);
        break;
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Mojibake check failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "No mojibake patterns found.\n");
