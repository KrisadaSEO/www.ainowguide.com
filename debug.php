<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain');

try {
    require __DIR__ . '/bootstrap.php';
    echo "BOOTSTRAP OK\n";

    $resolved = site_resolve_request('/');
    echo "RESOLVED: " . $resolved['type'] . "\n";

    $view = site_prepare_view($resolved);
    echo "VIEW OK\n";
} catch (\Throwable $e) {
    echo "ERROR: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
