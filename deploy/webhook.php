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
exec('/home/webserver005/public_html/krisada.com/deploy.sh');
echo "Deploy triggered";
?>
