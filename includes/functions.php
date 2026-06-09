<?php
declare(strict_types=1);

// ════════════════════════════════════════════════════════════════════════════
// CORE UTILITIES
// ════════════════════════════════════════════════════════════════════════════

function load_json(string $path): ?array
{
    if (!file_exists($path) || !is_readable($path)) return null;
    $raw = file_get_contents($path);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if (DEBUG) error_log('JSON parse error in ' . $path . ': ' . json_last_error_msg());
        return null;
    }
    return $data;
}

function sanitize_slug(string $slug): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug)));
}

function make_slug(string $str): string
{
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s\-]/', '', $str);
    $str = preg_replace('/[\s\-]+/', '-', $str);
    return trim($str, '-');
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function str_length_safe(string $str): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($str, 'UTF-8')
        : strlen($str);
}

function str_substr_safe(string $str, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null
            ? mb_substr($str, $start, null, 'UTF-8')
            : mb_substr($str, $start, $length, 'UTF-8');
    }

    return $length === null
        ? substr($str, $start)
        : substr($str, $start, $length);
}

function truncate(string $str, int $length = 160): string
{
    if (str_length_safe($str) <= $length) return $str;
    return str_substr_safe($str, 0, $length) . '...';
}

function render_inline(string $str): string
{
    $escaped = htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/',
        fn($m) => '<a href="' . $m[2] . '" rel="noopener">' . $m[1] . '</a>',
        $escaped
    );
}

function flash_set(string $key, mixed $value): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    $_SESSION['_flash'][$key] = $value;
}

function flash_consume(string $key, mixed $default = null): mixed
{
    if (session_status() !== PHP_SESSION_ACTIVE) return $default;
    if (!isset($_SESSION['_flash']) || !array_key_exists($key, $_SESSION['_flash'])) return $default;
    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    if (empty($_SESSION['_flash'])) unset($_SESSION['_flash']);
    return $value;
}

function redirect(string $path, int $status = 303): never
{
    header('Location: ' . $path, true, $status);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
// SITE SETTINGS
// ════════════════════════════════════════════════════════════════════════════

function get_site_settings(): ?array
{
    static $settings = null;
    if ($settings === null) {
        $settings = load_json(DATA_PATH . 'site-settings.json');
    }
    return $settings;
}

function get_page_data(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(PAGES_PATH . $slug . '.json');
}

// ════════════════════════════════════════════════════════════════════════════
// CHANNEL LOADERS
// ════════════════════════════════════════════════════════════════════════════

function get_channel(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(CHANNELS_PATH . $slug . '.json');
}

function get_all_channels(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = _load_all_from_dir(CHANNELS_PATH, $published_only);
        usort($cache[$key], fn($a, $b) =>
            ($a['core']['sort_order'] ?? 99) <=> ($b['core']['sort_order'] ?? 99)
        );
    }
    return $cache[$key];
}

// ════════════════════════════════════════════════════════════════════════════
// SESSION LOADERS
// ════════════════════════════════════════════════════════════════════════════

function get_session(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(SESSIONS_PATH . $slug . '.json');
}

function get_all_sessions(bool $published_only = true, string $visibility = ''): array
{
    static $cache = [];
    $key = $published_only . '|' . $visibility;
    if (!array_key_exists($key, $cache)) {
        $items = _load_all_from_dir(SESSIONS_PATH, $published_only);
        if ($visibility !== '') {
            $items = array_values(array_filter($items,
                fn($s) => ($s['core']['visibility'] ?? 'public') === $visibility
            ));
        }
        usort($items, fn($a, $b) =>
            strcmp($b['core']['date'] ?? '', $a['core']['date'] ?? '')
        );
        $cache[$key] = $items;
    }
    return $cache[$key];
}

function get_sessions_for_channel(string $channel_slug, bool $published_only = true, int $limit = 0): array
{
    $channel_slug = sanitize_slug($channel_slug);
    $all = get_all_sessions($published_only);
    $filtered = array_values(array_filter($all,
        fn($s) => sanitize_slug((string) ($s['core']['channel'] ?? '')) === $channel_slug
    ));
    return $limit > 0 ? array_slice($filtered, 0, $limit) : $filtered;
}

function get_latest_sessions(int $limit = 6, string $visibility = 'public'): array
{
    $all = get_all_sessions(true, $visibility);
    return array_slice($all, 0, $limit);
}

// ════════════════════════════════════════════════════════════════════════════
// MEMBERSHIP
// ════════════════════════════════════════════════════════════════════════════

function get_membership(): ?array
{
    static $mem = null;
    if ($mem === null) {
        $mem = load_json(DATA_PATH . 'membership.json');
    }
    return $mem;
}

// ════════════════════════════════════════════════════════════════════════════
// INTERNAL LOADER
// ════════════════════════════════════════════════════════════════════════════

function _load_all_from_dir(string $dir, bool $published_only): array
{
    $files = glob($dir . '*.json');
    if ($files === false || empty($files)) return [];
    $items = [];
    foreach ($files as $file) {
        $data = load_json($file);
        if ($data === null) continue;
        if ($published_only && !($data['meta']['published'] ?? false)) continue;
        $items[] = $data;
    }
    return $items;
}

// ════════════════════════════════════════════════════════════════════════════
// BREADCRUMB BUILDER
// ════════════════════════════════════════════════════════════════════════════

function build_breadcrumbs(string $page_type, array $data = []): array
{
    $home = [['label' => 'Home', 'url' => '/']];

    return match ($page_type) {
        'home'     => [],
        'about'    => array_merge($home, [['label' => 'About', 'url' => null]]),
        'channels' => array_merge($home, [['label' => 'Channels', 'url' => null]]),
        'channel'  => array_merge($home, [
                          ['label' => 'Channels', 'url' => '/channels'],
                          ['label' => $data['core']['title'] ?? 'Channel', 'url' => null],
                      ]),
        'sessions' => array_merge($home, [['label' => 'Sessions', 'url' => null]]),
        'session'  => array_merge($home, [
                          ['label' => 'Sessions', 'url' => '/sessions'],
                          ['label' => $data['core']['title'] ?? 'Session', 'url' => null],
                      ]),
        'contact'  => array_merge($home, [['label' => 'Contact', 'url' => null]]),
        default    => $home,
    };
}

// ════════════════════════════════════════════════════════════════════════════
// REDIRECT SYSTEM
// ════════════════════════════════════════════════════════════════════════════

function check_redirects(string $request_uri): void
{
    $rules = get_redirects();
    $path  = admin_normalize_path($request_uri);
    foreach ($rules as $rule) {
        $from = admin_normalize_path((string) ($rule['from'] ?? ''));
        if ($from === '') continue;
        if ($from === $path) {
            $to   = str_replace(["\r", "\n"], '', (string) ($rule['to'] ?? '/'));
            $code = ($rule['type'] ?? 301) === 302 ? 302 : 301;
            header('Location: ' . $to, true, $code);
            exit;
        }
    }
}

function log_404(string $request_uri): void
{
    $path = substr(trim($request_uri), 0, 2048);
    if ($path === '') return;
    $ref = substr(trim((string) ($_SERVER['HTTP_REFERER'] ?? '')), 0, 2048);

    $pdo = get_cms_db();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admin_404_log (url, url_hash, hits, referrer, first_hit, last_hit)
                VALUES (:url, :url_hash, 1, NULLIF(:referrer, ''), NOW(), NOW())
                ON DUPLICATE KEY UPDATE hits = hits + 1, last_hit = NOW()
            ");
            $stmt->execute([':url' => $path, ':url_hash' => hash('sha256', $path), ':referrer' => $ref]);
            return;
        } catch (Throwable $e) {
            if (DEBUG) error_log('404 log DB write error: ' . $e->getMessage());
        }
    }

    $file = DATA_PATH . '404-log.json';
    $log  = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    $now  = date('Y-m-d H:i:s');
    $found = false;
    foreach ($log as &$entry) {
        if (($entry['url'] ?? '') === $path) {
            $entry['hits']     = ($entry['hits'] ?? 1) + 1;
            $entry['last_hit'] = $now;
            $found = true;
            break;
        }
    }
    unset($entry);
    if (!$found) {
        $log[] = ['url' => $path, 'hits' => 1, 'first_hit' => $now, 'last_hit' => $now, 'referrer' => $ref];
    }
    if (count($log) > 500) {
        usort($log, fn($a, $b) => ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0));
        $log = array_slice($log, 0, 500);
    }
    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function get_redirects(): array
{
    $pdo = get_cms_db();
    if ($pdo instanceof PDO) {
        try {
            admin_seed_redirects_db_from_file($pdo);
            return admin_fetch_redirects_from_db($pdo);
        } catch (Throwable $e) {
            if (DEBUG) error_log('Redirect DB read error: ' . $e->getMessage());
        }
    }
    $file = DATA_PATH . 'redirects.json';
    if (!is_file($file)) return [];
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) return [];
    foreach ($data as $i => &$row) { $row['id'] = 'file:' . $i; }
    unset($row);
    return $data;
}

function save_redirects(array $rules): void
{
    $file  = DATA_PATH . 'redirects.json';
    $rules = admin_prepare_redirect_rules_for_storage($rules);
    file_put_contents($file, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function get_404_log(): array
{
    $pdo = get_cms_db();
    if ($pdo instanceof PDO) {
        try {
            admin_seed_404_log_db_from_file($pdo);
            $stmt = $pdo->query("
                SELECT url, hits, DATE_FORMAT(last_hit, '%Y-%m-%d %H:%i:%s') AS last_hit,
                       COALESCE(referrer, '') AS referrer
                FROM admin_404_log ORDER BY hits DESC, last_hit DESC
            ");
            $rows = $stmt->fetchAll();
            return is_array($rows) ? $rows : [];
        } catch (Throwable $e) {
            if (DEBUG) error_log('404 log DB read error: ' . $e->getMessage());
        }
    }
    $file = DATA_PATH . '404-log.json';
    if (!is_file($file)) return [];
    $log = json_decode((string) file_get_contents($file), true);
    if (!is_array($log)) return [];
    usort($log, fn($a, $b) => ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0));
    return $log;
}

function remove_from_404_log(string $url): void
{
    $url = trim($url);
    if ($url === '') return;
    $pdo = get_cms_db();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare('DELETE FROM admin_404_log WHERE url_hash = :url_hash');
            $stmt->execute([':url_hash' => hash('sha256', $url)]);
            return;
        } catch (Throwable $e) {}
    }
    $file = DATA_PATH . '404-log.json';
    if (!is_file($file)) return;
    $log = json_decode((string) file_get_contents($file), true);
    if (!is_array($log)) return;
    $log = array_values(array_filter($log, fn($e) => ($e['url'] ?? '') !== $url));
    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function purge_404_log(): void
{
    $pdo = get_cms_db();
    if ($pdo instanceof PDO) {
        try { $pdo->exec('TRUNCATE TABLE admin_404_log'); return; } catch (Throwable $e) {}
    }
    file_put_contents(DATA_PATH . '404-log.json', json_encode([], JSON_PRETTY_PRINT));
}

function purge_404_log_below(int $min_hits): void
{
    $pdo = get_cms_db();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare('DELETE FROM admin_404_log WHERE hits < :min_hits');
            $stmt->bindValue(':min_hits', $min_hits, PDO::PARAM_INT);
            $stmt->execute();
            return;
        } catch (Throwable $e) {}
    }
    $file = DATA_PATH . '404-log.json';
    if (!is_file($file)) return;
    $log = json_decode((string) file_get_contents($file), true);
    if (!is_array($log)) return;
    $log = array_values(array_filter($log, fn($e) => (int) ($e['hits'] ?? 0) >= $min_hits));
    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function admin_normalize_path(string $path): string
{
    $trimmed = trim($path);
    if ($trimmed === '') return '/';
    $parsed = parse_url($trimmed, PHP_URL_PATH);
    if (is_string($parsed) && $parsed !== '') $trimmed = $parsed;
    if ($trimmed === '') return '/';
    if ($trimmed[0] !== '/') $trimmed = '/' . $trimmed;
    $normalized = rtrim(strtolower($trimmed), '/');
    return $normalized === '' ? '/' : $normalized;
}

function admin_normalize_internal_redirect_target(string $target): ?string
{
    $target = trim($target);
    if ($target === '') return null;
    $parts = parse_url($target);
    if ($parts === false) return null;
    $target_host = strtolower((string) ($parts['host'] ?? ''));
    if ($target_host !== '') {
        $site_host = strtolower((string) (parse_url(SITE_URL, PHP_URL_HOST) ?: ''));
        if ($site_host === '' || $target_host !== $site_host) return null;
    }
    return admin_normalize_path((string) ($parts['path'] ?? '/'));
}

function admin_is_safe_redirect_target(string $url): bool
{
    if ($url === '' || strlen($url) > 2048) return false;
    if (str_starts_with($url, '/')) return true;
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function admin_add_redirect(string $from, string $to, int $type = 301): bool
{
    $from = admin_normalize_path($from);
    $to   = trim($to);
    $type = $type === 302 ? 302 : 301;
    if ($from === '' || $to === '') return false;
    if (strlen($from) > 2048 || !admin_is_safe_redirect_target($to)) return false;
    $normalized_target = admin_normalize_internal_redirect_target($to);
    if ($normalized_target !== null && $normalized_target === $from) return false;

    $pdo = get_cms_db();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admin_redirects (from_path, from_path_hash, to_url, redirect_type, created_at, updated_at)
                VALUES (:from_path, :from_path_hash, :to_url, :redirect_type, NOW(), NOW())
                ON DUPLICATE KEY UPDATE to_url = VALUES(to_url), redirect_type = VALUES(redirect_type), updated_at = NOW()
            ");
            $stmt->execute([
                ':from_path'      => $from,
                ':from_path_hash' => hash('sha256', $from),
                ':to_url'         => $to,
                ':redirect_type'  => $type,
            ]);
            admin_sync_redirects_file_from_db($pdo);
            return true;
        } catch (Throwable $e) {}
    }

    $rules   = get_redirects();
    $updated = false;
    foreach ($rules as &$rule) {
        if (admin_normalize_path((string) ($rule['from'] ?? '')) === $from) {
            $rule['from'] = $from; $rule['to'] = $to; $rule['type'] = $type;
            $rule['created_at'] = $rule['created_at'] ?? date('Y-m-d H:i:s');
            unset($rule['id']);
            $updated = true;
            break;
        }
    }
    unset($rule);
    if (!$updated) {
        $rules[] = ['from' => $from, 'to' => $to, 'type' => $type, 'created_at' => date('Y-m-d H:i:s')];
    }
    $rules = array_values(array_map(fn($rule) => (fn($r) => ($r))(array_diff_key($rule, ['id' => ''])), $rules));
    save_redirects($rules);
    return true;
}

function admin_delete_redirect(string $redirect_id): bool
{
    $redirect_id = trim($redirect_id);
    if ($redirect_id === '') return false;
    if (str_starts_with($redirect_id, 'db:')) {
        $pdo = get_cms_db();
        if (!$pdo instanceof PDO) return false;
        try {
            $stmt = $pdo->prepare('DELETE FROM admin_redirects WHERE id = :id');
            $stmt->bindValue(':id', (int) substr($redirect_id, 3), PDO::PARAM_INT);
            $stmt->execute();
            admin_sync_redirects_file_from_db($pdo);
            return true;
        } catch (Throwable $e) { return false; }
    }
    if (!str_starts_with($redirect_id, 'file:')) return false;
    $idx   = (int) substr($redirect_id, 5);
    $rules = get_redirects();
    if (!isset($rules[$idx])) return false;
    unset($rules[$idx]);
    $rules = array_values(array_map(fn($rule) => array_diff_key($rule, ['id' => '']), $rules));
    save_redirects($rules);
    return true;
}

function admin_prepare_redirect_rules_for_storage(array $rules): array
{
    $prepared = [];
    foreach ($rules as $rule) {
        $from = admin_normalize_path((string) ($rule['from'] ?? ''));
        $to   = trim((string) ($rule['to'] ?? ''));
        if ($from === '' || $to === '') continue;
        $prepared[] = [
            'from' => $from, 'to' => $to,
            'type' => ((int) ($rule['type'] ?? 301)) === 302 ? 302 : 301,
            'created_at' => (string) ($rule['created_at'] ?? date('Y-m-d H:i:s')),
        ];
    }
    return array_values($prepared);
}

function admin_fetch_redirects_from_db(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT CONCAT('db:', id) AS id, from_path AS `from`, to_url AS `to`,
               redirect_type AS `type`, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
        FROM admin_redirects ORDER BY created_at DESC, id DESC
    ");
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function admin_sync_redirects_file_from_db(PDO $pdo): void
{
    save_redirects(admin_fetch_redirects_from_db($pdo));
}

function admin_seed_redirects_db_from_file(PDO $pdo): void
{
    static $seeded = false;
    if ($seeded) return;
    $count = (int) ($pdo->query('SELECT COUNT(*) FROM admin_redirects')->fetchColumn() ?: 0);
    if ($count > 0) { $seeded = true; return; }
    $file = DATA_PATH . 'redirects.json';
    if (!is_file($file)) { $seeded = true; return; }
    $rows = json_decode((string) file_get_contents($file), true);
    if (!is_array($rows) || $rows === []) { $seeded = true; return; }
    $stmt = $pdo->prepare("
        INSERT INTO admin_redirects (from_path, from_path_hash, to_url, redirect_type, created_at, updated_at)
        VALUES (:from_path, :from_path_hash, :to_url, :redirect_type, :created_at, NOW())
        ON DUPLICATE KEY UPDATE to_url = VALUES(to_url), redirect_type = VALUES(redirect_type), updated_at = NOW()
    ");
    foreach ($rows as $row) {
        $from = admin_normalize_path((string) ($row['from'] ?? ''));
        $to   = trim((string) ($row['to'] ?? ''));
        if ($from === '' || $to === '') continue;
        $stmt->execute([
            ':from_path'      => $from,
            ':from_path_hash' => hash('sha256', $from),
            ':to_url'         => $to,
            ':redirect_type'  => ((int) ($row['type'] ?? 301)) === 302 ? 302 : 301,
            ':created_at'     => $row['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    $seeded = true;
}

function admin_seed_404_log_db_from_file(PDO $pdo): void
{
    static $seeded = false;
    if ($seeded) return;
    $count = (int) ($pdo->query('SELECT COUNT(*) FROM admin_404_log')->fetchColumn() ?: 0);
    if ($count > 0) { $seeded = true; return; }
    $file = DATA_PATH . '404-log.json';
    if (!is_file($file)) { $seeded = true; return; }
    $rows = json_decode((string) file_get_contents($file), true);
    if (!is_array($rows) || $rows === []) { $seeded = true; return; }
    $stmt = $pdo->prepare("
        INSERT INTO admin_404_log (url, url_hash, hits, referrer, first_hit, last_hit)
        VALUES (:url, :url_hash, :hits, NULLIF(:referrer, ''), :first_hit, :last_hit)
        ON DUPLICATE KEY UPDATE hits = VALUES(hits), last_hit = VALUES(last_hit)
    ");
    foreach ($rows as $row) {
        $url = trim((string) ($row['url'] ?? ''));
        if ($url === '') continue;
        $stmt->execute([
            ':url'       => $url,
            ':url_hash'  => hash('sha256', $url),
            ':hits'      => max(1, (int) ($row['hits'] ?? 1)),
            ':referrer'  => (string) ($row['referrer'] ?? ''),
            ':first_hit' => $row['first_hit'] ?? date('Y-m-d H:i:s'),
            ':last_hit'  => $row['last_hit'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    $seeded = true;
}

// ════════════════════════════════════════════════════════════════════════════
// ADMIN AUTH
// ════════════════════════════════════════════════════════════════════════════

function admin_get_users(): array
{
    $file  = DATA_PATH . 'admin-users.json';
    $users = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded) && isset($decoded['users'])) {
            foreach ($decoded['users'] as $u) {
                if (!is_array($u)) continue;
                $name = strtolower(trim((string) ($u['username'] ?? '')));
                $hash = (string) ($u['passwordHash'] ?? '');
                if ($name === '' || $hash === '' || ($u['active'] ?? true) !== true) continue;
                $users[$name] = $u;
            }
        }
    }
    $envUser = trim((string) (getenv('ADMIN_USERNAME') ?: ''));
    $envHash = trim((string) (getenv('ADMIN_PASSWORD_HASH') ?: ''));
    if ($envUser !== '' && $envHash !== '') {
        $users[strtolower($envUser)] = [
            'username'     => $envUser,
            'displayName'  => $envUser,
            'passwordHash' => $envHash,
            'active'       => true,
        ];
    }
    return $users;
}

function admin_find_user(string $username): ?array
{
    $needle = strtolower(trim($username));
    if ($needle === '') return null;
    return admin_get_users()[$needle] ?? null;
}

function admin_get_all_users(): array
{
    $file    = DATA_PATH . 'admin-users.json';
    if (!is_file($file)) return [];
    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded) || !isset($decoded['users'])) return [];
    return is_array($decoded['users']) ? array_values($decoded['users']) : [];
}

function admin_save_all_users(array $users): bool
{
    $file    = DATA_PATH . 'admin-users.json';
    $payload = ['users' => array_values($users)];
    return file_put_contents(
        $file,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function _admin_rate_key(string $username): string
{
    $ip = preg_replace('/[^a-f0-9:.]/', '', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return 'login-' . preg_replace('/[^a-z0-9]/', '-', strtolower($username)) . '-' . md5($ip);
}

function _admin_rate_path(string $key): string
{
    $dir = DATA_PATH . 'rate-limits';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return $dir . '/' . preg_replace('/[^a-z0-9\-]/', '', $key) . '.json';
}

function _admin_is_rate_limited(string $key): bool
{
    $path = _admin_rate_path($key);
    if (!is_file($path)) return false;
    $state = json_decode((string) file_get_contents($path), true) ?? [];
    $first = strtotime((string) ($state['first_at'] ?? ''));
    if ($first === false || (time() - $first) > 900) return false;
    return ((int) ($state['attempts'] ?? 0)) >= 5;
}

function _admin_record_failed(string $key): void
{
    $path  = _admin_rate_path($key);
    $state = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?? []) : [];
    $now   = date('c');
    $first = strtotime((string) ($state['first_at'] ?? ''));
    if ($first === false || (time() - $first) > 900) {
        $state = ['attempts' => 1, 'first_at' => $now];
    } else {
        $state['attempts'] = ((int) ($state['attempts'] ?? 0)) + 1;
    }
    file_put_contents($path, json_encode($state));
}

function _admin_clear_rate(string $key): void
{
    $path = _admin_rate_path($key);
    if (is_file($path)) unlink($path);
}

function admin_csrf_token(): string
{
    if (!isset($_SESSION['_admin_csrf']) || !is_string($_SESSION['_admin_csrf'])) {
        $_SESSION['_admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_admin_csrf'];
}

function admin_verify_csrf(?string $token): bool
{
    $stored = $_SESSION['_admin_csrf'] ?? null;
    return is_string($stored) && is_string($token) && hash_equals($stored, $token);
}

function admin_attempt_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'message' => 'Enter both username and password.'];
    }
    $rateKey = _admin_rate_key($username);
    if (_admin_is_rate_limited($rateKey)) {
        return ['ok' => false, 'message' => 'Too many failed attempts. Try again in 15 minutes.'];
    }
    $user = admin_find_user($username);
    $hash = is_array($user) ? (string) ($user['passwordHash'] ?? '') : '';
    if (!is_array($user) || $hash === '' || !password_verify($password, $hash)) {
        _admin_record_failed($rateKey);
        return ['ok' => false, 'message' => 'Invalid username or password.'];
    }
    _admin_clear_rate($rateKey);
    session_regenerate_id(true);
    $_SESSION['admin_auth'] = [
        'username'     => $user['username'],
        'displayName'  => $user['displayName'] ?? $user['username'],
        'logged_in_at' => date('c'),
    ];
    return ['ok' => true, 'message' => 'Login successful.'];
}

function admin_is_authenticated(): bool
{
    return isset($_SESSION['admin_auth']) && is_array($_SESSION['admin_auth']);
}

function admin_current_user(): ?array
{
    return admin_is_authenticated() ? $_SESSION['admin_auth'] : null;
}

function admin_require_auth(): void
{
    if (!admin_is_authenticated()) {
        header('Location: /admin/login');
        exit;
    }
}

function admin_update_password(string $username, string $currentPassword, string $newPassword): array
{
    $user = admin_find_user($username);
    if (!is_array($user)) return ['ok' => false, 'message' => 'User not found.'];
    if (!password_verify($currentPassword, (string) ($user['passwordHash'] ?? ''))) {
        return ['ok' => false, 'message' => 'Current password is incorrect.'];
    }
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'message' => 'New password must be at least 8 characters.'];
    }
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $file = DATA_PATH . 'admin-users.json';
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) return ['ok' => false, 'message' => 'Could not read users file.'];
    $updated = false;
    foreach ($data['users'] as &$u) {
        if (strtolower(trim((string) ($u['username'] ?? ''))) === strtolower(trim($username))) {
            $u['passwordHash'] = $newHash;
            $updated = true;
            break;
        }
    }
    unset($u);
    if (!$updated) return ['ok' => false, 'message' => 'User not found in file.'];
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return ['ok' => true, 'message' => 'Password updated successfully.'];
}

// ════════════════════════════════════════════════════════════════════════════
// GIT PUSH FROM VPS
// ════════════════════════════════════════════════════════════════════════════

function admin_git_run(string $command): array
{
    if (function_exists('proc_open')) {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env  = ['HOME' => '/home/webserver005', 'PATH' => '/usr/local/bin:/usr/bin:/bin'];
        $proc = proc_open($command, $descriptors, $pipes, null, $env);
        if (!is_resource($proc)) return ['could not start process', 1];
        fclose($pipes[0]);
        $out  = stream_get_contents($pipes[1]);
        $out .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [trim((string) $out), proc_close($proc)];
    }

    if (function_exists('exec')) {
        $output = [];
        $code   = 0;
        exec($command . ' 2>&1', $output, $code);
        return [trim(implode("\n", $output)), $code];
    }

    return ['Process execution is unavailable on this server.', 127];
}

function admin_git_push(string $message): array
{
    $repo   = escapeshellarg(GIT_REPO);
    $msg    = escapeshellarg($message ?: 'live edit');
    $remote = GIT_REMOTE;
    $lines  = [];

    [$out] = admin_git_run("git -C $repo add -A");
    $lines[] = $out;

    [$out, $commitCode] = admin_git_run("git -C $repo commit -m $msg");
    $lines[] = $out;

    $flat            = implode(' ', $lines);
    $nothingToCommit = str_contains($flat, 'nothing to commit') || str_contains($flat, 'nothing added to commit');

    if ($nothingToCommit) {
        return ['status' => 'nothing', 'output' => implode("\n", array_filter($lines))];
    }
    if ($commitCode !== 0) {
        return ['status' => 'error', 'output' => implode("\n", array_filter($lines))];
    }

    [$out, $pushCode] = admin_git_run("git -C $repo push $remote main");
    $lines[] = $out;

    return [
        'status' => $pushCode === 0 ? 'success' : 'error',
        'output' => implode("\n", array_filter($lines)),
    ];
}

function admin_normalize_text_input(string $value): string
{
    return str_replace(["\r\n", "\r"], "\n", trim($value));
}
