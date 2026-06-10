<?php
/**
 * Generate a bcrypt password hash for storage/admin/admin-users.json.
 * Run from the CLI only:
 *   php tools/generate-admin-hash.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

echo "New admin password: ";
$password = trim((string) fgets(STDIN));

if (strlen($password) < 8) {
    echo "Error: password must be at least 8 characters.\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "\nBcrypt hash (cost 12):\n" . $hash . "\n\n";
echo "Paste this into storage/admin/admin-users.json as the \"passwordHash\" value.\n";
