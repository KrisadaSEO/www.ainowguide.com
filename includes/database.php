<?php
declare(strict_types=1);

function cms_db_is_configured(): bool
{
    return CMS_DB_HOST !== '' && CMS_DB_NAME !== '' && CMS_DB_USER !== '';
}

function cms_db_connection_state(): string
{
    if (get_cms_db() instanceof PDO) return 'mysql';
    return cms_db_is_configured() ? 'json-fallback' : 'json-files';
}

function cms_db_backend_label(): string
{
    return match (cms_db_connection_state()) {
        'mysql'         => 'MySQL',
        'json-fallback' => 'JSON Fallback',
        default         => 'JSON Files',
    };
}

function get_cms_db(): ?PDO
{
    static $pdo = false;

    if ($pdo instanceof PDO) return $pdo;

    if ($pdo === false) {
        if (!cms_db_is_configured() || !class_exists('PDO')) {
            $pdo = null;
            return null;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                CMS_DB_HOST,
                CMS_DB_PORT > 0 ? CMS_DB_PORT : 3306,
                CMS_DB_NAME,
                CMS_DB_CHARSET !== '' ? CMS_DB_CHARSET : 'utf8mb4'
            );

            $pdo = new PDO($dsn, CMS_DB_USER, CMS_DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            cms_db_bootstrap($pdo);
        } catch (Throwable $e) {
            if (DEBUG) error_log('CMS DB connection error: ' . $e->getMessage());
            $pdo = null;
        }
    }

    return $pdo instanceof PDO ? $pdo : null;
}

function cms_db_bootstrap(PDO $pdo): void
{
    static $bootstrapped = false;
    if ($bootstrapped) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_redirects (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            from_path VARCHAR(2048) NOT NULL,
            from_path_hash CHAR(64) NOT NULL,
            to_url VARCHAR(2048) NOT NULL,
            redirect_type SMALLINT UNSIGNED NOT NULL DEFAULT 301,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_admin_redirect_from_hash (from_path_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_404_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(2048) NOT NULL,
            url_hash CHAR(64) NOT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 1,
            referrer VARCHAR(2048) DEFAULT NULL,
            first_hit DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_hit DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_admin_404_url_hash (url_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $bootstrapped = true;
}
