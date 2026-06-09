<?php
$secret = "supersecret2026";
$payload = file_get_contents("php://input");
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($hash, $signature)) {
    http_response_code(403);
    echo "Invalid signature";
    exit;
}

if (!function_exists('exec')) {
    http_response_code(503);
    echo "Shell execution is disabled on this server";
    exit;
}

$output = [];
$exitCode = 0;
exec('/home/webserver005/public_html/ainowguide.com/deploy.sh 2>&1', $output, $exitCode);

if ($exitCode !== 0) {
    http_response_code(500);
    echo "Deploy failed";
    if ($output !== []) {
        echo "\n" . implode("\n", $output);
    }
    exit;
}

echo "Deploy triggered";
?>
