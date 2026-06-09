<?php
/**
 * bootstrap.php ... Application core for ainowguide.com
 *
 * INTENTIONALLY MONOLITHIC. Do not split into lib/ or separate files.
 * All functions, routing, content loading, and sidebar resolution live here.
 * This single-file architecture keeps the rendering path fully traceable and
 * is what makes AI-assisted development reliable on this stack.
 *
 * Entry point: index.php -> bootstrap.php -> templates/layouts/default.php
 */
declare(strict_types=1);

const SITE_ROOT = __DIR__;

function site_root_path(string $path = ''): string
{
    if ($path === '') {
        return SITE_ROOT;
    }

    return SITE_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function site_install_config(): array
{
    static $install;

    if ($install !== null) {
        return $install;
    }

    $path = site_root_path('config/install.json');

    if (!is_file($path)) {
        $install = [];

        return $install;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    $install = is_array($decoded) ? $decoded : [];

    return $install;
}

function site_load_json_file(string $relativePath): array
{
    $fullPath = site_root_path($relativePath);

    if (!is_file($fullPath)) {
        throw new RuntimeException(sprintf('Missing JSON file: %s', $relativePath));
    }

    $decoded = json_decode((string) file_get_contents($fullPath), true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($decoded)) {
        throw new RuntimeException(sprintf('Invalid JSON structure in: %s', $relativePath));
    }

    return $decoded;
}

function site_load_collection(string $relativeDir): array
{
    $records = [];

    foreach (glob(site_root_path($relativeDir . '/*.json')) ?: [] as $fullPath) {
        $record = json_decode((string) file_get_contents($fullPath), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($record)) {
            continue;
        }

        $record['_source'] = str_replace('\\', '/', substr($fullPath, strlen(SITE_ROOT) + 1));
        $records[] = $record;
    }

    usort($records, static function (array $left, array $right): int {
        $leftOrder = (int) ($left['sort_order'] ?? 9999);
        $rightOrder = (int) ($right['sort_order'] ?? 9999);

        if ($leftOrder !== $rightOrder) {
            return $leftOrder <=> $rightOrder;
        }

        $leftDate = (string) ($left['publish_date'] ?? $left['updated_at'] ?? '');
        $rightDate = (string) ($right['publish_date'] ?? $right['updated_at'] ?? '');

        if ($leftDate !== $rightDate) {
            return strcmp($rightDate, $leftDate);
        }

        return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
    });

    return $records;
}

function site_normalize_path(string $path): string
{
    $parsedPath = parse_url($path, PHP_URL_PATH);
    $parsedPath = is_string($parsedPath) ? $parsedPath : '/';
    $parsedPath = preg_replace('#/+#', '/', $parsedPath) ?? '/';
    $trimmed = trim($parsedPath, '/');

    if ($trimmed === '') {
        return '/';
    }

    return '/' . strtolower($trimmed) . '/';
}

function site_absolute_url(string $pathOrUrl): string
{
    if ($pathOrUrl === '') {
        return site_app()['site']['domain'];
    }

    if (preg_match('#^https?://#i', $pathOrUrl) === 1) {
        return $pathOrUrl;
    }

    return rtrim(site_app()['site']['domain'], '/') . site_normalize_path($pathOrUrl);
}

function site_absolute_raw_url(string $pathOrUrl): string
{
    if ($pathOrUrl === '') {
        return rtrim(site_app()['site']['domain'], '/');
    }

    if (preg_match('#^https?://#i', $pathOrUrl) === 1) {
        return $pathOrUrl;
    }

    $path = str_replace('\\', '/', $pathOrUrl);

    if ($path === '') {
        return rtrim(site_app()['site']['domain'], '/');
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return rtrim(site_app()['site']['domain'], '/') . $path;
}

function site_path_from_url(string $pathOrUrl): string
{
    if ($pathOrUrl === '') {
        return '/';
    }

    return site_normalize_path($pathOrUrl);
}

function site_navigation_children(array $navItem): array
{
    $children = $navItem['children'] ?? null;

    if (!is_array($children)) {
        return [
            'aria_label' => '',
            'items' => [],
        ];
    }

    $items = [];
    $source = (string) ($children['source'] ?? '');

    if (isset($children['items']) && is_array($children['items'])) {
        foreach ($children['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $items[] = [
                'label' => $label,
                'url' => $url,
            ];
        }
    }

    if ($source === 'top_level_categories') {
        foreach (site_get_top_level_categories() as $category) {
            $items[] = [
                'label' => (string) ($category['title'] ?? ''),
                'url' => (string) ($category['canonical_url'] ?? ''),
            ];
        }
    } elseif ($source === 'latest_articles') {
        $latestLimit = 8;
        $seenUrls = [];

        foreach ($items as $item) {
            $url = site_normalize_path((string) ($item['url'] ?? ''));
            if ($url !== '') {
                $seenUrls[$url] = true;
            }
        }

        foreach (site_get_pinned_articles_for_target('nav:articles') as $article) {
            $articleUrl = site_normalize_path((string) ($article['canonical_url'] ?? ''));
            if ($articleUrl === '' || isset($seenUrls[$articleUrl])) {
                continue;
            }

            $seenUrls[$articleUrl] = true;
            $items[] = [
                'label' => (string)($article['title'] ?? ''),
                'url' => (string)($article['canonical_url'] ?? ''),
            ];

            if (count($items) >= $latestLimit) {
                break;
            }
        }

        if (count($items) < $latestLimit) {
            foreach (site_app()['published_articles'] as $article) {
                $articleUrl = site_normalize_path((string) ($article['canonical_url'] ?? ''));
                if ($articleUrl === '' || isset($seenUrls[$articleUrl])) {
                    continue;
                }

                $seenUrls[$articleUrl] = true;
                $items[] = [
                    'label' => (string)($article['title'] ?? ''),
                    'url' => (string)($article['canonical_url'] ?? ''),
                ];

                if (count($items) >= $latestLimit) {
                    break;
                }
            }
        }
    } elseif ($source === 'category_articles') {
        $categoryKey = trim((string) ($children['category'] ?? ''));
        $limit = max(1, (int) ($children['limit'] ?? 8));
        $category = $categoryKey !== '' ? site_find_category_by_path($categoryKey) : null;

        if ($category === null && $categoryKey !== '') {
            $category = site_find_category_by_slug($categoryKey);
        }

        if ($category !== null && site_is_published($category)) {
            $seenUrls = [];

            foreach (site_category_featured_articles($category) as $article) {
                $articleUrl = site_normalize_path((string) ($article['canonical_url'] ?? ''));
                if ($articleUrl === '' || isset($seenUrls[$articleUrl])) {
                    continue;
                }

                $seenUrls[$articleUrl] = true;
                $items[] = [
                    'label' => (string) ($article['title'] ?? ''),
                    'url' => (string) ($article['canonical_url'] ?? ''),
                ];

                if (count($items) >= $limit) {
                    break;
                }
            }

            if (count($items) < $limit) {
                foreach (site_get_articles_for_category($category) as $article) {
                    $articleUrl = site_normalize_path((string) ($article['canonical_url'] ?? ''));
                    if ($articleUrl === '' || isset($seenUrls[$articleUrl])) {
                        continue;
                    }

                    $seenUrls[$articleUrl] = true;
                    $items[] = [
                        'label' => (string) ($article['title'] ?? ''),
                        'url' => (string) ($article['canonical_url'] ?? ''),
                    ];

                    if (count($items) >= $limit) {
                        break;
                    }
                }
            }
        }
    } elseif ($source === 'portfolio_featured') {
        $constellations = array_values(site_app()['dir_categories_by_parent']['portfolio'] ?? []);
        usort($constellations, static fn($a, $b) => strcmp(
            (string)($a['title'] ?? ''), (string)($b['title'] ?? '')
        ));
        foreach ($constellations as $constellation) {
            $catPath  = trim(strtolower((string)($constellation['path'] ?? '')));
            $listings = site_app()['dir_listings_by_category'][$catPath] ?? [];
            $featured = array_values(array_filter($listings, static function (array $l): bool {
                return (string)($l['fields']['priority'] ?? '') === '1'
                    && trim((string)($l['fields']['asking_price'] ?? '')) !== '';
            }));
            if (empty($featured)) {
                continue;
            }
            $items[] = [
                'label'   => (string)($constellation['title'] ?? ''),
                'url'     => '',
                'divider' => true,
            ];
            foreach ($featured as $listing) {
                $items[] = [
                    'label' => (string)($listing['title'] ?? ''),
                    'url'   => (string)($listing['canonical_url'] ?? ''),
                    'price' => trim((string)($listing['fields']['asking_price'] ?? '')),
                ];
            }
        }
    }

    $deduped = [];
    $seen = [];

    foreach ($items as $item) {
        if ($item['divider'] ?? false) {
            $deduped[] = $item;
            continue;
        }

        $url = site_normalize_path((string) ($item['url'] ?? ''));

        if ($url === '' || isset($seen[$url])) {
            continue;
        }

        $seen[$url] = true;
        $deduped[] = $item;
    }

    return [
        'aria_label' => (string) ($children['aria_label'] ?? ''),
        'items' => $deduped,
    ];
}

function site_record_redirect_paths(array $record): array
{
    $redirectFrom = $record['redirect_from'] ?? [];

    if (is_string($redirectFrom)) {
        $redirectFrom = [$redirectFrom];
    }

    if (!is_array($redirectFrom)) {
        return [];
    }

    $paths = [];

    foreach ($redirectFrom as $path) {
        if (!is_string($path)) {
            continue;
        }

        $path = trim($path);

        if ($path === '') {
            continue;
        }

        $normalized = site_normalize_path($path);
        $paths[$normalized] = $normalized;
    }

    return array_values($paths);
}

function site_record_redirect_target(array $record): string
{
    $canonicalUrl = trim((string) ($record['canonical_url'] ?? ''));

    if ($canonicalUrl !== '') {
        return site_normalize_redirect_target($canonicalUrl);
    }

    $categoryPath = trim((string) ($record['path'] ?? ''), '/');

    if ($categoryPath !== '') {
        return site_normalize_path('/library/' . $categoryPath . '/');
    }

    return '/';
}

function site_build_record_redirect_index(array $records, array $livePaths, string $recordType): array
{
    $index = [];

    foreach ($records as $record) {
        $target = site_record_redirect_target($record);
        $slug = (string) ($record['slug'] ?? '');
        $updatedAt = (string) ($record['updated_at'] ?? $record['publish_date'] ?? date('Y-m-d'));

        foreach (site_record_redirect_paths($record) as $from) {
            if ($from === $target || isset($livePaths[$from]) || isset($index[$from])) {
                continue;
            }

            $index[$from] = [
                'from' => $from,
                'to' => $target,
                'type' => 301,
                'active' => true,
                'note' => 'Auto-generated from record redirect_from',
                'source' => 'record-redirect',
                'record_type' => $recordType,
                'record_slug' => $slug,
                'created_at' => $updatedAt,
                'updated_at' => $updatedAt,
            ];
        }
    }

    return $index;
}

function site_redirects_file_path(): string
{
    return site_root_path('content/redirects/redirects.json');
}

function site_redirect_log_path(): string
{
    $configuredPath = trim((string) (getenv('REDIRECT_404_LOG_FILE') ?: (site_install_config()['redirect_404_log_file'] ?? '')));

    if ($configuredPath !== '') {
        return $configuredPath;
    }

    return site_root_path('storage/logs/redirect-404-log.json');
}

function site_admin_token(): string
{
    $token = trim((string) getenv('ADMIN_TOKEN'));

    if ($token !== '') {
        return $token;
    }

    $token = trim((string) (site_install_config()['admin_token'] ?? ''));

    if ($token !== '') {
        return $token;
    }

    return '5124-krisada-admin';
}

function site_verify_admin_token(): bool
{
    $token = site_admin_token();

    if ($token === '') {
        return false;
    }

    return hash_equals($token, (string) ($_GET['token'] ?? $_POST['token'] ?? ''));
}

function site_redirect_admin_url(): string
{
    $token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

    if ($token === '') {
        return '/admin/redirects/';
    }

    return '/admin/redirects/?token=' . rawurlencode($token);
}

function site_normalize_redirect_target(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value) === 1) {
        return $value;
    }

    return site_normalize_path($value);
}

function site_load_redirect_rules(): array
{
    $data = site_load_json_file('content/redirects/redirects.json');
    $rules = $data['redirects'] ?? [];

    if (!is_array($rules)) {
        return [];
    }

    return array_values(array_filter($rules, static fn (mixed $rule): bool => is_array($rule)));
}

function site_save_redirect_rules(array $rules): void
{
    $payload = [
        'redirects' => array_values($rules),
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        throw new RuntimeException('Unable to encode redirect rules.');
    }

    $path = site_redirects_file_path();
    file_put_contents($path, $json . PHP_EOL, LOCK_EX);
    admin_git_commit_and_push($path, 'update redirect rules');
    site_app(true);
}

function site_read_404_log(): array
{
    $path = site_redirect_log_path();

    if (!is_file($path)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    if (!is_array($decoded)) {
        return [];
    }

    return array_values(array_filter($decoded, static fn (mixed $entry): bool => is_array($entry)));
}

function site_save_404_log(array $entries): void
{
    $path = site_redirect_log_path();
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        return;
    }

    $json = json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        return;
    }

    file_put_contents($path, $json . PHP_EOL, LOCK_EX);
}

function site_get_404_log(): array
{
    $entries = site_read_404_log();

    usort($entries, static function (array $left, array $right): int {
        $leftHits = (int) ($left['hits'] ?? 0);
        $rightHits = (int) ($right['hits'] ?? 0);

        if ($leftHits !== $rightHits) {
            return $rightHits <=> $leftHits;
        }

        return strcmp((string) ($right['last_hit'] ?? ''), (string) ($left['last_hit'] ?? ''));
    });

    return $entries;
}

function site_remove_404_log_entry(string $url): void
{
    $needle = site_normalize_path($url);
    $entries = array_values(array_filter(site_read_404_log(), static function (array $entry) use ($needle): bool {
        return site_normalize_path((string) ($entry['url'] ?? '/')) !== $needle;
    }));

    site_save_404_log($entries);
}

function site_purge_404_log(): void
{
    site_save_404_log([]);
}

function site_purge_404_log_below(int $minHits): void
{
    $entries = array_values(array_filter(site_read_404_log(), static function (array $entry) use ($minHits): bool {
        return (int) ($entry['hits'] ?? 0) >= $minHits;
    }));

    site_save_404_log($entries);
}

function site_log_404(string $requestUri): void
{
    $path = site_normalize_path($requestUri);
    $entries = site_read_404_log();
    $now = date('c');
    $referrer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $userAgent = substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 200);
    $matched = false;

    foreach ($entries as &$entry) {
        if (site_normalize_path((string) ($entry['url'] ?? '/')) !== $path) {
            continue;
        }

        $entry['url'] = $path;
        $entry['hits'] = max(0, (int) ($entry['hits'] ?? 0)) + 1;
        $entry['last_hit'] = $now;
        $entry['referrer'] = $referrer;

        if ($userAgent !== '') {
            $entry['user_agent'] = $userAgent;
        }

        $matched = true;
        break;
    }
    unset($entry);

    if (!$matched) {
        $entries[] = [
            'url' => $path,
            'hits' => 1,
            'first_hit' => $now,
            'last_hit' => $now,
            'referrer' => $referrer,
            'user_agent' => $userAgent,
            'resolved' => false,
        ];
    }

    usort($entries, static fn (array $left, array $right): int => ((int) ($right['hits'] ?? 0)) <=> ((int) ($left['hits'] ?? 0)));
    $entries = array_slice($entries, 0, 500);

    site_save_404_log($entries);
}

function site_process_redirect_admin_request(): void
{
    if (!site_verify_admin_token()) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add-redirect') {
        $from = site_normalize_path((string) ($_POST['from'] ?? ''));
        $to = site_normalize_redirect_target((string) ($_POST['to'] ?? ''));
        $type = (int) ($_POST['type'] ?? 301);

        if ($from !== '' && $to !== '' && $from !== $to) {
            $rules = site_load_redirect_rules();
            $timestamp = date('c');
            $matchedIndex = null;

            foreach ($rules as $index => $rule) {
                if (site_normalize_path((string) ($rule['from'] ?? '/')) === $from) {
                    $matchedIndex = $index;
                    break;
                }
            }

            $rule = [
                'from' => $from,
                'to' => $to,
                'type' => $type === 302 ? 302 : 301,
                'active' => true,
                'note' => (string) (($matchedIndex !== null ? ($rules[$matchedIndex]['note'] ?? '') : '') ?: 'Added via redirect admin'),
                'source' => 'redirect-admin',
                'created_at' => $matchedIndex !== null ? (string) ($rules[$matchedIndex]['created_at'] ?? $timestamp) : $timestamp,
                'updated_at' => $timestamp,
            ];

            if ($matchedIndex !== null) {
                $rules[$matchedIndex] = array_merge($rules[$matchedIndex], $rule);
            } else {
                $rules[] = $rule;
            }

            site_save_redirect_rules($rules);
            site_remove_404_log_entry($from);
        }
    } elseif ($action === 'delete-redirect') {
        $index = (int) ($_POST['index'] ?? -1);
        $rules = site_load_redirect_rules();

        if (isset($rules[$index])) {
            unset($rules[$index]);
            site_save_redirect_rules($rules);
        }
    } elseif ($action === 'dismiss-404') {
        $url = (string) ($_POST['url'] ?? '');

        if ($url !== '') {
            site_remove_404_log_entry($url);
        }
    } elseif ($action === 'purge-all-errors') {
        site_purge_404_log();
    } elseif ($action === 'purge-errors-below') {
        site_purge_404_log_below(max(1, (int) ($_POST['min_hits'] ?? 1)));
    }

    header('Location: ' . site_redirect_admin_url(), true, 303);
    exit;
}

function site_json_ld_node_id(string $pathOrUrl, string $fragment): string
{
    return site_json_ld_object_url($pathOrUrl) . '#' . ltrim($fragment, '#');
}

function site_json_ld_ref(string $pathOrUrl, string $fragment): array
{
    return ['@id' => site_json_ld_node_id($pathOrUrl, $fragment)];
}

function site_json_ld_object_url(string $pathOrUrl): string
{
    $url = site_absolute_url($pathOrUrl);
    $parts = parse_url($url);

    if (!is_array($parts)) {
        return $url;
    }

    $path = (string) ($parts['path'] ?? '');

    if ($path === '') {
        return rtrim($url, '/') . '/';
    }

    return $url;
}

function site_json_ld_clean_value(mixed $value): mixed
{
    if (is_array($value)) {
        if (array_is_list($value)) {
            $cleaned = [];

            foreach ($value as $item) {
                $filtered = site_json_ld_clean_value($item);

                if ($filtered === null || $filtered === [] || $filtered === '') {
                    continue;
                }

                $cleaned[] = $filtered;
            }

            return $cleaned;
        }

        $cleaned = [];

        foreach ($value as $key => $item) {
            $filtered = site_json_ld_clean_value($item);

            if ($filtered === null || $filtered === [] || $filtered === '') {
                continue;
            }

            $cleaned[$key] = $filtered;
        }

        return $cleaned;
    }

    if (is_string($value) && trim($value) === '') {
        return null;
    }

    return $value;
}

function site_json_ld_clean(array $node): array
{
    $cleaned = site_json_ld_clean_value($node);

    return is_array($cleaned) ? $cleaned : [];
}

function site_json_ld_property_url(string $property): string
{
    $property = trim($property);

    if ($property === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $property) === 1) {
        return rtrim($property, '/') . '/';
    }

    return 'https://' . strtolower(trim($property, '/')) . '/';
}

function site_json_ld_property_name(string $property): string
{
    $property = trim($property);

    if ($property === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $property) === 1) {
        $host = parse_url($property, PHP_URL_HOST);

        return is_string($host) ? $host : $property;
    }

    return $property;
}

function site_json_ld_unique_refs(array $refs): array
{
    $unique = [];

    foreach ($refs as $ref) {
        $id = (string) ($ref['@id'] ?? '');

        if ($id === '') {
            continue;
        }

        $unique[$id] = ['@id' => $id];
    }

    return array_values($unique);
}

function site_json_ld_unique_entities(array $entities): array
{
    $unique = [];

    foreach ($entities as $entity) {
        if (!is_array($entity) || $entity === []) {
            continue;
        }

        $key = '';

        if (!empty($entity['@id'])) {
            $key = '@id:' . (string) $entity['@id'];
        } elseif (!empty($entity['@type']) || !empty($entity['name'])) {
            $key = '@type:' . (string) ($entity['@type'] ?? '') . '|name:' . (string) ($entity['name'] ?? '');
        } else {
            $key = md5(json_encode($entity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
        }

        $unique[$key] = site_json_ld_clean($entity);
    }

    return array_values($unique);
}

function site_json_ld_article_ref(array $article): array
{
    return site_json_ld_ref((string) ($article['canonical_url'] ?? '/'), 'article');
}

function site_json_ld_category_ref(array $category): array
{
    return site_json_ld_ref((string) ($category['canonical_url'] ?? '/library/'), 'collection');
}

function site_json_ld_is_urlish(string $value): bool
{
    return str_starts_with($value, '/') || preg_match('#^https?://#i', $value) === 1;
}

function site_json_ld_ref_from_url(string $pathOrUrl): ?array
{
    $pathOrUrl = trim($pathOrUrl);

    if ($pathOrUrl === '') {
        return null;
    }

    $resolved = null;
    $host = parse_url($pathOrUrl, PHP_URL_HOST);
    $siteHost = parse_url(site_app()['site']['domain'], PHP_URL_HOST);

    if (str_starts_with($pathOrUrl, '/')) {
        $resolved = site_resolve_request($pathOrUrl);
    } elseif (is_string($host) && is_string($siteHost) && strtolower($host) === strtolower($siteHost)) {
        $resolved = site_resolve_request((string) (parse_url($pathOrUrl, PHP_URL_PATH) ?? '/'));
    }

    if (is_array($resolved) && isset($resolved['type'])) {
        $resolvedType = (string) $resolved['type'];
        $record = $resolved['record'] ?? [];
        $resolvedUrl = (string) (($record['canonical_url'] ?? '') !== '' ? $record['canonical_url'] : ($resolved['path'] ?? '/'));

        if ($resolvedType === 'article') {
            return site_json_ld_ref($resolvedUrl, 'article');
        }

        if ($resolvedType === 'category' || $resolvedType === 'toc' || $resolvedType === 'dir_root' || $resolvedType === 'dir_category') {
            return site_json_ld_ref($resolvedUrl, 'collection');
        }

        if ($resolvedType === 'page') {
            if (($record['slug'] ?? '') === 'home') {
                return site_json_ld_ref('/', 'website');
            }

            return site_json_ld_ref($resolvedUrl, 'webpage');
        }
    }

    return ['@id' => site_json_ld_object_url($pathOrUrl)];
}

function site_json_ld_normalize_object(array $item): array
{
    $normalized = $item;

    if (isset($normalized['type']) && !isset($normalized['@type'])) {
        $normalized['@type'] = $normalized['type'];
        unset($normalized['type']);
    }

    if (!empty($normalized['url']) && is_string($normalized['url'])) {
        $normalized['url'] = site_absolute_url($normalized['url']);
    }

    return site_json_ld_clean($normalized);
}

function site_json_ld_entity_from_item(mixed $item, string $defaultType = 'Thing'): array|null
{
    if (is_string($item)) {
        $item = trim($item);

        if ($item === '') {
            return null;
        }

        if (site_json_ld_is_urlish($item)) {
            return site_json_ld_ref_from_url($item);
        }

        return ['@type' => $defaultType, 'name' => $item];
    }

    if (is_array($item) && $item !== []) {
        if (!empty($item['@id'])) {
            return site_json_ld_clean($item);
        }

        if (!empty($item['url']) && empty($item['name']) && empty($item['@type']) && empty($item['type']) && is_string($item['url'])) {
            return site_json_ld_ref_from_url($item['url']);
        }

        return site_json_ld_normalize_object($item);
    }

    return null;
}

function site_json_ld_entity_list(mixed $items, string $defaultType = 'Thing'): array
{
    $items = is_array($items) ? $items : ($items === null ? [] : [$items]);
    $entities = [];

    foreach ($items as $item) {
        $entity = site_json_ld_entity_from_item($item, $defaultType);

        if ($entity !== null && $entity !== []) {
            $entities[] = $entity;
        }
    }

    return site_json_ld_unique_entities($entities);
}

function site_json_ld_ref_list(mixed $items): array
{
    $items = is_array($items) ? $items : ($items === null ? [] : [$items]);
    $refs = [];

    foreach ($items as $item) {
        $entity = site_json_ld_entity_from_item($item, 'Thing');

        if ($entity !== null && $entity !== []) {
            $refs[] = $entity;
        }
    }

    return site_json_ld_unique_entities($refs);
}

function site_json_ld_url_list(mixed $items): array
{
    $items = is_array($items) ? $items : ($items === null ? [] : [$items]);
    $urls = [];

    foreach ($items as $item) {
        if (!is_string($item)) {
            continue;
        }

        $item = trim($item);

        if ($item === '') {
            continue;
        }

        $urls[] = site_absolute_url($item);
    }

    return array_values(array_unique($urls));
}

function site_json_ld_domain_ref_list(mixed $items): array
{
    $items = is_array($items) ? $items : ($items === null ? [] : [$items]);
    $refs = [];

    foreach ($items as $item) {
        if (!is_string($item)) {
            continue;
        }

        $url = site_json_ld_property_url($item);

        if ($url === '') {
            continue;
        }

        $refs[] = ['@id' => site_json_ld_node_id($url, 'website')];
    }

    return site_json_ld_unique_refs($refs);
}

function site_json_ld_schema_config(array $record): array
{
    $schema = $record['schema'] ?? [];

    return is_array($schema) ? $schema : [];
}

function site_json_ld_breadcrumb_node(array $breadcrumbs, string $canonicalUrl): ?array
{
    if (count($breadcrumbs) < 2) {
        return null;
    }

    $items = [];

    foreach ($breadcrumbs as $index => $crumb) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => (string) ($crumb['label'] ?? ''),
            'item' => site_absolute_url((string) ($crumb['url'] ?? '/')),
        ];
    }

    return site_json_ld_clean([
        '@id' => site_json_ld_node_id($canonicalUrl, 'breadcrumb'),
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ]);
}

function site_json_ld_base_graph(): array
{
    $site = site_app()['site'];
    $federation = site_app()['federation'];
    $websiteId = site_json_ld_node_id('/', 'website');
    $personId = site_json_ld_node_id('/', 'person');
    $federationId = site_json_ld_node_id('/', 'federation-network');
    $dataCatalogId = site_json_ld_node_id('/', 'data-catalog');
    $datasetId = site_json_ld_node_id('/', 'content-dataset');

    $relatedRefs = [];
    $relatedNodes = [];

    foreach ($federation['related_properties'] ?? [] as $property) {
        $propertyName = site_json_ld_property_name((string) $property);
        $propertyUrl = site_json_ld_property_url((string) $property);

        if ($propertyName === '' || $propertyUrl === '') {
            continue;
        }

        $relatedRefs[] = ['@id' => site_json_ld_node_id($propertyUrl, 'website')];
        $relatedNodes[] = site_json_ld_clean([
            '@id' => site_json_ld_node_id($propertyUrl, 'website'),
            '@type' => 'WebSite',
            'url' => site_json_ld_object_url($propertyUrl),
            'name' => $propertyName,
            'isPartOf' => ['@id' => $federationId],
        ]);
    }

    // Build contributor Person nodes from contributors map
    $contributorsMap = $site['contributors'] ?? [];
    $contributorNodes = [];
    foreach ($contributorsMap as $slug => $contributor) {
        $nodeId = ($slug === 'krisada') ? $personId : site_json_ld_node_id('/', $slug);
        if ($slug === 'krisada') {
            // Krisada's node gets full author metadata from the author block
            $contributorNodes[] = site_json_ld_clean([
                '@id'         => $nodeId,
                '@type'       => 'Person',
                'name'        => (string) ($site['author']['name'] ?? $contributor['name'] ?? 'Krisada'),
                'description' => (string) ($site['author']['bio'] ?? $site['author']['role'] ?? ''),
                'jobTitle'    => (string) ($site['author']['role'] ?? ''),
                'url'         => (string) ($contributor['url'] ?? site_absolute_url('/')),
                'sameAs'      => array_values(array_filter((array) ($contributor['same_as'] ?? $site['author']['same_as'] ?? []))),
                'knowsAbout'  => !empty($site['author']['knows_about']) ? array_values((array) $site['author']['knows_about']) : null,
                'hasOccupation' => !empty($site['author']['occupation']) ? site_json_ld_clean([
                    '@type' => 'Occupation',
                    'name'  => (string) $site['author']['occupation'],
                    'occupationLocation' => !empty($site['author']['occupation_location']) ? [
                        '@type' => 'Country',
                        'name'  => (string) $site['author']['occupation_location'],
                    ] : null,
                ]) : null,
            ]);
        } else {
            $contributorNodes[] = site_json_ld_clean([
                '@id'         => $nodeId,
                '@type'       => 'Person',
                'name'        => (string) ($contributor['name'] ?? $slug),
                'description' => (string) ($contributor['description'] ?? ''),
                'url'         => (string) ($contributor['url'] ?? ''),
                'sameAs'      => array_values(array_filter((array) ($contributor['same_as'] ?? []))),
            ]);
        }
    }

    $graph = [
        ...$contributorNodes,
        site_json_ld_clean([
            '@id' => $websiteId,
            '@type' => 'WebSite',
            'url' => site_absolute_url('/'),
            'name' => (string) ($site['name'] ?? 'Krisada.com'),
            'description' => (string) ($site['description'] ?? ''),
            'inLanguage' => (string) ($site['locale'] ?? 'en'),
            'publisher' => ['@id' => $personId],
            'isPartOf' => ['@id' => $federationId],
            'subjectOf' => ['@id' => $dataCatalogId],
            'hasPart' => site_json_ld_unique_refs([
                site_json_ld_ref('/library/', 'webpage'),
                site_json_ld_ref('/directory/', 'collection'),
                site_json_ld_ref('/thai/', 'webpage'),
            ]),
        ]),
        site_json_ld_clean([
            '@id' => $federationId,
            '@type' => 'Collection',
            'name' => sprintf('Federation v%s Network', (string) ($federation['version'] ?? '')),
            'description' => (string) ($federation['machine_summary'] ?? ''),
            'hasPart' => site_json_ld_unique_refs(array_merge([['@id' => $websiteId]], $relatedRefs)),
        ]),
        site_json_ld_clean([
            '@id' => $dataCatalogId,
            '@type' => 'DataCatalog',
            'name' => ((string) ($site['name'] ?? 'Krisada.com')) . ' AI Content Catalog',
            'description' => 'Machine-readable content index and AI foundation layer for ' . ((string) ($site['name'] ?? 'Krisada.com')) . '. Covers AI website systems, digital independence, content systems, and search visibility.',
            'url' => site_absolute_url('/ai/catalog.json'),
            'publisher' => ['@id' => $personId],
            'dataset' => [['@id' => $datasetId]],
        ]),
        site_json_ld_clean([
            '@id' => $datasetId,
            '@type' => 'Dataset',
            'name' => ((string) ($site['name'] ?? 'Krisada.com')) . ' Content Library',
            'description' => (string) ($site['description'] ?? ''),
            'url' => site_absolute_url('/library/'),
            'creator' => ['@id' => $personId],
            'publisher' => ['@id' => $personId],
            'inLanguage' => (string) ($site['locale'] ?? 'en'),
            'keywords' => 'SEO, AI website systems, digital independence, content systems, structured data, machine-readable content, digital asset thinking',
            'includedInDataCatalog' => ['@id' => $dataCatalogId],
            'isPartOf' => ['@id' => $websiteId],
            'distribution' => [
                '@type' => 'DataDownload',
                'encodingFormat' => 'application/json',
                'contentUrl' => site_absolute_url('/ai/catalog.json'),
            ],
        ]),
    ];

    return array_merge($graph, $relatedNodes);
}

function site_build_json_ld(array $resolved, array $view): array
{
    $record = $resolved['record'];
    $schema = site_json_ld_schema_config($record);
    $site = site_app()['site'];
    $type = (string) ($resolved['type'] ?? 'page');
    $canonicalUrl = site_absolute_url((string) ($record['canonical_url'] ?? $resolved['path'] ?? '/'));
    $websiteId = site_json_ld_node_id('/', 'website');
    $personId  = site_json_ld_node_id('/', 'person');
    $atlasId   = site_json_ld_node_id('/', 'atlas');

    // Build contributor slug -> @id map for author resolution
    $contributorIds = ['krisada' => $personId, 'atlas' => $atlasId];
    foreach (array_keys($site['contributors'] ?? []) as $_cSlug) {
        if (!isset($contributorIds[$_cSlug])) {
            $contributorIds[$_cSlug] = site_json_ld_node_id('/', $_cSlug);
        }
    }
    $dataCatalogId = site_json_ld_node_id('/', 'data-catalog');
    $datasetId = site_json_ld_node_id('/', 'content-dataset');
    $graph = site_json_ld_base_graph();
    $breadcrumbNode = site_json_ld_breadcrumb_node($view['breadcrumbs'] ?? [], $canonicalUrl);
    $breadcrumbRef = $breadcrumbNode !== null ? ['@id' => (string) $breadcrumbNode['@id']] : null;

    if ($type === 'article') {
        $pageId = site_json_ld_node_id($canonicalUrl, 'webpage');
        $articleId = site_json_ld_node_id($canonicalUrl, 'article');
        $isPartOf = [['@id' => $websiteId]];
        $about = [];

        if (!empty($view['primary_category'])) {
            $isPartOf[] = site_json_ld_category_ref($view['primary_category']);
            $about[] = [
                '@type' => 'Thing',
                'name' => (string) ($view['primary_category']['title'] ?? ''),
            ];
        }

        foreach ($record['category_secondary'] ?? [] as $secondaryPath) {
            $secondaryCategory = site_find_category_by_path((string) $secondaryPath);
            if ($secondaryCategory !== null) {
                $isPartOf[] = site_json_ld_category_ref($secondaryCategory);
            }
        }

        $about = site_json_ld_unique_entities(array_merge($about, site_json_ld_entity_list($schema['about'] ?? [])));
        $mentions = site_json_ld_unique_entities(array_merge(
            array_map('site_json_ld_article_ref', $view['related_articles'] ?? []),
            site_json_ld_ref_list($schema['mentions'] ?? []),
            site_json_ld_domain_ref_list($schema['related_domains'] ?? [])
        ));
        $isPartOf = site_json_ld_unique_entities(array_merge($isPartOf, site_json_ld_ref_list($schema['is_part_of'] ?? [])));
        $citation = site_json_ld_url_list($schema['citation'] ?? []);
        $isBasedOn = site_json_ld_ref_list($schema['is_based_on'] ?? []);

        $graph[] = site_json_ld_clean([
            '@id' => $pageId,
            '@type' => 'WebPage',
            'url' => $canonicalUrl,
            'name' => (string) ($record['title'] ?? ''),
            'description' => (string) ($view['description'] ?? ''),
            'isPartOf' => ['@id' => $websiteId],
            'mainEntity' => ['@id' => $articleId],
            'breadcrumb' => $breadcrumbRef,
            'mentions' => $mentions,
        ]);

        // authors array: ["krisada", "atlas"] -- falls back to ["krisada"] for existing content
        $authorSlugs = array_map('strtolower', (array) ($record['authors'] ?? []));
        if (empty($authorSlugs)) {
            // backward compat: respect legacy co_author field
            $authorSlugs = ['krisada'];
            foreach (array_map('strtolower', (array) ($record['co_author'] ?? [])) as $_coSlug) {
                if ($_coSlug !== 'krisada') {
                    $authorSlugs[] = $_coSlug;
                }
            }
        }
        $authorRefs = [];
        foreach ($authorSlugs as $_aSlug) {
            if (isset($contributorIds[$_aSlug])) {
                $authorRefs[] = ['@id' => $contributorIds[$_aSlug]];
            }
        }
        if (empty($authorRefs)) {
            $authorRefs = [['@id' => $personId]];
        }
        $authorField = count($authorRefs) === 1 ? $authorRefs[0] : $authorRefs;

        $graph[] = site_json_ld_clean([
            '@id' => $articleId,
            '@type' => 'Article',
            'headline' => (string) ($record['title'] ?? ''),
            'description' => (string) ($view['description'] ?? ''),
            'datePublished' => (string) ($record['publish_date'] ?? ''),
            'dateModified' => (string) ($record['updated_at'] ?? ''),
            'author' => $authorField,
            'publisher' => ['@id' => $personId],
            'mainEntityOfPage' => ['@id' => $pageId],
            'isPartOf' => site_json_ld_unique_entities($isPartOf),
            'about' => $about,
            'mentions' => $mentions,
            'citation' => $citation,
            'isBasedOn' => $isBasedOn,
        ]);

        $audioSummaryUrl = (string) ($record['audio_summary_url'] ?? '');
        if ($audioSummaryUrl !== '') {
            $audioTranscriptText = (string) ($record['audio_transcript'] ?? '');
            $graph[] = site_json_ld_clean([
                '@id'            => site_json_ld_node_id($canonicalUrl, 'audio-summary'),
                '@type'          => 'AudioObject',
                'name'           => 'Audio Summary: ' . (string) ($record['title'] ?? ''),
                'description'    => 'AI-generated audio summary of ' . (string) ($record['title'] ?? ''),
                'contentUrl'     => site_absolute_raw_url($audioSummaryUrl),
                'encodingFormat' => 'audio/mpeg',
                'transcript'     => $audioTranscriptText !== '' ? $audioTranscriptText : null,
                'author'         => ['@id' => $personId],
                'inLanguage'     => 'en',
            ]);
        }

        $faqItems = $schema['faq'] ?? [];
        if (!empty($faqItems) && is_array($faqItems)) {
            $questions = [];
            foreach ($faqItems as $item) {
                $question = (string) ($item['question'] ?? '');
                $answer   = (string) ($item['answer'] ?? '');
                if ($question === '' || $answer === '') {
                    continue;
                }
                $questions[] = [
                    '@type' => 'Question',
                    'name'  => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $answer,
                    ],
                ];
            }
            if (!empty($questions)) {
                $graph[] = [
                    '@id'        => $canonicalUrl . '#faqpage',
                    '@type'      => 'FAQPage',
                    'mainEntity' => $questions,
                ];
            }
        }
    } elseif ($type === 'category') {
        $collectionId = site_json_ld_node_id($canonicalUrl, 'collection');
        $isPartOf = [['@id' => $websiteId]];

        if (!empty($record['parent_slug'])) {
            $parentCategory = site_find_category_by_slug((string) $record['parent_slug']);

            if ($parentCategory !== null) {
                $isPartOf[] = site_json_ld_category_ref($parentCategory);
            }
        }

        $hasPart = [];

        foreach ($view['child_categories'] ?? [] as $childCategory) {
            $hasPart[] = site_json_ld_category_ref($childCategory);
        }

        foreach ($view['featured_articles'] ?? [] as $article) {
            $hasPart[] = site_json_ld_article_ref($article);
        }

        $mentions = [];

        foreach ($view['related_categories'] ?? [] as $relatedCategory) {
            $mentions[] = site_json_ld_category_ref($relatedCategory);
        }

        $hasPart = site_json_ld_unique_entities(array_merge($hasPart, site_json_ld_ref_list($schema['has_part'] ?? [])));
        $mentions = site_json_ld_unique_entities(array_merge($mentions, site_json_ld_ref_list($schema['mentions'] ?? []), site_json_ld_domain_ref_list($schema['related_domains'] ?? [])));
        $isPartOf = site_json_ld_unique_entities(array_merge($isPartOf, site_json_ld_ref_list($schema['is_part_of'] ?? [])));
        $about = site_json_ld_entity_list($schema['about'] ?? []);
        $citation = site_json_ld_url_list($schema['citation'] ?? []);
        $isBasedOn = site_json_ld_ref_list($schema['is_based_on'] ?? []);

        $graph[] = site_json_ld_clean([
            '@id' => $collectionId,
            '@type' => 'CollectionPage',
            'url' => $canonicalUrl,
            'name' => (string) ($record['title'] ?? ''),
            'description' => (string) ($view['description'] ?? ''),
            'isPartOf' => $isPartOf,
            'hasPart' => $hasPart,
            'mentions' => $mentions,
            'about' => $about,
            'breadcrumb' => $breadcrumbRef,
            'citation' => $citation,
            'isBasedOn' => $isBasedOn,
        ]);

        $parentDatasetRef = (isset($parentCategory) && is_array($parentCategory))
            ? ['@id' => site_json_ld_node_id((string) ($parentCategory['canonical_url'] ?? '/library/'), 'dataset')]
            : ['@id' => $datasetId];

        $graph[] = site_json_ld_clean([
            '@id' => site_json_ld_node_id($canonicalUrl, 'dataset'),
            '@type' => 'Dataset',
            'name' => (string) ($record['title'] ?? ''),
            'description' => (string) ($view['description'] ?? ''),
            'url' => $canonicalUrl,
            'creator' => ['@id' => $personId],
            'publisher' => ['@id' => $personId],
            'inLanguage' => 'en',
            'includedInDataCatalog' => ['@id' => $dataCatalogId],
            'isPartOf' => $parentDatasetRef,
        ]);

        $allCategoryArticles = array_merge($view['featured_articles'] ?? [], $view['recent_articles'] ?? []);
        $seenArticleSlugs = [];
        $uniqueCategoryArticles = [];
        foreach ($allCategoryArticles as $a) {
            $aSlug = (string) ($a['slug'] ?? '');
            if ($aSlug !== '' && !isset($seenArticleSlugs[$aSlug])) {
                $seenArticleSlugs[$aSlug] = true;
                $uniqueCategoryArticles[] = $a;
            }
        }
        if (!empty($uniqueCategoryArticles)) {
            $catListItems = [];
            foreach ($uniqueCategoryArticles as $i => $a) {
                $catListItems[] = [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => (string) ($a['title'] ?? ''),
                    'url'      => site_absolute_url((string) ($a['canonical_url'] ?? '/')),
                ];
            }
            $graph[] = [
                '@id'             => site_json_ld_node_id($canonicalUrl, 'articles'),
                '@type'           => 'ItemList',
                'name'            => (string) ($record['title'] ?? '') . ' -- Articles',
                'numberOfItems'   => count($catListItems),
                'itemListElement' => $catListItems,
            ];
        }
    } elseif ($type === 'toc') {
        $focusCategory = $resolved['focus_category'] ?? null;

        if ($focusCategory !== null && is_array($focusCategory)) {
            // Section page: /table-of-contents/{section}/
            $sectionSlug      = (string) ($focusCategory['slug'] ?? '');
            $sectionUrl       = site_absolute_url('/table-of-contents/' . $sectionSlug . '/');
            $rootDirUrl       = site_absolute_url('/table-of-contents/');
            $collectionId     = site_json_ld_node_id((string) ($focusCategory['canonical_url'] ?? '/library/'), 'collection');
            $rootCollectionId = site_json_ld_node_id($rootDirUrl, 'collection');

            $childCategories = site_get_child_categories($focusCategory);
            $hasPart = [];
            foreach ($childCategories as $child) {
                $hasPart[] = site_json_ld_category_ref($child);
            }

            $graph[] = site_json_ld_clean([
                '@id'         => $collectionId,
                '@type'       => 'CollectionPage',
                'url'         => $sectionUrl,
                'name'        => (string) ($focusCategory['title'] ?? ''),
                'description' => (string) ($focusCategory['seo']['description'] ?? $focusCategory['description'] ?? ''),
                'isPartOf'    => ['@id' => $rootCollectionId],
                'hasPart'     => $hasPart,
                'breadcrumb'  => $breadcrumbRef,
            ]);

            $sectionArticles = site_get_articles_for_category($focusCategory);
            if (!empty($sectionArticles)) {
                $listItems = [];
                foreach ($sectionArticles as $i => $article) {
                    $listItems[] = [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'name'     => (string) ($article['title'] ?? ''),
                        'url'      => site_absolute_url((string) ($article['canonical_url'] ?? '/')),
                    ];
                }
                $graph[] = [
                    '@id'            => site_json_ld_node_id((string) ($focusCategory['canonical_url'] ?? '/library/'), 'articles'),
                    '@type'          => 'ItemList',
                    'name'           => (string) ($focusCategory['title'] ?? '') . ' -- Articles',
                    'numberOfItems'  => count($listItems),
                    'itemListElement' => $listItems,
                ];
            }
        } else {
            // Root TOC: /table-of-contents/
            $collectionId = site_json_ld_node_id($canonicalUrl, 'collection');
            $hasPart = [];

            foreach ($view['top_level_categories'] ?? [] as $category) {
                $hasPart[] = site_json_ld_category_ref($category);
            }

            $hasPart   = site_json_ld_unique_entities(array_merge($hasPart, site_json_ld_ref_list($schema['has_part'] ?? [])));
            $mentions  = site_json_ld_unique_entities(array_merge(site_json_ld_ref_list($schema['mentions'] ?? []), site_json_ld_domain_ref_list($schema['related_domains'] ?? [])));
            $about     = site_json_ld_entity_list($schema['about'] ?? []);
            $citation  = site_json_ld_url_list($schema['citation'] ?? []);
            $isBasedOn = site_json_ld_ref_list($schema['is_based_on'] ?? []);
            $mainEntity = site_json_ld_entity_from_item($schema['main_entity'] ?? null, 'Thing');

            $graph[] = site_json_ld_clean([
                '@id'         => $collectionId,
                '@type'       => 'CollectionPage',
                'url'         => $canonicalUrl,
                'name'        => (string) ($record['title'] ?? ''),
                'description' => (string) ($view['description'] ?? ''),
                'isPartOf'    => ['@id' => $websiteId],
                'hasPart'     => $hasPart,
                'mentions'    => $mentions,
                'about'       => $about,
                'mainEntity'  => $mainEntity,
                'breadcrumb'  => $breadcrumbRef,
                'citation'    => $citation,
                'isBasedOn'   => $isBasedOn,
            ]);
        }
    } elseif ($type === 'dir_root') {
        $collectionId = site_json_ld_node_id($canonicalUrl, 'collection');
        $hasPart      = [];

        foreach ($view['top_categories'] ?? [] as $category) {
            $hasPart[] = ['@id' => site_json_ld_object_url((string) ($category['canonical_url'] ?? '/directory/'))];
        }

        $graph[] = site_json_ld_clean([
            '@id'         => $collectionId,
            '@type'       => 'CollectionPage',
            'url'         => $canonicalUrl,
            'name'        => (string) ($record['title'] ?? ''),
            'description' => (string) ($view['description'] ?? ''),
            'isPartOf'    => ['@id' => $websiteId],
            'hasPart'     => $hasPart,
            'breadcrumb'  => $breadcrumbRef,
        ]);
    } elseif ($type === 'dir_category') {
        $collectionId   = site_json_ld_node_id($canonicalUrl, 'collection');
        $hasPart        = [];

        foreach ($view['child_categories'] ?? [] as $child) {
            $hasPart[] = ['@id' => site_json_ld_object_url((string) ($child['canonical_url'] ?? '/directory/'))];
        }

        foreach ($view['listings'] ?? [] as $listing) {
            $hasPart[] = ['@id' => site_json_ld_object_url((string) ($listing['canonical_url'] ?? '/directory/'))];
        }

        $parentUrl = null;
        $ancestors = $view['ancestor_categories'] ?? [];
        if (!empty($ancestors)) {
            $parentUrl = site_json_ld_object_url((string) (end($ancestors)['canonical_url'] ?? '/directory/'));
        }

        $graph[] = site_json_ld_clean([
            '@id'         => $collectionId,
            '@type'       => 'CollectionPage',
            'url'         => $canonicalUrl,
            'name'        => (string) ($record['title'] ?? ''),
            'description' => (string) ($record['seo']['description'] ?? $record['description'] ?? ''),
            'isPartOf'    => $parentUrl !== null ? ['@id' => $parentUrl] : ['@id' => site_json_ld_object_url('/directory/')],
            'hasPart'     => $hasPart,
            'breadcrumb'  => $breadcrumbRef,
        ]);
    } elseif ($type === 'dir_listing') {
        $listingId  = site_json_ld_node_id($canonicalUrl, 'listing');
        $parentUrl  = site_json_ld_object_url((string) ($view['parent_category']['canonical_url'] ?? '/directory/'));
        $schemaType = (string) ($resolved['dir_schema']['schema_type'] ?? 'Thing');

        $graph[] = site_json_ld_clean([
            '@id'         => $listingId,
            '@type'       => $schemaType,
            'url'         => $canonicalUrl,
            'name'        => (string) ($record['title'] ?? ''),
            'description' => (string) ($record['seo']['description'] ?? $record['excerpt'] ?? $record['description'] ?? ''),
            'isPartOf'    => ['@id' => $parentUrl],
            'breadcrumb'  => $breadcrumbRef,
        ]);
    } elseif ($type === 'glossary_index') {
        $glossaryDef  = $resolved['glossary'] ?? [];
        $glossarySlug = (string) ($glossaryDef['slug'] ?? 'main');
        $termSetId    = site_json_ld_node_id($canonicalUrl, 'defined-term-set');
        $hasPart      = [];
        foreach ($view['glossary_terms'] ?? [] as $t) {
            $tSlug    = (string) ($t['slug'] ?? '');
            $tGlossary = (string) ($t['glossary'] ?? 'main');
            $tPath    = $tGlossary === 'main' ? '/glossary/' . $tSlug . '/' : '/glossary/' . $tGlossary . '/' . $tSlug . '/';
            $hasPart[] = ['@id' => site_json_ld_node_id(site_absolute_url($tPath), 'defined-term')];
        }
        $graph[] = site_json_ld_clean([
            '@id'         => $termSetId,
            '@type'       => ['DefinedTermSet', 'CollectionPage'],
            'url'         => $canonicalUrl,
            'name'        => (string) ($glossaryDef['title'] ?? 'Glossary'),
            'description' => (string) ($glossaryDef['description'] ?? ''),
            'isPartOf'    => ['@id' => $websiteId],
            'hasPart'     => $hasPart,
            'breadcrumb'  => $breadcrumbRef,
        ]);
    } elseif ($type === 'glossary_term') {
        $pageId       = site_json_ld_node_id($canonicalUrl, 'webpage');
        $termId       = site_json_ld_node_id($canonicalUrl, 'defined-term');
        $glossarySlug = (string) ($record['glossary'] ?? 'main');
        $glossaryPath = $glossarySlug === 'main' ? '/glossary/' : '/glossary/' . $glossarySlug . '/';
        $termSetId    = site_json_ld_node_id(site_absolute_url($glossaryPath), 'defined-term-set');
        $alsoKnownAs  = array_values(array_filter((array) ($record['also_known_as'] ?? [])));
        $graph[] = site_json_ld_clean([
            '@id'        => $pageId,
            '@type'      => 'WebPage',
            'url'        => $canonicalUrl,
            'name'       => (string) ($record['term'] ?? ''),
            'description'=> (string) ($record['short_def'] ?? $view['description'] ?? ''),
            'isPartOf'   => ['@id' => $websiteId],
            'mainEntity' => ['@id' => $termId],
            'breadcrumb' => $breadcrumbRef,
        ]);
        $graph[] = site_json_ld_clean([
            '@id'              => $termId,
            '@type'            => 'DefinedTerm',
            'name'             => (string) ($record['term'] ?? ''),
            'description'      => (string) ($record['short_def'] ?? ''),
            'termCode'         => (string) ($record['slug'] ?? ''),
            'alternateName'    => !empty($alsoKnownAs) ? $alsoKnownAs : null,
            'inDefinedTermSet' => ['@id' => $termSetId],
            'url'              => $canonicalUrl,
        ]);
    } else {
        $pageId = site_json_ld_node_id($canonicalUrl, 'webpage');
        $mentions = [];

        foreach ($view['featured_categories'] ?? [] as $category) {
            $mentions[] = site_json_ld_category_ref($category);
        }

        foreach ($view['featured_articles'] ?? [] as $article) {
            $mentions[] = site_json_ld_article_ref($article);
        }

        $mentions = site_json_ld_unique_entities(array_merge($mentions, site_json_ld_ref_list($schema['mentions'] ?? []), site_json_ld_domain_ref_list($schema['related_domains'] ?? [])));
        $about = site_json_ld_entity_list($schema['about'] ?? []);
        $hasPart = site_json_ld_ref_list($schema['has_part'] ?? []);
        $isPartOf = site_json_ld_unique_entities(array_merge([['@id' => $websiteId]], site_json_ld_ref_list($schema['is_part_of'] ?? [])));
        $citation = site_json_ld_url_list($schema['citation'] ?? []);
        $isBasedOn = site_json_ld_ref_list($schema['is_based_on'] ?? []);
        $mainEntity = site_json_ld_entity_from_item($schema['main_entity'] ?? null, 'Thing');

        $pageNode = [
            '@id' => $pageId,
            '@type' => 'WebPage',
            'url' => $canonicalUrl,
            'name' => (string) ($record['title'] ?? ''),
            'description' => (string) ($view['description'] ?? ''),
            'isPartOf' => $isPartOf,
            'mentions' => $mentions,
            'about' => $about,
            'hasPart' => $hasPart,
            'breadcrumb' => $breadcrumbRef,
            'citation' => $citation,
            'isBasedOn' => $isBasedOn,
        ];

        if (($record['slug'] ?? '') === 'home') {
            $pageNode['mainEntity'] = ['@id' => $websiteId];
            $pageNode['about'] = site_json_ld_unique_entities(array_merge([['@id' => $personId]], $about));

            $audioTranscript = (string) ($record['audio_transcript'] ?? '');
            if ($audioTranscript !== '') {
                $audioObjectId = site_json_ld_node_id('/', 'audio-summary');
                $pageNode['audio'] = ['@id' => $audioObjectId];
                $graph[] = site_json_ld_clean([
                    '@id'            => $audioObjectId,
                    '@type'          => 'AudioObject',
                    'name'           => 'Homepage Audio Summary',
                    'description'    => 'AI-generated audio summary of Krisada.com',
                    'contentUrl'     => site_absolute_url('/assets/audio/homepage.mp3'),
                    'encodingFormat' => 'audio/mpeg',
                    'transcript'     => $audioTranscript,
                    'author'         => ['@id' => $personId],
                    'inLanguage'     => 'en',
                ]);
            }
        } elseif ($mainEntity !== null) {
            $pageNode['mainEntity'] = $mainEntity;
        }

        $graph[] = site_json_ld_clean($pageNode);
    }

    if ($breadcrumbNode !== null) {
        $graph[] = $breadcrumbNode;
    }

    return [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter($graph, static function (array $node): bool {
            return $node !== [];
        })),
    ];
}

function site_is_published(array $record): bool
{
    return ($record['status'] ?? '') === 'published';
}

function site_record_index(array $records, string $field): array
{
    $indexed = [];

    foreach ($records as $record) {
        if (!isset($record[$field])) {
            continue;
        }

        $indexed[(string) $record[$field]] = $record;
    }

    return $indexed;
}

function site_group_records(array $records, string $field): array
{
    $grouped = [];

    foreach ($records as $record) {
        $grouped[(string) ($record[$field] ?? '')][] = $record;
    }

    return $grouped;
}

function site_app(bool $refresh = false): array
{
    static $app;

    if ($refresh || $app === null) {
        $site = site_load_json_file('config/site.json');

        // Merge machine-specific install.json over site.json if it exists.
        // install.json is gitignored ... use config/install.example.json as the template.
        $install = site_install_config();
        foreach (['site_name', 'domain', 'environment'] as $key) {
            if (isset($install[$key]) && $install[$key] !== '') {
                if ($key === 'site_name') {
                    $site['name'] = $install[$key];
                } else {
                    $site[$key] = $install[$key];
                }
            }
        }

        $routes = site_load_json_file('config/routes.json');
        $tokens = site_load_json_file('config/design-tokens.json');
        $flags = site_load_json_file('config/feature-flags.json');
        $federation = is_file(site_root_path('config/federation.json')) ? site_load_json_file('config/federation.json') : [];

        $pages = site_load_collection('content/pages');
        $categories = site_load_collection('content/categories');
        $articles = site_load_collection('content/articles');
        $sidebarProfiles = site_load_collection('content/sidebars');
        $offers = site_load_collection('content/offers');
        $downloads = is_dir(site_root_path('content/downloads')) ? site_load_collection('content/downloads') : [];
        $channels = is_dir(site_root_path('content/channels')) ? site_load_collection('content/channels') : [];
        $sessions = is_dir(site_root_path('content/sessions')) ? site_load_collection('content/sessions') : [];
        $redirectData = site_load_json_file('content/redirects/redirects.json');
        $thaiResources = is_file(site_root_path('content/thai/resources.json')) ? site_load_json_file('content/thai/resources.json') : ['resources' => []];
        $federationResources = is_file(site_root_path('content/federation/resources.json')) ? site_load_json_file('content/federation/resources.json') : ['resources' => []];

        $dirSchemas    = is_dir(site_root_path('content/directory/schemas'))    ? site_load_collection('content/directory/schemas')    : [];
        $dirCategories = is_dir(site_root_path('content/directory/categories')) ? site_load_collection('content/directory/categories') : [];
        $dirListings   = is_dir(site_root_path('content/directory/listings'))   ? site_load_collection('content/directory/listings')   : [];

        $publishedPages = array_values(array_filter($pages, 'site_is_published'));
        $publishedCategories = array_values(array_filter($categories, 'site_is_published'));
        $publishedArticles = array_values(array_filter($articles, 'site_is_published'));
        $publishedOffers = array_values(array_filter($offers, 'site_is_published'));
        $publishedDownloads     = array_values(array_filter($downloads, 'site_is_published'));
        $publishedDirCategories = array_values(array_filter($dirCategories, 'site_is_published'));
        $publishedDirListings   = array_values(array_filter($dirListings, 'site_is_published'));
        $publishedChannels = array_values(array_filter($channels, 'site_is_published'));
        $publishedSessions = array_values(array_filter($sessions, 'site_is_published'));

        $redirects = array_values(array_filter($redirectData['redirects'] ?? [], static function (array $redirect): bool {
            return (bool) ($redirect['active'] ?? false);
        }));

        $redirectIndex = [];
        foreach ($redirects as $redirect) {
            $redirectIndex[site_normalize_path((string) ($redirect['from'] ?? '/'))] = $redirect;
        }

        $pagesByPath = [];
        $livePaths = [];
        foreach ($publishedPages as $page) {
            $path = site_path_from_url((string) ($page['canonical_url'] ?? '/'));
            $pagesByPath[$path] = $page;
            $livePaths[$path] = true;
        }

        $articlesByPath = [];
        foreach ($publishedArticles as $article) {
            $path = site_path_from_url((string) ($article['canonical_url'] ?? '/'));
            $articlesByPath[$path] = $article;
            $livePaths[$path] = true;
        }

        $downloadsByPath = [];
        foreach ($publishedDownloads as $download) {
            $path = site_path_from_url((string) ($download['canonical_url'] ?? '/'));
            $downloadsByPath[$path] = $download;
            $livePaths[$path] = true;
        }

        $categoriesByPath = [];
        foreach ($publishedCategories as $category) {
            $categoriesByPath[(string) ($category['path'] ?? '')] = $category;
            $livePaths[site_path_from_url((string) ($category['canonical_url'] ?? '/library/'))] = true;
        }

        $dirCategoriesByPath = [];
        foreach ($publishedDirCategories as $dirCat) {
            $dirCategoriesByPath[trim(strtolower((string) ($dirCat['path'] ?? '')), '/')] = $dirCat;
            $livePaths[site_path_from_url((string) ($dirCat['canonical_url'] ?? '/directory/'))] = true;
        }

        $dirListingsByPath = [];
        foreach ($publishedDirListings as $dirListing) {
            $dirListingsByPath[trim(strtolower((string) ($dirListing['path'] ?? '')), '/')] = $dirListing;
            $livePaths[site_path_from_url((string) ($dirListing['canonical_url'] ?? '/directory/'))] = true;
        }

        $recordRedirectIndex = site_build_record_redirect_index($publishedPages, $livePaths, 'page');
        $recordRedirectIndex += site_build_record_redirect_index($publishedArticles, $livePaths, 'article');
        $recordRedirectIndex += site_build_record_redirect_index($publishedDownloads, $livePaths, 'download');
        $recordRedirectIndex += site_build_record_redirect_index($publishedCategories, $livePaths, 'category');
        $redirectIndex += $recordRedirectIndex;

        $app = [
            'site' => $site,
            'routes' => $routes,
            'tokens' => $tokens,
            'flags' => $flags,
            'federation' => $federation,
            'pages' => $pages,
            'published_pages' => $publishedPages,
            'pages_by_slug' => site_record_index($pages, 'slug'),
            'pages_by_path' => $pagesByPath,
            'categories' => $categories,
            'published_categories' => $publishedCategories,
            'categories_by_slug' => site_record_index($categories, 'slug'),
            'categories_by_path' => $categoriesByPath,
            'categories_by_parent' => site_group_records($publishedCategories, 'parent_slug'),
            'articles' => $articles,
            'published_articles' => $publishedArticles,
            'articles_by_slug' => site_record_index($articles, 'slug'),
            'articles_by_path' => $articlesByPath,
            'downloads' => $downloads,
            'published_downloads' => $publishedDownloads,
            'downloads_by_slug' => site_record_index($downloads, 'slug'),
            'downloads_by_path' => $downloadsByPath,
            'sidebars' => $sidebarProfiles,
            'sidebars_by_slug' => site_record_index($sidebarProfiles, 'slug'),
            'offers' => $offers,
            'published_offers' => $publishedOffers,
            'offers_by_slug' => site_record_index($offers, 'slug'),
            'redirects' => $redirects,
            'record_redirects' => array_values($recordRedirectIndex),
            'redirects_by_path' => $redirectIndex,
            'thai_resources' => $thaiResources,
            'federation_resources' => $federationResources,
            'dir_schemas_by_type'      => site_record_index($dirSchemas, 'type'),
            'dir_categories_by_path'   => $dirCategoriesByPath,
            'dir_categories_by_parent' => site_group_records($publishedDirCategories, 'parent_path'),
            'dir_listings_by_path'     => $dirListingsByPath,
            'dir_listings_by_category' => site_group_records($publishedDirListings, 'category_path'),
            'channels' => $channels,
            'published_channels' => $publishedChannels,
            'channels_by_slug' => site_record_index($channels, 'slug'),
            'sessions' => $sessions,
            'published_sessions' => $publishedSessions,
            'sessions_by_slug' => site_record_index($sessions, 'slug'),
            'sessions_by_channel' => site_group_records($publishedSessions, 'channel_slug'),
        ];
    }

    return $app;
}

function site_find_page(string $slug): ?array
{
    $page = site_app()['pages_by_slug'][$slug] ?? null;

    return is_array($page) ? $page : null;
}

// ── Glossary ─────────────────────────────────────────────────────────────────

function site_glossary_terms_all(): array
{
    static $terms;

    if ($terms !== null) {
        return $terms;
    }

    $terms = [];
    $dir   = site_root_path('content/glossary-terms');

    if (!is_dir($dir)) {
        return $terms;
    }

    foreach (glob($dir . '/*.json') ?: [] as $file) {
        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        if (is_array($data)) {
            $terms[] = $data;
        }
    }

    usort($terms, static fn($a, $b) => strcmp(
        strtolower((string) ($a['term'] ?? '')),
        strtolower((string) ($b['term'] ?? ''))
    ));

    return $terms;
}

function site_glossary_terms_for(string $glossarySlug): array
{
    return array_values(array_filter(site_glossary_terms_all(), static function (array $t) use ($glossarySlug): bool {
        $tGlossary = (string) ($t['glossary'] ?? 'main');

        return $tGlossary === $glossarySlug && ($t['status'] ?? 'published') === 'published';
    }));
}

function site_find_glossary_term(string $termSlug, string $glossarySlug = 'main'): ?array
{
    foreach (site_glossary_terms_all() as $term) {
        if (
            (string) ($term['slug'] ?? '') === $termSlug &&
            (string) ($term['glossary'] ?? 'main') === $glossarySlug &&
            ($term['status'] ?? 'published') === 'published'
        ) {
            return $term;
        }
    }

    return null;
}

function site_glossary_definitions(): array
{
    static $defs;

    if ($defs !== null) {
        return $defs;
    }

    $defs = [];
    $dir  = site_root_path('content/glossary');

    if (!is_dir($dir)) {
        return $defs;
    }

    foreach (glob($dir . '/*.json') ?: [] as $file) {
        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        if (is_array($data)) {
            $defs[] = $data;
        }
    }

    return $defs;
}

function site_find_glossary_def(string $slug): ?array
{
    foreach (site_glossary_definitions() as $def) {
        if ((string) ($def['slug'] ?? '') === $slug) {
            return $def;
        }
    }

    return null;
}

function site_glossary_resolve_related(array $term): array
{
    $glossarySlug = (string) ($term['glossary'] ?? 'main');
    $related      = [];

    foreach ($term['related_terms'] ?? [] as $relSlug) {
        $relSlug = (string) $relSlug;
        $found   = site_find_glossary_term($relSlug, $glossarySlug);

        if ($found === null) {
            $found = site_find_glossary_term($relSlug, 'main');
        }

        if ($found !== null) {
            $related[] = $found;
        }
    }

    return $related;
}

// ── End Glossary ─────────────────────────────────────────────────────────────

function site_find_dir_category(string $path): ?array
{
    $normalized = trim(strtolower($path), '/');
    $cat = site_app()['dir_categories_by_path'][$normalized] ?? null;

    return is_array($cat) ? $cat : null;
}

function site_find_dir_listing(string $path): ?array
{
    $normalized = trim(strtolower($path), '/');
    $listing = site_app()['dir_listings_by_path'][$normalized] ?? null;

    return is_array($listing) ? $listing : null;
}

function site_get_dir_schema(string $type): array
{
    $app    = site_app();
    $schema = $app['dir_schemas_by_type'][$type] ?? $app['dir_schemas_by_type']['general'] ?? [];

    return is_array($schema) ? $schema : [];
}

function site_get_dir_child_categories(array $parent): array
{
    $parentPath = trim(strtolower((string) ($parent['path'] ?? '')), '/');
    $children   = site_app()['dir_categories_by_parent'][$parentPath] ?? [];

    return is_array($children) ? $children : [];
}

function site_get_dir_listings_for_category(array $category, int $limit = 0): array
{
    $path     = trim(strtolower((string) ($category['path'] ?? '')), '/');
    $listings = site_app()['dir_listings_by_category'][$path] ?? [];
    $listings = is_array($listings) ? $listings : [];

    return $limit > 0 ? array_slice($listings, 0, $limit) : $listings;
}

function site_get_dir_top_categories(): array
{
    $cats = site_app()['dir_categories_by_parent'][''] ?? [];

    return is_array($cats) ? $cats : [];
}

function site_get_author(string $slug): ?array
{
    static $cache = null;
    if ($cache === null) {
        $file  = site_root_path('config/authors.json');
        $cache = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?? []) : [];
    }
    $slug = strtolower(trim($slug));
    return isset($cache[$slug]) && is_array($cache[$slug]) ? $cache[$slug] : null;
}

function site_get_dir_category_ancestors(array $category): array
{
    $ancestors = [];
    $path      = trim(strtolower((string) ($category['path'] ?? '')), '/');
    $parts     = explode('/', $path);
    array_pop($parts);

    $current = '';
    foreach ($parts as $part) {
        $current  = $current === '' ? $part : $current . '/' . $part;
        $ancestor = site_find_dir_category($current);
        if ($ancestor !== null) {
            $ancestors[] = $ancestor;
        }
    }

    return $ancestors;
}

function site_find_offer(string $slug): ?array
{
    $offer = site_app()['offers_by_slug'][$slug] ?? null;

    return is_array($offer) ? $offer : null;
}

function site_find_download(string $slug): ?array
{
    $download = site_app()['downloads_by_slug'][$slug] ?? null;

    return is_array($download) ? $download : null;
}

function site_find_category_by_slug(string $slug): ?array
{
    $category = site_app()['categories_by_slug'][$slug] ?? null;

    return is_array($category) ? $category : null;
}

function site_find_category_by_path(string $path): ?array
{
    $normalized = trim(strtolower($path), '/');
    $category = site_app()['categories_by_path'][$normalized] ?? null;

    return is_array($category) ? $category : null;
}

function site_find_article_by_slug(string $slug): ?array
{
    $article = site_app()['articles_by_slug'][$slug] ?? null;

    return is_array($article) ? $article : null;
}

function site_find_redirect(string $path): ?array
{
    $redirect = site_app()['redirects_by_path'][site_normalize_path($path)] ?? null;

    return is_array($redirect) ? $redirect : null;
}

function site_resolve_request(string $requestPath): array
{
    $path = site_normalize_path($requestPath);

    if (($redirect = site_find_redirect($path)) !== null) {
        return [
            'type' => 'redirect',
            'status_code' => (int) ($redirect['type'] ?? 301),
            'location' => (string) ($redirect['to'] ?? '/'),
            'record' => $redirect,
            'path' => $path,
        ];
    }

    // ── Admin backend routes ─────────────────────────────────────────────────
    if (str_starts_with($path, '/admin')) {
        admin_ensure_session();
        $segments    = array_values(array_filter(explode('/', trim($path, '/'))));
        $admin_slug  = $segments[1] ?? '';
        $admin_sub   = $segments[2] ?? '';
        $admin_child = $segments[3] ?? '';

        return [
            'type'        => 'admin',
            'admin_slug'  => $admin_slug,
            'admin_sub'   => $admin_sub,
            'admin_child' => $admin_child,
            'path'        => $path,
            'status_code' => 200,
        ];
    }

    // POST-only virtual endpoint ... GETs redirect home, POSTs are handled in index.php
    if ($path === '/subscribe/') {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return [
                'type' => 'subscribe',
                'record' => [
                    'title' => 'Subscribe',
                    'canonical_url' => '',
                ],
                'status_code' => 200,
                'path' => $path,
            ];
        }

        header('Location: /', true, 302);
        exit;
    }

    $app = site_app();

    if (str_starts_with($path, '/download/')) {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $downloadSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($segments[1] ?? '')));
        $downloadAction = strtolower((string) ($segments[2] ?? ''));
        $downloadToken = preg_replace('/[^a-f0-9]/i', '', (string) ($segments[3] ?? ''));

        if ($downloadSlug !== '' && $downloadAction === 'access' && $downloadToken !== '') {
            $download = site_find_download($downloadSlug);

            if ($download !== null && site_is_published($download)) {
                return [
                    'type' => 'download_access',
                    'record' => $download,
                    'download_token' => $downloadToken,
                    'status_code' => 200,
                    'path' => $path,
                ];
            }
        }
    }

    if (isset($app['pages_by_path'][$path])) {
        $page = $app['pages_by_path'][$path];

        return [
            'type' => match($page['template'] ?? '') {
                'table-of-contents' => 'toc',
                'directory-root'    => 'dir_root',
                default             => 'page',
            },
            'record' => $page,
            'status_code' => 200,
            'path' => $path,
        ];
    }

    if (isset($app['articles_by_path'][$path])) {
        return [
            'type' => 'article',
            'record' => $app['articles_by_path'][$path],
            'status_code' => 200,
            'path' => $path,
        ];
    }

    if (isset($app['downloads_by_path'][$path])) {
        return [
            'type' => 'download',
            'record' => $app['downloads_by_path'][$path],
            'status_code' => 200,
            'path' => $path,
        ];
    }

    if (str_starts_with($path, '/article/')) {
        $slug = trim(substr($path, strlen('/article/')), '/');
        $article = site_find_article_by_slug($slug);

        if ($article !== null && site_is_published($article)) {
            return [
                'type' => 'article',
                'record' => $article,
                'status_code' => 200,
                'path' => $path,
            ];
        }
    }

    if (str_starts_with($path, '/download/')) {
        $downloadPath = trim(substr($path, strlen('/download/')), '/');

        if ($downloadPath !== '' && !str_contains($downloadPath, '/')) {
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($downloadPath));
            $download = site_find_download($slug);

            if ($download !== null && site_is_published($download)) {
                return [
                    'type' => 'download',
                    'record' => $download,
                    'status_code' => 200,
                    'path' => $path,
                ];
            }
        }
    }

    if (str_starts_with($path, '/library/')) {
        $categoryPath = trim(substr($path, strlen('/library/')), '/');
        $category = site_find_category_by_path($categoryPath);

        if ($category !== null) {
            return [
                'type' => 'category',
                'record' => $category,
                'status_code' => 200,
                'path' => $path,
            ];
        }
    }

    if (str_starts_with($path, '/table-of-contents/')) {
        $sectionSlug = trim(substr($path, strlen('/table-of-contents/')), '/');
        $tocPage     = site_find_page('table-of-contents');

        if ($tocPage !== null && $sectionSlug !== '') {
            $section = site_find_category_by_slug($sectionSlug);

            if ($section !== null) {
                return [
                    'type'           => 'toc',
                    'record'         => $tocPage,
                    'focus_category' => $section,
                    'status_code'    => 200,
                    'path'           => $path,
                ];
            }
        }
    }

    if (str_starts_with($path, '/directory/')) {
        $dirPath = trim(substr($path, strlen('/directory/')), '/');

        if ($dirPath !== '') {
            $dirCat = site_find_dir_category($dirPath);

            if ($dirCat !== null) {
                return [
                    'type'        => 'dir_category',
                    'record'      => $dirCat,
                    'status_code' => 200,
                    'path'        => $path,
                ];
            }

            $dirListing = site_find_dir_listing($dirPath);

            if ($dirListing !== null) {
                return [
                    'type'        => 'dir_listing',
                    'record'      => $dirListing,
                    'dir_schema'  => site_get_dir_schema((string) ($dirListing['directory_type'] ?? 'general')),
                    'status_code' => 200,
                    'path'        => $path,
                ];
            }
        }
    }

    // ── Glossary routes ──────────────────────────────────────────────────────
    if ($path === '/glossary/') {
        $mainDef = site_find_glossary_def('main') ?? [
            'slug'        => 'main',
            'title'       => 'Glossary',
            'description' => 'Definitions for terms, frameworks, and concepts used across this site.',
            'canonical_url' => '/glossary/',
            'status'      => 'published',
        ];

        return [
            'type'        => 'glossary_index',
            'glossary'    => $mainDef,
            'record'      => array_merge($mainDef, ['template' => 'glossary']),
            'status_code' => 200,
            'path'        => $path,
        ];
    }

    if (str_starts_with($path, '/glossary/')) {
        $glossaryPath = trim(substr($path, strlen('/glossary/')), '/');
        $segments     = explode('/', $glossaryPath);

        if (count($segments) === 1 && $segments[0] !== '') {
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($segments[0]));

            $glossaryDef = site_find_glossary_def($slug);

            if ($glossaryDef !== null && ($glossaryDef['status'] ?? 'published') === 'published') {
                return [
                    'type'        => 'glossary_index',
                    'glossary'    => $glossaryDef,
                    'record'      => array_merge($glossaryDef, ['template' => 'glossary']),
                    'status_code' => 200,
                    'path'        => $path,
                ];
            }

            $term = site_find_glossary_term($slug, 'main');

            if ($term !== null) {
                $mainDef = site_find_glossary_def('main') ?? ['slug' => 'main', 'title' => 'Glossary'];

                return [
                    'type'        => 'glossary_term',
                    'record'      => array_merge($term, ['template' => 'glossary-term']),
                    'glossary'    => $mainDef,
                    'status_code' => 200,
                    'path'        => $path,
                ];
            }
        }

        if (count($segments) === 2 && $segments[0] !== '' && $segments[1] !== '') {
            $glossarySlug = preg_replace('/[^a-z0-9\-]/', '', strtolower($segments[0]));
            $termSlug     = preg_replace('/[^a-z0-9\-]/', '', strtolower($segments[1]));
            $term         = site_find_glossary_term($termSlug, $glossarySlug);

            if ($term !== null) {
                $glossaryDef = site_find_glossary_def($glossarySlug) ?? [
                    'slug'  => $glossarySlug,
                    'title' => 'Glossary',
                ];

                return [
                    'type'        => 'glossary_term',
                    'record'      => array_merge($term, ['template' => 'glossary-term']),
                    'glossary'    => $glossaryDef,
                    'status_code' => 200,
                    'path'        => $path,
                ];
            }
        }
    }

    // ── Channel routes ───────────────────────────────────────────────────────
    if ($path === '/channels/') {
        return [
            'type'        => 'channel_list',
            'record'      => array_merge(
                $app['pages_by_path']['/channels/'] ?? [],
                ['title' => 'Channels', 'description' => 'Browse all build-in-public channels.', 'canonical_url' => '/channels/', 'template' => 'channels', 'status' => 'published', 'slug' => 'channels']
            ),
            'status_code' => 200,
            'path'        => $path,
        ];
    }

    if (str_starts_with($path, '/channels/')) {
        $channelSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim(substr($path, strlen('/channels/')), '/')));
        if ($channelSlug !== '') {
            $channel = $app['channels_by_slug'][$channelSlug] ?? null;
            if ($channel !== null && site_is_published($channel)) {
                return [
                    'type'        => 'channel',
                    'record'      => array_merge($channel, ['template' => 'channel']),
                    'status_code' => 200,
                    'path'        => $path,
                ];
            }
        }
    }

    // ── Session routes ───────────────────────────────────────────────────────
    if ($path === '/sessions/') {
        return [
            'type'        => 'session_list',
            'record'      => array_merge(
                $app['pages_by_path']['/sessions/'] ?? [],
                ['title' => 'Sessions', 'description' => 'All build-in-public recording sessions.', 'canonical_url' => '/sessions/', 'template' => 'sessions', 'status' => 'published', 'slug' => 'sessions']
            ),
            'status_code' => 200,
            'path'        => $path,
        ];
    }

    if (str_starts_with($path, '/sessions/')) {
        $sessionSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim(substr($path, strlen('/sessions/')), '/')));
        if ($sessionSlug !== '') {
            $session = $app['sessions_by_slug'][$sessionSlug] ?? null;
            if ($session !== null && site_is_published($session)) {
                return [
                    'type'        => 'session',
                    'record'      => array_merge($session, ['template' => 'session']),
                    'status_code' => 200,
                    'path'        => $path,
                ];
            }
        }
    }

    return [
        'type' => 'error',
        'record' => [
            'title' => 'Page not found',
            'description' => 'The page you requested is not available here yet.',
            'template' => 'error',
            'sidebar_profile' => 'authority-standard',
            'canonical_url' => site_absolute_url($path),
            'updated_at' => date('c'),
        ],
        'status_code' => 404,
        'path' => $path,
    ];
}

function site_get_category_ancestors(array $category): array
{
    $ancestors = [];
    $currentParent = $category['parent_slug'] ?? null;

    while (is_string($currentParent) && $currentParent !== '') {
        $parent = site_find_category_by_slug($currentParent);

        if ($parent === null) {
            break;
        }

        array_unshift($ancestors, $parent);
        $currentParent = $parent['parent_slug'] ?? null;
    }

    return $ancestors;
}

function site_get_child_categories(array $category): array
{
    $children = site_app()['categories_by_parent'][(string) ($category['slug'] ?? '')] ?? [];

    return array_values(array_filter($children, 'site_is_published'));
}

function site_get_top_level_categories(): array
{
    return array_values(array_filter(site_app()['published_categories'], static function (array $category): bool {
        return empty($category['parent_slug']);
    }));
}

function site_article_category_paths(array $article): array
{
    $paths = [];

    if (!empty($article['category_primary'])) {
        $paths[] = (string) $article['category_primary'];
    }

    foreach ($article['category_secondary'] ?? [] as $secondary) {
        $paths[] = (string) $secondary;
    }

    return array_values(array_unique(array_filter($paths)));
}

function site_article_matches_category(array $article, array $category): bool
{
    $categoryPath = trim((string) ($category['path'] ?? ''), '/');

    foreach (site_article_category_paths($article) as $path) {
        $normalized = trim($path, '/');

        if ($normalized === $categoryPath || str_starts_with($normalized, $categoryPath . '/')) {
            return true;
        }
    }

    return false;
}

function site_get_articles_for_category(array $category, int $limit = 0): array
{
    $matches = array_values(array_filter(site_app()['published_articles'], static function (array $article) use ($category): bool {
        return site_article_matches_category($article, $category);
    }));

    usort($matches, static function (array $left, array $right): int {
        return strcmp((string) ($right['publish_date'] ?? ''), (string) ($left['publish_date'] ?? ''));
    });

    if ($limit > 0) {
        return array_slice($matches, 0, $limit);
    }

    return $matches;
}

function site_get_related_categories(array $category, int $limit = 3): array
{
    $related = [];

    foreach ($category['relationships']['related_categories'] ?? [] as $slug) {
        $match = site_find_category_by_slug((string) $slug);

        if ($match !== null && site_is_published($match)) {
            $related[$match['slug']] = $match;
        }
    }

    if (count($related) < $limit) {
        $siblings = site_app()['categories_by_parent'][(string) ($category['parent_slug'] ?? '')] ?? [];

        foreach ($siblings as $sibling) {
            if (($sibling['slug'] ?? '') === ($category['slug'] ?? '')) {
                continue;
            }

            $related[$sibling['slug']] = $sibling;
        }
    }

    return array_slice(array_values($related), 0, $limit);
}

function site_get_related_articles(array $record, int $limit = 3): array
{
    $related = [];

    if (($record['type'] ?? '') === 'article') {
        foreach ($record['related_content'] ?? [] as $slug) {
            $article = site_find_article_by_slug((string) $slug);

            if ($article !== null && site_is_published($article)) {
                $related[$article['slug']] = $article;
            }
        }

        if (count($related) < $limit) {
            foreach (site_app()['published_articles'] as $article) {
                if (($article['slug'] ?? '') === ($record['slug'] ?? '')) {
                    continue;
                }

                $sharedCategory = array_intersect(site_article_category_paths($record), site_article_category_paths($article));
                $sharedTags = array_intersect($record['tags'] ?? [], $article['tags'] ?? []);

                if ($sharedCategory !== [] || $sharedTags !== []) {
                    $related[$article['slug']] = $article;
                }
            }
        }
    } elseif (($record['type'] ?? '') === 'category') {
        foreach (site_get_articles_for_category($record, $limit) as $article) {
            $related[$article['slug']] = $article;
        }
    } else {
        foreach (site_app()['published_articles'] as $article) {
            if (!empty($article['is_featured'])) {
                $related[$article['slug']] = $article;
            }
        }
    }

    return array_slice(array_values($related), 0, $limit);
}

function site_page_categories(array $page): array
{
    $categories = [];

    foreach ($page['featured_categories'] ?? [] as $slug) {
        $category = site_find_category_by_slug((string) $slug);

        if ($category !== null && site_is_published($category)) {
            $categories[] = $category;
        }
    }

    return $categories;
}

function site_page_articles(array $page): array
{
    $articles = [];
    $seen = [];

    foreach ($page['featured_articles'] ?? [] as $slug) {
        $article = site_find_article_by_slug((string) $slug);

        if ($article !== null && site_is_published($article)) {
            $articleSlug = (string) ($article['slug'] ?? '');
            if ($articleSlug === '' || isset($seen[$articleSlug])) {
                continue;
            }

            $seen[$articleSlug] = true;
            $articles[] = $article;
        }
    }

    $pageSlug = trim((string) ($page['slug'] ?? ''));
    if ($pageSlug !== '') {
        foreach (site_get_pinned_articles_for_target('page:' . strtolower($pageSlug)) as $article) {
            $articleSlug = (string) ($article['slug'] ?? '');
            if ($articleSlug === '' || isset($seen[$articleSlug])) {
                continue;
            }

            $seen[$articleSlug] = true;
            $articles[] = $article;
        }
    }

    if ($articles === [] && !empty($page['show_featured_articles'])) {
        foreach (site_app()['published_articles'] as $article) {
            if (!empty($article['is_featured'])) {
                $articles[] = $article;
            }
        }
    }

    return $articles;
}

function site_article_pin_targets(array $article): array
{
    $targets = $article['pin_targets'] ?? [];

    if (is_string($targets)) {
        $targets = [$targets];
    }

    if (!is_array($targets)) {
        return [];
    }

    $normalized = [];
    foreach ($targets as $target) {
        if (!is_string($target)) {
            continue;
        }

        $value = strtolower(trim($target));
        if ($value === '') {
            continue;
        }

        $normalized[$value] = true;
    }

    return array_keys($normalized);
}

function site_article_has_pin_target(array $article, string $target): bool
{
    $target = strtolower(trim($target));
    if ($target === '') {
        return false;
    }

    return in_array($target, site_article_pin_targets($article), true);
}

function site_get_pinned_articles_for_target(string $target): array
{
    $target = strtolower(trim($target));
    if ($target === '') {
        return [];
    }

    $matches = [];
    foreach (site_app()['published_articles'] as $article) {
        if (site_article_has_pin_target($article, $target)) {
            $matches[] = $article;
        }
    }

    return $matches;
}

function site_category_featured_articles(array $category): array
{
    $articles = [];
    $seen = [];

    foreach ($category['featured_articles'] ?? [] as $slug) {
        $article = site_find_article_by_slug((string) $slug);

        if ($article === null || !site_is_published($article)) {
            continue;
        }

        $articleSlug = (string) ($article['slug'] ?? '');
        if ($articleSlug === '' || isset($seen[$articleSlug])) {
            continue;
        }

        $seen[$articleSlug] = true;
        $articles[] = $article;
    }

    $categoryPath = trim(strtolower((string) ($category['path'] ?? '')), '/');
    $categorySlug = trim(strtolower((string) ($category['slug'] ?? '')), '/');
    $categoryTargets = [];

    if ($categoryPath !== '') {
        $categoryTargets[] = 'category:' . $categoryPath;
    }
    if ($categorySlug !== '' && $categorySlug !== $categoryPath) {
        $categoryTargets[] = 'category:' . $categorySlug;
    }

    foreach ($categoryTargets as $target) {
        foreach (site_get_pinned_articles_for_target($target) as $article) {
            $articleSlug = (string) ($article['slug'] ?? '');
            if ($articleSlug === '' || isset($seen[$articleSlug])) {
                continue;
            }

            $seen[$articleSlug] = true;
            $articles[] = $article;
        }
    }

    foreach (site_get_articles_for_category($category) as $article) {
        if (empty($article['is_featured'])) {
            continue;
        }

        $articleSlug = (string) ($article['slug'] ?? '');
        if ($articleSlug === '' || isset($seen[$articleSlug])) {
            continue;
        }

        $seen[$articleSlug] = true;
        $articles[] = $article;
    }

    return $articles;
}

function site_breadcrumbs(array $resolved): array
{
    $items = [
        ['label' => 'Home', 'url' => '/'],
    ];

    if ($resolved['type'] === 'page') {
        $record = $resolved['record'];

        if (($record['slug'] ?? '') !== 'home') {
            $items[] = ['label' => (string) ($record['title'] ?? 'Page'), 'url' => (string) ($record['canonical_url'] ?? '/')];
        }

        return $items;
    }

    if ($resolved['type'] === 'download') {
        $items[] = [
            'label' => (string) ($resolved['record']['title'] ?? 'Download'),
            'url' => (string) ($resolved['record']['canonical_url'] ?? '/'),
        ];

        return $items;
    }

    if ($resolved['type'] === 'category') {
        $items[] = ['label' => 'Library', 'url' => '/library/'];

        foreach (site_get_category_ancestors($resolved['record']) as $ancestor) {
            $items[] = [
                'label' => (string) ($ancestor['title'] ?? 'Category'),
                'url' => (string) ($ancestor['canonical_url'] ?? '/library/'),
            ];
        }

        $items[] = [
            'label' => (string) ($resolved['record']['title'] ?? 'Category'),
            'url' => (string) ($resolved['record']['canonical_url'] ?? '/library/'),
        ];

        return $items;
    }

    if ($resolved['type'] === 'article') {
        $items[] = ['label' => 'Library', 'url' => '/library/'];
        $primaryCategory = site_find_category_by_path((string) ($resolved['record']['category_primary'] ?? ''));

        if ($primaryCategory !== null) {
            foreach (site_get_category_ancestors($primaryCategory) as $ancestor) {
                $items[] = [
                    'label' => (string) ($ancestor['title'] ?? 'Category'),
                    'url' => (string) ($ancestor['canonical_url'] ?? '/library/'),
                ];
            }

            $items[] = [
                'label' => (string) ($primaryCategory['title'] ?? 'Category'),
                'url' => (string) ($primaryCategory['canonical_url'] ?? '/library/'),
            ];
        }

        $items[] = [
            'label' => (string) ($resolved['record']['title'] ?? 'Article'),
            'url' => (string) ($resolved['record']['canonical_url'] ?? '/'),
        ];

        return $items;
    }

    if ($resolved['type'] === 'toc') {
        $items[] = ['label' => 'Table of Contents', 'url' => '/table-of-contents/'];

        if (!empty($resolved['focus_category'])) {
            $items[] = [
                'label' => (string) ($resolved['focus_category']['title'] ?? 'Section'),
                'url'   => site_absolute_url('/table-of-contents/' . ($resolved['focus_category']['slug'] ?? '') . '/'),
            ];
        }

        return $items;
    }

    if ($resolved['type'] === 'dir_root') {
        $items[] = ['label' => 'Directory', 'url' => '/directory/'];

        return $items;
    }

    if ($resolved['type'] === 'dir_category') {
        $items[] = ['label' => 'Directory', 'url' => '/directory/'];

        foreach (site_get_dir_category_ancestors($resolved['record']) as $ancestor) {
            $items[] = [
                'label' => (string) ($ancestor['title'] ?? ''),
                'url'   => (string) ($ancestor['canonical_url'] ?? '/directory/'),
            ];
        }

        $items[] = [
            'label' => (string) ($resolved['record']['title'] ?? ''),
            'url'   => (string) ($resolved['record']['canonical_url'] ?? '/directory/'),
        ];

        return $items;
    }

    if ($resolved['type'] === 'dir_listing') {
        $items[] = ['label' => 'Directory', 'url' => '/directory/'];

        $catPath = trim(strtolower((string) ($resolved['record']['category_path'] ?? '')), '/');

        if ($catPath !== '') {
            $parentCat = site_find_dir_category($catPath);

            if ($parentCat !== null) {
                foreach (site_get_dir_category_ancestors($parentCat) as $ancestor) {
                    $items[] = [
                        'label' => (string) ($ancestor['title'] ?? ''),
                        'url'   => (string) ($ancestor['canonical_url'] ?? '/directory/'),
                    ];
                }

                $items[] = [
                    'label' => (string) ($parentCat['title'] ?? ''),
                    'url'   => (string) ($parentCat['canonical_url'] ?? '/directory/'),
                ];
            }
        }

        $items[] = [
            'label' => (string) ($resolved['record']['title'] ?? ''),
            'url'   => (string) ($resolved['record']['canonical_url'] ?? '/directory/'),
        ];

        return $items;
    }

    if ($resolved['type'] === 'glossary_index') {
        $glossarySlug = (string) ($resolved['glossary']['slug'] ?? 'main');
        $items[]      = ['label' => 'Glossary', 'url' => '/glossary/'];

        if ($glossarySlug !== 'main') {
            $items[] = [
                'label' => (string) ($resolved['glossary']['title'] ?? 'Glossary'),
                'url'   => '/glossary/' . $glossarySlug . '/',
            ];
        }

        return $items;
    }

    if ($resolved['type'] === 'glossary_term') {
        $glossarySlug = (string) ($resolved['record']['glossary'] ?? 'main');
        $items[]      = ['label' => 'Glossary', 'url' => '/glossary/'];

        if ($glossarySlug !== 'main') {
            $glossaryTitle = (string) ($resolved['glossary']['title'] ?? $glossarySlug);
            $items[]       = ['label' => $glossaryTitle, 'url' => '/glossary/' . $glossarySlug . '/'];
        }

        $items[] = [
            'label' => (string) ($resolved['record']['term'] ?? 'Term'),
            'url'   => (string) ($resolved['record']['canonical_url'] ?? '/glossary/'),
        ];

        return $items;
    }

    return $items;
}

function site_resolve_sidebar_profile(array $record, string $routeType): array
{
    $app = site_app();
    $slug = (string) ($record['sidebar_profile'] ?? $app['routes']['template_defaults'][$routeType] ?? 'authority-standard');
    $profile = $app['sidebars_by_slug'][$slug] ?? null;

    if (is_array($profile)) {
        return $profile;
    }

    return $app['sidebars_by_slug']['authority-standard'];
}

function site_sidebar_blocks(array $profile, array $resolved): array
{
    $app = site_app();
    $record = $resolved['record'];
    $blocks = [];

    foreach ($profile['blocks'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'related-articles') {
            $block['links'] = array_map(static function (array $article): array {
                return [
                    'label' => (string) $article['title'],
                    'url' => (string) $article['canonical_url'],
                    'description' => (string) ($article['excerpt'] ?? ''),
                ];
            }, site_get_related_articles($record));
        }

        if (($block['type'] ?? '') === 'topic-list') {
            if ($resolved['type'] === 'category') {
                $tocArticles = site_get_articles_for_category($record);
                $block['links'] = array_map(static function (array $article): array {
                    return [
                        'label' => (string) $article['title'],
                        'url' => (string) $article['canonical_url'],
                        'description' => '',
                    ];
                }, $tocArticles);
            } elseif ($resolved['type'] === 'page') {
                $topics = site_page_categories($record);
                $block['links'] = array_map(static function (array $category): array {
                    return [
                        'label' => (string) $category['title'],
                        'url' => (string) $category['canonical_url'],
                        'description' => (string) ($category['description'] ?? ''),
                    ];
                }, $topics);
            }
        }

        if (($block['type'] ?? '') === 'thai-links') {
            $block['body'] = (string) ($app['thai_resources']['body'] ?? '');
            $block['links'] = $app['thai_resources']['links'] ?? [];
        }

        if (($block['type'] ?? '') === 'topic-articles') {
            $primaryPath = (string) ($resolved['record']['category_primary'] ?? '');
            $cat = $primaryPath !== '' ? site_find_category_by_path($primaryPath) : null;

            if ($cat !== null && site_is_published($cat)) {
                $currentSlug = (string) ($resolved['record']['slug'] ?? '');
                $catArticles = array_values(array_filter(
                    site_get_articles_for_category($cat, 8),
                    static fn(array $a): bool => (string) ($a['slug'] ?? '') !== $currentSlug
                ));
                $block['title'] = $block['title'] ?? ('In: ' . ($cat['title'] ?? 'This Topic'));
                $block['category_url'] = (string) ($cat['canonical_url'] ?? '');
                $block['links'] = array_map(static fn(array $a): array => [
                    'label' => (string) $a['title'],
                    'url'   => (string) $a['canonical_url'],
                ], array_slice($catArticles, 0, 4));
            }
        }

        if (($block['type'] ?? '') === 'library-browse') {
            $libraryPage = $app['pages_by_slug']['library'] ?? null;
            if ($libraryPage !== null) {
                $catIndex = $app['categories_by_slug'] ?? [];
                $currentPath = $resolved['type'] === 'article'
                    ? (string) ($record['category_primary'] ?? '')
                    : (string) ($record['path'] ?? '');
                $currentTopSlug = explode('/', trim($currentPath, '/'))[0];
                $links = [];
                foreach ($libraryPage['featured_categories'] ?? [] as $slug) {
                    $cat = $catIndex[$slug] ?? null;
                    if ($cat !== null && site_is_published($cat)) {
                        $links[] = [
                            'label'      => (string) $cat['title'],
                            'url'        => (string) $cat['canonical_url'],
                            'is_current' => ($slug === $currentTopSlug),
                        ];
                    }
                }
                $block['links'] = $links;
            }
        }

        if (($block['type'] ?? '') === 'featured-link' && !empty($block['offer_slug'])) {
            $offer = site_find_offer((string) $block['offer_slug']);

            if ($offer !== null) {
                $block['title'] = $block['title'] ?? $offer['title'];
                $block['body'] = $block['body'] ?? $offer['summary'];
                $block['cta_label'] = $block['cta_label'] ?? $offer['cta_label'];
                $block['cta_url'] = $block['cta_url'] ?? $offer['cta_url'];
            }
        }

        $blocks[] = $block;
    }

    return $blocks;
}

function site_tokens_css(): string
{
    $lines = [];

    foreach (site_app()['tokens'] as $name => $value) {
        $lines[] = sprintf('--%s: %s;', $name, $value);
    }

    return implode(' ', $lines);
}

function site_format_date(string $date): string
{
    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('F j, Y', $timestamp);
}

function site_should_track_view(array $resolved): bool
{
    if (PHP_SAPI === 'cli') {
        return false;
    }

    $trackableTypes = ['page', 'article', 'category', 'toc', 'dir_root', 'dir_category', 'dir_listing', 'download', 'glossary_index', 'glossary_term'];
    $type = (string) ($resolved['type'] ?? '');
    $statusCode = (int) ($resolved['status_code'] ?? 200);
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if (!in_array($type, $trackableTypes, true) || $statusCode !== 200 || $method !== 'GET') {
        return false;
    }

    return true;
}

function site_is_bot_request(): bool
{
    $userAgent = strtolower(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));

    if ($userAgent === '') {
        return true;
    }

    $botMarkers = [
        'bot',
        'crawl',
        'crawler',
        'spider',
        'slurp',
        'preview',
        'headless',
        'lighthouse',
        'pagespeed',
        'wget',
        'curl',
        'python-requests',
        'go-http-client',
        'node-fetch',
        'axios',
        'feedfetcher',
        'monitor',
        'uptime',
        'pingdom',
        'check_http',
        'discordbot',
        'slackbot',
        'facebookexternalhit',
        'whatsapp',
        'telegrambot',
        'linkedinbot',
        'ahrefs',
        'semrush',
        'mj12bot',
        'yandex',
        'bingpreview',
        'google-read-aloud',
        'amazonbot',
    ];

    foreach ($botMarkers as $marker) {
        if (str_contains($userAgent, $marker)) {
            return true;
        }
    }

    return false;
}

function site_view_counter_path(): string
{
    $configuredPath = trim((string) (site_app()['site']['view_counter_file'] ?? ''));

    if ($configuredPath !== '') {
        return $configuredPath;
    }

    $runtimeDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'krisada-runtime';

    return $runtimeDirectory . DIRECTORY_SEPARATOR . 'page-views.json';
}

function site_view_counter_key(array $resolved): string
{
    $record = $resolved['record'] ?? [];

    return site_normalize_path((string) ($record['canonical_url'] ?? $resolved['path'] ?? '/'));
}

function site_view_counter_cookie_name(string $key): string
{
    return 'krisada_view_' . substr(md5($key), 0, 12);
}

function site_read_view_counter_store(): array
{
    $path = site_view_counter_path();

    if (!is_file($path)) {
        return ['updated_at' => null, 'pages' => []];
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    if (!is_array($decoded)) {
        return ['updated_at' => null, 'pages' => []];
    }

    $pages = $decoded['pages'] ?? [];

    return [
        'updated_at' => $decoded['updated_at'] ?? null,
        'pages' => is_array($pages) ? $pages : [],
    ];
}

function site_read_view_count(string $key): int
{
    $store = site_read_view_counter_store();
    $page = $store['pages'][$key] ?? null;

    if (!is_array($page)) {
        return 0;
    }

    return max(0, (int) ($page['count'] ?? 0));
}

function site_set_view_cookie(string $cookieName): void
{
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    setcookie($cookieName, '1', [
        'expires' => time() + 4 * 3600,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[$cookieName] = '1';
}

function site_track_page_view(array $resolved): int
{
    $key = site_view_counter_key($resolved);

    if (!site_should_track_view($resolved)) {
        return site_read_view_count($key);
    }

    if (site_is_bot_request()) {
        return site_read_view_count($key);
    }

    $cookieName = site_view_counter_cookie_name($key);

    if (!empty($_COOKIE[$cookieName])) {
        return site_read_view_count($key);
    }

    $path = site_view_counter_path();
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        return site_read_view_count($key);
    }

    $handle = fopen($path, 'c+');

    if ($handle === false) {
        return site_read_view_count($key);
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return site_read_view_count($key);
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        $decoded = json_decode($contents !== false ? $contents : '', true);
        $store = is_array($decoded) ? $decoded : ['updated_at' => null, 'pages' => []];
        $pages = $store['pages'] ?? [];
        $pages = is_array($pages) ? $pages : [];
        $page = $pages[$key] ?? [];
        $count = max(0, (int) ($page['count'] ?? 0)) + 1;

        $pages[$key] = [
            'count' => $count,
            'updated_at' => date('c'),
        ];

        $store['updated_at'] = date('c');
        $store['pages'] = $pages;

        $json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return $count;
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json . PHP_EOL);
        fflush($handle);
        flock($handle, LOCK_UN);

        site_set_view_cookie($cookieName);

        return $count;
    } finally {
        fclose($handle);
    }
}

function site_format_view_count(int $count): string
{
    $suffix = $count === 1 ? 'view' : 'views';

    return number_format(max(0, $count)) . ' ' . $suffix;
}

function site_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function site_checklist_questions(): array
{
    return [
        [
            'title' => 'Do you publish first on a site you control?',
            'note' => 'If your main home is Substack, Medium, LinkedIn, or social media, answer No.',
            'meaning' => 'Your primary publishing home should live on infrastructure you own: your domain, your site, your files, and your navigation.',
            'why' => 'Owned infrastructure compounds. Rented platforms can change the rules, limit your archive, or force you to rebuild somewhere else later.',
            'rule' => 'If a third-party platform controls the audience relationship or the export path, you do not fully own the platform.',
            'link_url' => '/article/what-website-ownership-actually-means/',
            'link_label' => 'Read what website ownership actually means',
        ],
        [
            'title' => 'Is your content easy for both people and AI systems to understand?',
            'note' => 'Think clear titles, strong headings, consistent structure, and obvious context.',
            'meaning' => 'Machine-readable does not mean robotic. It means your content is easy to parse, label, and interpret without guessing what each page is about.',
            'why' => 'Clear structure helps humans scan faster and helps AI systems interpret your topics, entities, and relevance more reliably.',
            'rule' => 'If your content would confuse a smart reader who skimmed only the headings, it probably needs stronger structure.',
            'link_url' => '/article/writing-content-machines-can-read/',
            'link_label' => 'Read how to write content machines can read',
        ],
        [
            'title' => 'Do your key pages use structured data like schema or JSON-LD?',
            'note' => 'Homepage, about, article, service, and library pages matter most.',
            'meaning' => 'Structured data is extra context you attach to pages so machines know what they are looking at without inference alone.',
            'why' => 'It reduces ambiguity. Search engines and AI tools can connect your pages to the right entities, topics, and page types faster.',
            'rule' => 'If you have never checked your schema or you are not sure which pages use it, answer Not sure.',
            'link_url' => '/article/json-ld-practical-guide/',
            'link_label' => 'Read the practical JSON-LD guide',
        ],
        [
            'title' => 'Does your internal linking teach the topic, not just move people around the menu?',
            'note' => 'Good linking connects related ideas and deepens authority. It is more than navigation chrome.',
            'meaning' => 'Internal linking should show how pages support each other inside a topic, not just help someone click to the next page.',
            'why' => 'Strong internal linking turns isolated posts into a connected body of work that builds topical authority over time.',
            'rule' => 'If most links are only in menus, footers, or random related-post widgets, this area probably needs work.',
            'link_url' => '/article/why-your-content-library-is-your-moat/',
            'link_label' => 'Read why your content library is your moat',
        ],
        [
            'title' => 'Do you have an email list you can export and control?',
            'note' => 'Owning the audience relationship matters as much as owning the site.',
            'meaning' => 'An owned email list means the list belongs to you, you can export it, and you are not trapped inside one platform.',
            'why' => 'Traffic sources change. A portable audience gives you a direct line back to people who already trust your work.',
            'rule' => 'If you cannot export your list cleanly or have no direct subscriber channel, answer No.',
            'link_url' => '/article/moving-your-audience-off-platform/',
            'link_label' => 'Read how to move your audience off platform',
        ],
        [
            'title' => 'Are you building reusable content assets instead of one-off posts?',
            'note' => 'A durable asset can be expanded, linked, reused, cited, and repurposed later.',
            'meaning' => 'Compounding content keeps earning attention because it becomes a durable reference, not a disposable update.',
            'why' => 'One-off posts fade quickly. Reusable assets keep giving you material for future pages, emails, tools, and citations.',
            'rule' => 'If most of your publishing has no obvious second use six months later, you are probably creating expiring content.',
            'link_url' => '/article/how-to-turn-one-good-idea-into-a-content-system/',
            'link_label' => 'Read how to turn one idea into a content system',
        ],
        [
            'title' => 'Do you know what your site is already visible for in search and AI answers?',
            'note' => 'Not what you hope it is known for. What it is actually cited or surfaced for right now.',
            'meaning' => 'Visibility is not just ranking for a keyword. It is understanding what themes, questions, and entities your site is already associated with.',
            'why' => 'You cannot improve positioning if you do not know the pattern your site is already sending to machines.',
            'rule' => 'If you have not reviewed search visibility, citations, or AI mentions recently, answer Not sure.',
            'link_url' => '/article/being-citable-vs-being-rankable/',
            'link_label' => 'Read being citable vs being rankable',
        ],
        [
            'title' => 'Is your site organized like a library with clear topic homes?',
            'note' => 'A library has structure. A blog archive is just a timeline.',
            'meaning' => 'A library model groups related material into durable topic areas, pathways, and cornerstone pages instead of leaving everything in chronological sprawl.',
            'why' => 'Clear architecture helps users orient themselves and helps machines understand how your knowledge base is organized.',
            'rule' => 'If your best content disappears into an archive after publishing, answer No.',
            'link_url' => '/article/why-content-library-beats-blog-archive/',
            'link_label' => 'Read why a content library beats a blog archive',
        ],
        [
            'title' => 'Do you have a repeatable content system, not just a publishing habit?',
            'note' => 'A system covers planning, drafting, reviewing, structuring, publishing, and updating.',
            'meaning' => 'A content system is a repeatable process. A habit is just shipping when you happen to have time or energy.',
            'why' => 'Systems make quality and consistency easier to sustain. They also make delegation and AI assistance much safer.',
            'rule' => 'If your process lives only in your head, it is a habit more than a system.',
            'link_url' => '/article/ai-assisted-publishing-workflow/',
            'link_label' => 'Read the AI-assisted publishing workflow',
        ],
        [
            'title' => 'If social traffic disappeared tomorrow, would your site still get found and convert?',
            'note' => 'This is a resilience question, not a social-media question.',
            'meaning' => 'You want enough owned visibility, direct audience, and conversion paths that one external platform cannot erase your momentum overnight.',
            'why' => 'Healthy digital systems can survive platform shocks because they are not dependent on a single borrowed source of attention.',
            'rule' => 'If losing social reach would cut off discovery, leads, or revenue immediately, answer No.',
            'link_url' => '/article/building-income-that-doesnt-depend-on-one-platform/',
            'link_label' => 'Read how to build income beyond one platform',
        ],
    ];
}

function site_checklist_score_summary(int $score, int $answered, int $unsure, int $total = 10): array
{
    if ($answered === 0) {
        return [
            'summary' => 'Start answering the questions and the score will build as you go.',
            'detail' => 'Use "Not sure" whenever you cannot confidently verify something. That is a useful signal, not a failure.',
        ];
    }

    if ($answered < $total) {
        return [
            'summary' => 'This is a provisional read. Finish the remaining questions for a cleaner picture.',
            'detail' => 'So far you have ' . $score . ' solid areas, ' . max(0, $answered - $score - $unsure) . ' clear gaps, and ' . $unsure . ' items that need verification.',
        ];
    }

    if ($score <= 3) {
        return [
            'summary' => 'The foundation is still fragile. Start with ownership, structure, and resilience before chasing tactics.',
            'detail' => 'A low score does not mean you are behind forever. It means the most important work is still foundational, which is good because it is fixable.',
        ];
    }

    if ($score <= 6) {
        return [
            'summary' => 'You already have pieces of the right system. The next move is tightening what is inconsistent or undocumented.',
            'detail' => 'Most people in this band are not starting from zero. They are stuck between good instincts and incomplete execution.',
        ];
    }

    if ($score <= 8) {
        return [
            'summary' => 'This is a strong base. Your next gains will come from removing blind spots and making the system more repeatable.',
            'detail' => 'At this level, weak areas are usually hidden in architecture, measurement, or process rather than effort.',
        ];
    }

    return [
        'summary' => 'You are operating from a compounding base. Keep refining the weak edges so the system stays durable.',
        'detail' => 'A high score does not mean "done." It means your next improvements can be more strategic than reactive.',
    ];
}

function site_checklist_state_label(string $state): string
{
    return match ($state) {
        'yes' => 'Yes',
        'no' => 'No',
        'unsure' => 'Not sure',
        default => 'Not answered',
    };
}

function site_checklist_state_badge(string $state): string
{
    return match ($state) {
        'no' => 'Needs work',
        'unsure' => 'Needs checking',
        'yes' => 'Solid',
        default => 'Open',
    };
}

function site_mask_email(string $email): string
{
    $email = trim($email);

    if ($email === '' || !str_contains($email, '@')) {
        return $email;
    }

    [$local, $domain] = explode('@', $email, 2);
    $localLength = strlen($local);

    if ($localLength <= 2) {
        $maskedLocal = str_repeat('*', max(1, $localLength));
    } else {
        $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(1, $localLength - 2)) . substr($local, -1);
    }

    return $maskedLocal . '@' . $domain;
}

function site_checklist_assessment_from_post(array $post): array
{
    $questions = site_checklist_questions();
    $answersRaw = trim((string) ($post['assessment_answers'] ?? ''));
    $parsed = [];

    if ($answersRaw !== '') {
        try {
            $decoded = json_decode($answersRaw, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $parsed = $decoded;
            }
        } catch (\Throwable) {
            $parsed = [];
        }
    }

    $statesByIndex = [];
    foreach ($parsed as $row) {
        if (!is_array($row)) {
            continue;
        }

        $index = (int) ($row['index'] ?? 0);
        $state = (string) ($row['state'] ?? '');

        if ($index < 1 || $index > count($questions)) {
            continue;
        }

        if (!in_array($state, ['yes', 'no', 'unsure'], true)) {
            continue;
        }

        $statesByIndex[$index] = $state;
    }

    $answers = [];
    $score = 0;
    $answered = 0;
    $unsure = 0;
    $focusAreas = [];

    foreach ($questions as $index => $question) {
        $number = $index + 1;
        $state = $statesByIndex[$number] ?? '';

        if ($state !== '') {
            $answered++;
        }

        if ($state === 'yes') {
            $score++;
        }

        if ($state === 'unsure') {
            $unsure++;
        }

        $answer = [
            'index' => $number,
            'state' => $state,
            'question' => $question['title'],
            'link_url' => $question['link_url'],
            'link_label' => $question['link_label'],
        ];
        $answers[] = $answer;

        if (in_array($state, ['no', 'unsure'], true)) {
            $focusAreas[] = $answer;
        }
    }

    return [
        'questions' => $questions,
        'answers' => $answers,
        'focus_areas' => array_slice($focusAreas, 0, 3),
        'score' => $score,
        'answered' => $answered,
        'unsure' => $unsure,
        'total' => count($questions),
        'is_complete' => $answered === count($questions),
        'summary' => site_checklist_score_summary($score, $answered, $unsure, count($questions)),
    ];
}

function site_prepare_view(array $resolved): array
{
    $record = $resolved['record'];
    $type = $resolved['type'];
    $templateName = (string) ($record['template'] ?? 'page');
    $sidebarProfile = site_resolve_sidebar_profile($record, $templateName);
    $description = (string) ($record['seo']['description'] ?? $record['excerpt'] ?? $record['description'] ?? $record['intro'] ?? site_app()['site']['description']);
    $canonicalUrl = site_absolute_url((string) ($record['canonical_url'] ?? $resolved['path'] ?? '/'));
    $title = (string) ($record['seo']['title'] ?? $record['title'] ?? site_app()['site']['name']);
    $socialTitle = (string) ($record['seo']['og_title'] ?? $record['seo']['twitter_title'] ?? $title);
    $socialDescription = (string) ($record['seo']['og_description'] ?? $record['seo']['twitter_description'] ?? $description);
    $socialImage = trim((string) ($record['seo']['image'] ?? $record['seo']['og_image'] ?? $record['seo']['twitter_image'] ?? $record['featured_image'] ?? ''));
    $socialType = $type === 'article' ? 'article' : 'website';
    $twitterCard = trim((string) ($record['seo']['twitter_card'] ?? ''));

    if ($twitterCard === '') {
        $twitterCard = $socialImage !== '' ? 'summary_large_image' : 'summary';
    }

    if ($socialImage !== '') {
        $socialImage = site_absolute_raw_url($socialImage);
    }

    $view = [
        'site' => site_app()['site'],
        'resolved' => $resolved,
        'record' => $record,
        'type' => $type,
        'status_code' => (int) ($resolved['status_code'] ?? 200),
        'template_path' => site_root_path('templates/' . $templateName . '.php'),
        'breadcrumbs' => site_breadcrumbs($resolved),
        'sidebar_profile' => $sidebarProfile,
        'sidebar_blocks' => site_sidebar_blocks($sidebarProfile, $resolved),
        'title' => $title,
        'description' => $description,
        'canonical_url' => $canonicalUrl,
        'extra_css' => (string) ($record['extra_css'] ?? ''),
        'extra_js' => (string) ($record['extra_js'] ?? ''),
        'social' => [
            'title' => $socialTitle,
            'description' => $socialDescription,
            'url' => $canonicalUrl,
            'type' => $socialType,
            'image' => $socialImage,
            'twitter_card' => $twitterCard,
            'site_name' => (string) (site_app()['site']['name'] ?? 'Krisada.com'),
        ],
        'json_ld' => [],
    ];

    if (!empty($record['hide_sidebar'])) {
        $view['hide_sidebar'] = true;
    }

    if ($type === 'page') {
        $view['featured_categories'] = site_page_categories($record);
        $view['featured_articles'] = site_page_articles($record);

        if (($record['template'] ?? '') === 'contact') {
            $view['form_config'] = site_contact_form_config() ?? [];
            $view['form_state']  = site_contact_form_state();
            $intentCopy = $view['form_state']['intent_copy'] ?? [];
            $view['hero'] = array_replace_recursive((array) ($record['hero'] ?? []), (array) ($intentCopy['hero'] ?? []));
            $view['form_copy'] = array_replace_recursive((array) ($record['form'] ?? []), (array) ($intentCopy['form'] ?? []));
        }
    }

    if ($type === 'category') {
        $view['child_categories'] = site_get_child_categories($record);
        $view['featured_articles'] = site_category_featured_articles($record);
        $view['recent_articles'] = site_get_articles_for_category($record);
        $view['related_categories'] = site_get_related_categories($record);
    }

    if ($type === 'article') {
        $view['primary_category'] = site_find_category_by_path((string) ($record['category_primary'] ?? ''));
        $view['related_articles'] = site_get_related_articles($record, 3);
    }

    if ($type === 'download') {
        $view['download_state'] = site_download_claim_state((string) ($record['slug'] ?? ''));
    }

    if ($type === 'toc') {
        $view['top_level_categories'] = site_get_top_level_categories();
        $view['focus_category']       = $resolved['focus_category'] ?? null;
    }

    if ($type === 'dir_root') {
        $view['top_categories'] = site_get_dir_top_categories();
    }

    if ($type === 'dir_category') {
        $view['child_categories']    = site_get_dir_child_categories($record);
        $view['listings']            = site_get_dir_listings_for_category($record);
        $view['ancestor_categories'] = site_get_dir_category_ancestors($record);
    }

    if ($type === 'dir_listing') {
        $view['dir_schema']          = $resolved['dir_schema'] ?? [];
        $catPath                     = trim(strtolower((string) ($record['category_path'] ?? '')), '/');
        $view['parent_category']     = $catPath !== '' ? site_find_dir_category($catPath) : null;
        $view['ancestor_categories'] = $view['parent_category'] !== null ? site_get_dir_category_ancestors($view['parent_category']) : [];
    }

    if ($type === 'glossary_index') {
        $glossarySlug           = (string) ($resolved['glossary']['slug'] ?? 'main');
        $view['glossary']       = $resolved['glossary'] ?? [];
        $view['glossary_terms'] = site_glossary_terms_for($glossarySlug);
        $view['sub_glossaries'] = $glossarySlug === 'main'
            ? array_values(array_filter(site_glossary_definitions(), static fn($g) => ($g['slug'] ?? '') !== 'main' && ($g['status'] ?? 'published') === 'published'))
            : [];
        $view['hide_sidebar']   = false;
        $view['extra_css']      = 'glossary';
    }

    if ($type === 'glossary_term') {
        $view['glossary']      = $resolved['glossary'] ?? [];
        $view['related_terms'] = site_glossary_resolve_related($record);
        $view['extra_css']     = 'glossary';
    }

    if ($type === 'channel_list') {
        $view['channels'] = site_app()['published_channels'];
    }

    if ($type === 'channel') {
        $channelSlug = (string) ($record['slug'] ?? '');
        $allSessions = site_app()['sessions_by_channel'][$channelSlug] ?? [];
        $view['sessions'] = array_values(array_filter($allSessions, 'site_is_published'));
    }

    if ($type === 'session_list') {
        $view['sessions']  = site_app()['published_sessions'];
        $view['channels_by_slug'] = site_app()['channels_by_slug'];
    }

    if ($type === 'session') {
        $channelSlug = (string) ($record['channel_slug'] ?? '');
        $view['channel'] = $channelSlug !== '' ? (site_app()['channels_by_slug'][$channelSlug] ?? null) : null;
        $view['related_sessions'] = array_slice(
            array_values(array_filter(
                site_app()['sessions_by_channel'][$channelSlug] ?? [],
                static fn($s) => site_is_published($s) && ($s['slug'] ?? '') !== ($record['slug'] ?? '')
            )),
            0, 4
        );
    }

    if ($type === 'redirect_admin') {
        $view['breadcrumbs'] = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Redirect Manager', 'url' => '/admin/redirects/'],
        ];
        $view['redirects'] = site_load_redirect_rules();
        $view['log_entries'] = site_get_404_log();
        $view['admin_url'] = site_redirect_admin_url();
        $view['admin_token'] = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        $view['hide_sidebar'] = true;
        $view['json_ld'] = [];

        return $view;
    }

    $view['json_ld'] = site_build_json_ld($resolved, $view);

    return $view;
}

// ── Contact form ──────────────────────────────────────────────────────────────

function site_contact_ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function site_flash_set(string $key, mixed $value): void
{
    site_contact_ensure_session();
    $_SESSION['_flash'][$key] = $value;
}

function site_flash_consume(string $key, mixed $default = null): mixed
{
    site_contact_ensure_session();

    if (!isset($_SESSION['_flash']) || !array_key_exists($key, $_SESSION['_flash'])) {
        return $default;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    if (empty($_SESSION['_flash'])) {
        unset($_SESSION['_flash']);
    }

    return $value;
}

function site_contact_form_config(): ?array
{
    static $config = null;

    if ($config === null) {
        $fullPath = site_root_path('content/system/contact-form-config.json');

        if (is_file($fullPath)) {
            $decoded = json_decode((string) file_get_contents($fullPath), true, 512, JSON_THROW_ON_ERROR);
            $config  = is_array($decoded) ? $decoded : [];
        }
    }

    return ($config !== null && $config !== []) ? $config : null;
}

function site_contact_current_intent(): string
{
    $raw = $_POST['intent'] ?? $_GET['intent'] ?? '';

    return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim((string) $raw)));
}

function site_contact_intent_config(array $config, string $intent): array
{
    if ($intent === '') {
        return [];
    }

    $intentMap = $config['intent_map'] ?? [];
    $entry = $intentMap[$intent] ?? null;

    if (is_string($entry) && $entry !== '') {
        return [
            'prefill' => [
                'inquiry_type' => $entry,
            ],
        ];
    }

    return is_array($entry) ? $entry : [];
}

function site_contact_redirect_url(string $intent = ''): string
{
    if ($intent === '') {
        return '/contact/';
    }

    return '/contact/?intent=' . rawurlencode($intent);
}

function site_contact_form_state(): array
{
    $config = site_contact_form_config() ?? [];
    $values = site_flash_consume('contact.old', []);
    $errors = site_flash_consume('contact.errors', []);
    $status = site_flash_consume('contact.status', null);

    if (!is_array($values)) {
        $values = [];
    }

    if (!is_array($errors)) {
        $errors = [];
    }

    $intent = site_contact_current_intent();
    $intentConfig = site_contact_intent_config($config, $intent);
    $prefill = [];

    if (is_array($intentConfig['prefill'] ?? null)) {
        $prefill = $intentConfig['prefill'];
    }

    foreach (['inquiry_type', 'stage', 'goal', 'message', 'website'] as $field) {
        if (!array_key_exists($field, $prefill) && array_key_exists($field, $intentConfig)) {
            $prefill[$field] = $intentConfig[$field];
        }
    }

    foreach ($prefill as $field => $value) {
        if (!isset($values[$field]) && is_string($value) && trim($value) !== '') {
            $values[$field] = $value;
        }
    }

    return [
        'values'       => $values,
        'errors'       => $errors,
        'status'       => is_array($status) ? $status : null,
        'form_started' => (string) time(),
        'intent'       => $intent,
        'form_action'  => site_contact_redirect_url($intent),
        'intent_copy'  => [
            'hero' => is_array($intentConfig['hero'] ?? null) ? $intentConfig['hero'] : [],
            'form' => is_array($intentConfig['form'] ?? null) ? $intentConfig['form'] : [],
        ],
    ];
}

function site_process_contact_form(): never
{
    $config  = site_contact_form_config() ?? [];
    $page    = site_find_page('contact');
    $intent  = site_contact_current_intent();
    $copy    = (array) ($page['form'] ?? []);
    $copy    = array_replace_recursive($copy, (array) (site_contact_intent_config($config, $intent)['form'] ?? []));
    $redirectUrl = site_contact_redirect_url($intent);

    $fields        = $config['fields'] ?? [];
    $inquiryTypes  = $config['inquiry_types'] ?? [];
    $stages        = $config['stages'] ?? [];
    $delivery      = $config['delivery'] ?? [];
    $spam          = $config['spam_protection'] ?? [];

    $values = [];
    foreach ($fields as $field) {
        $name = (string) ($field['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $values[$name] = trim((string) ($_POST[$name] ?? ''));
    }

    $honeypotField   = (string) ($spam['honeypot_field'] ?? 'company');
    $honeypotValue   = trim((string) ($_POST[$honeypotField] ?? ''));
    $formStarted     = (int) ($_POST['form_started'] ?? 0);
    $minimumSeconds  = max(1, (int) ($spam['minimum_seconds'] ?? 3));
    $successMessage  = (string) ($copy['success_message'] ?? 'Message received.');

    if ($honeypotValue !== '' || $formStarted <= 0 || (time() - $formStarted) < $minimumSeconds) {
        site_flash_set('contact.status', ['type' => 'success', 'message' => $successMessage]);
        header('Location: ' . $redirectUrl, true, 303);
        exit;
    }

    $errors = [];

    $allowedInquiryTypes = [];
    foreach ($inquiryTypes as $option) {
        $val   = (string) ($option['value'] ?? '');
        $label = (string) ($option['label'] ?? $val);
        if ($val !== '') {
            $allowedInquiryTypes[$val] = $label;
        }
    }

    $allowedStages = [];
    foreach ($stages as $option) {
        $val   = (string) ($option['value'] ?? '');
        $label = (string) ($option['label'] ?? $val);
        if ($val !== '') {
            $allowedStages[$val] = $label;
        }
    }

    if (mb_strlen($values['name'] ?? '') < 2) {
        $errors['name'] = 'Add your name so I know who this is from.';
    }

    $email = trim($values['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Use a valid email address so I can reply.';
    }

    $website = trim($values['website'] ?? '');
    if ($website !== '') {
        $normalizedWebsite = $website;
        if (!preg_match('#^https?://#i', $normalizedWebsite)) {
            $normalizedWebsite = 'https://' . $normalizedWebsite;
        }
        if (!filter_var($normalizedWebsite, FILTER_VALIDATE_URL)) {
            $errors['website'] = 'Use a valid website or project URL, or leave it blank.';
        } else {
            $values['website'] = $normalizedWebsite;
        }
    }

    $inquiryType = $values['inquiry_type'] ?? '';
    if ($inquiryType === '' || !isset($allowedInquiryTypes[$inquiryType])) {
        $errors['inquiry_type'] = 'Choose the option that best matches the inquiry.';
    }

    $stage = $values['stage'] ?? '';
    if ($stage !== '' && !isset($allowedStages[$stage])) {
        $errors['stage'] = 'Choose a valid stage or leave this blank.';
    }

    $goal = trim($values['goal'] ?? '');
    if (mb_strlen($goal) < 20) {
        $errors['goal'] = 'Tell me what you are actually trying to do in a little more detail.';
    }

    $message = trim($values['message'] ?? '');
    if (mb_strlen($message) > 3000) {
        $errors['message'] = 'Keep the extra context under 3000 characters.';
    }

    if (!empty($errors)) {
        site_flash_set('contact.errors', $errors);
        site_flash_set('contact.old', $values);
        site_flash_set('contact.status', [
            'type'    => 'error',
            'message' => (string) ($copy['error_message'] ?? 'Please fix the highlighted fields and try again.'),
        ]);
        header('Location: ' . $redirectUrl, true, 303);
        exit;
    }

    $siteName      = (string) (site_app()['site']['name'] ?? 'Krisada.com');
    $toEmail       = (string) ($delivery['to_email'] ?? 'hello@ainowguide.com');
    $fromEmail     = (string) ($delivery['from_email'] ?? $toEmail);
    $fromName      = (string) ($delivery['from_name'] ?? $siteName . ' Contact');

    $cleanName      = trim(preg_replace('/[\r\n]+/', ' ', $values['name'] ?? ''));
    $cleanEmail     = trim(preg_replace('/[\r\n]+/', '', $email));
    $cleanFromName  = trim(preg_replace('/[\r\n]+/', ' ', $fromName));
    $cleanFromEmail = trim(preg_replace('/[\r\n]+/', '', $fromEmail));

    $subject        = '[' . $siteName . '] ' . ($allowedInquiryTypes[$inquiryType] ?? 'Contact Inquiry') . ' ... ' . $cleanName;
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $bodyLines = [
        'New contact form submission from ' . $siteName,
        '',
        'Name: ' . $cleanName,
        'Email: ' . $cleanEmail,
        'Website or project URL: ' . ($values['website'] ?: 'Not provided'),
        'Inquiry type: ' . ($allowedInquiryTypes[$inquiryType] ?? $inquiryType),
        'Stage: ' . ($stage !== '' ? ($allowedStages[$stage] ?? $stage) : 'Not provided'),
        '',
        'What they are trying to accomplish:',
        $goal,
    ];

    if ($message !== '') {
        $bodyLines[] = '';
        $bodyLines[] = 'Additional context:';
        $bodyLines[] = $message;
    }

    $bodyLines[] = '';
    $bodyLines[] = 'Submitted at: ' . gmdate('Y-m-d H:i:s') . ' UTC';
    $bodyLines[] = 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $bodyLines[] = 'User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $cleanFromName . ' <' . $cleanFromEmail . '>',
        'Reply-To: ' . $cleanName . ' <' . $cleanEmail . '>',
        'X-Mailer: PHP/' . phpversion(),
    ];

    site_db_record_submission('contact', [
        'intent' => $intent,
        'name' => $cleanName,
        'email' => $cleanEmail,
        'website' => (string) ($values['website'] ?? ''),
        'inquiry_type' => $inquiryType,
        'stage' => $stage,
        'goal' => $goal,
        'message' => $message,
    ]);

    $sent = mail($toEmail, $encodedSubject, implode("\n", $bodyLines), implode("\r\n", $headers));

    if ($sent) {
        site_flash_set('contact.status', ['type' => 'success', 'message' => $successMessage]);
        header('Location: ' . $redirectUrl, true, 303);
        exit;
    }

    site_flash_set('contact.old', $values);
    site_flash_set('contact.status', [
        'type'    => 'error',
        'message' => (string) ($copy['fallback_note'] ?? 'The form could not be sent. Please email hello@ainowguide.com directly.'),
    ]);
    header('Location: ' . $redirectUrl, true, 303);
    exit;
}

// ── Centralised form database (SQLite) ───────────────────────────────────────

function site_db_connect(): \PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $path = site_root_path('storage/forms.db');
    $pdo  = new \PDO('sqlite:' . $path, null, null, [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');

    site_db_init($pdo);

    return $pdo;
}

function site_db_init(\PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscribers (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            source        TEXT    NOT NULL DEFAULT 'optin',
            ip            TEXT,
            user_agent    TEXT,
            subscribed_at TEXT    NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS form_submissions (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            form_type    TEXT NOT NULL,
            data         TEXT NOT NULL,
            ip           TEXT,
            user_agent   TEXT,
            submitted_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS download_access (
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            download_slug      TEXT    NOT NULL,
            email              TEXT    NOT NULL COLLATE NOCASE,
            token_hash         TEXT    NOT NULL,
            status             TEXT    NOT NULL DEFAULT 'active',
            ip                 TEXT,
            user_agent         TEXT,
            created_at         TEXT    NOT NULL DEFAULT (datetime('now')),
            last_issued_at     TEXT    NOT NULL DEFAULT (datetime('now')),
            last_downloaded_at TEXT,
            download_count     INTEGER NOT NULL DEFAULT 0,
            UNIQUE(download_slug, email)
        )
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_download_access_lookup
        ON download_access (download_slug, token_hash)
    ");
}

function site_db_record_subscriber(string $email, string $source = 'optin'): bool
{
    $pdo  = site_db_connect();
    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO subscribers (email, source, ip, user_agent) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        strtolower(trim($email)),
        $source,
        $_SERVER['REMOTE_ADDR'] ?? null,
        mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
    ]);

    return $stmt->rowCount() > 0;
}

function site_db_subscriber_exists(string $email): bool
{
    $pdo  = site_db_connect();
    $stmt = $pdo->prepare('SELECT 1 FROM subscribers WHERE email = ? COLLATE NOCASE LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);

    return $stmt->fetchColumn() !== false;
}

function site_db_record_submission(string $formType, array $data): void
{
    $pdo  = site_db_connect();
    $stmt = $pdo->prepare(
        'INSERT INTO form_submissions (form_type, data, ip, user_agent) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $formType,
        json_encode($data, JSON_UNESCAPED_UNICODE),
        $_SERVER['REMOTE_ADDR'] ?? null,
        mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
    ]);
}

function site_db_issue_download_access(string $downloadSlug, string $email): array
{
    $pdo = site_db_connect();
    $token = bin2hex(random_bytes(24));
    $tokenHash = hash('sha256', $token);
    $now = date('c');
    $normalizedEmail = strtolower(trim($email));

    $stmt = $pdo->prepare(
        "INSERT INTO download_access (
            download_slug,
            email,
            token_hash,
            status,
            ip,
            user_agent,
            created_at,
            last_issued_at
        ) VALUES (?, ?, ?, 'active', ?, ?, ?, ?)
        ON CONFLICT(download_slug, email) DO UPDATE SET
            token_hash = excluded.token_hash,
            status = 'active',
            ip = excluded.ip,
            user_agent = excluded.user_agent,
            last_issued_at = excluded.last_issued_at"
    );
    $stmt->execute([
        $downloadSlug,
        $normalizedEmail,
        $tokenHash,
        $_SERVER['REMOTE_ADDR'] ?? null,
        mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
        $now,
        $now,
    ]);

    $select = $pdo->prepare(
        'SELECT * FROM download_access WHERE download_slug = ? AND email = ? COLLATE NOCASE LIMIT 1'
    );
    $select->execute([$downloadSlug, $normalizedEmail]);
    $row = $select->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('Could not load download access record.');
    }

    $row['token'] = $token;

    return $row;
}

function site_db_find_download_access(string $downloadSlug, string $token): ?array
{
    $pdo = site_db_connect();
    $stmt = $pdo->prepare(
        "SELECT * FROM download_access
         WHERE download_slug = ?
           AND token_hash = ?
           AND status = 'active'
         LIMIT 1"
    );
    $stmt->execute([
        $downloadSlug,
        hash('sha256', $token),
    ]);

    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function site_db_touch_download_access(int $id): void
{
    $pdo = site_db_connect();
    $stmt = $pdo->prepare(
        'UPDATE download_access SET download_count = download_count + 1, last_downloaded_at = ? WHERE id = ?'
    );
    $stmt->execute([date('c'), $id]);
}

function site_db_download_stats(string $downloadSlug): array
{
    $pdo = site_db_connect();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS claims, COALESCE(SUM(download_count), 0) AS downloads, MAX(last_issued_at) AS last_claimed_at, MAX(last_downloaded_at) AS last_downloaded_at
         FROM download_access
         WHERE download_slug = ?'
    );
    $stmt->execute([$downloadSlug]);
    $row = $stmt->fetch();

    return [
        'claims' => (int) ($row['claims'] ?? 0),
        'downloads' => (int) ($row['downloads'] ?? 0),
        'last_claimed_at' => (string) ($row['last_claimed_at'] ?? ''),
        'last_downloaded_at' => (string) ($row['last_downloaded_at'] ?? ''),
    ];
}

function site_db_download_recent_claims(string $downloadSlug, int $limit = 15): array
{
    $pdo = site_db_connect();
    $stmt = $pdo->prepare(
        'SELECT email, status, created_at, last_issued_at, last_downloaded_at, download_count
         FROM download_access
         WHERE download_slug = ?
         ORDER BY last_issued_at DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $downloadSlug, \PDO::PARAM_STR);
    $stmt->bindValue(2, max(1, $limit), \PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

function site_db_delete_download_access(string $downloadSlug): void
{
    $pdo = site_db_connect();
    $stmt = $pdo->prepare('DELETE FROM download_access WHERE download_slug = ?');
    $stmt->execute([$downloadSlug]);
}

function site_send_assessment_results_email(string $email, array $assessment): bool
{
    $site       = site_app()['site'];
    $siteName   = (string) ($site['name'] ?? 'Krisada.com');
    $fromEmail  = (string) ($site['contact']['email'] ?? 'hello@ainowguide.com');
    $summary    = is_array($assessment['summary'] ?? null) ? $assessment['summary'] : ['summary' => '', 'detail' => ''];
    $focusAreas = is_array($assessment['focus_areas'] ?? null) ? $assessment['focus_areas'] : [];
    $answers    = is_array($assessment['answers'] ?? null) ? $assessment['answers'] : [];

    $subject = '=?UTF-8?B?' . base64_encode('[' . $siteName . '] Your self-assessment: ' . ($assessment['score'] ?? 0) . '/' . ($assessment['total'] ?? 10)) . '?=';

    $bodyLines = [
        'Your Digital Independence self-assessment is complete.',
        '',
        'Score: ' . ($assessment['score'] ?? 0) . '/' . ($assessment['total'] ?? 10),
        'Answered: ' . ($assessment['answered'] ?? 0) . '/' . ($assessment['total'] ?? 10),
        'Marked "Not sure": ' . ($assessment['unsure'] ?? 0),
        '',
        (string) ($summary['summary'] ?? ''),
        (string) ($summary['detail'] ?? ''),
        '',
    ];

    if (!empty($focusAreas)) {
        $bodyLines[] = 'Recommended next steps:';
        foreach ($focusAreas as $focus) {
            if (!is_array($focus)) {
                continue;
            }

            $bodyLines[] = '- ' . ((string) ($focus['question'] ?? 'Focus area')) . ' [' . site_checklist_state_badge((string) ($focus['state'] ?? '')) . ']';
            $bodyLines[] = '  ' . site_absolute_url((string) ($focus['link_url'] ?? '/library/'));
        }
        $bodyLines[] = '';
    } else {
        $bodyLines[] = 'You marked every area as solid. The next step is to keep the system documented, current, and measurable.';
        $bodyLines[] = 'Browse the library for refinement: ' . site_absolute_url('/library/');
        $bodyLines[] = '';
    }

    $bodyLines[] = 'Your answers:';
    foreach ($answers as $answer) {
        if (!is_array($answer)) {
            continue;
        }

        $bodyLines[] = ($answer['index'] ?? '?') . '. ' . ((string) ($answer['question'] ?? 'Question')) . ' - ' . site_checklist_state_label((string) ($answer['state'] ?? ''));
    }

    $bodyLines[] = '';
    $bodyLines[] = 'Assessment: ' . site_absolute_url('/checklist/');
    $bodyLines[] = 'Library: ' . site_absolute_url('/library/');
    $bodyLines[] = 'Audit: ' . site_absolute_url('/work-with-me/');
    $bodyLines[] = '';
    $bodyLines[] = 'Powered by AI Digital Karma® v8.0.0';

    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $siteName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $siteName . ' <' . $fromEmail . '>',
        'X-Mailer: PHP/' . phpversion(),
    ]);

    return mail($email, $subject, implode("\n", $bodyLines), $headers);
}

function site_send_subscribe_notification(string $email, bool $isNew, string $source, array $assessment = []): void
{
    $site     = site_app()['site'];
    $siteName = (string) ($site['name'] ?? 'Krisada.com');
    $toEmail  = (string) ($site['contact']['email'] ?? 'hello@ainowguide.com');

    $subject = '=?UTF-8?B?' . base64_encode('[' . $siteName . '] Subscriber: ' . $email) . '?=';
    $bodyLines = [
        'Subscriber activity on ' . $siteName,
        '',
        'Email: ' . $email,
        'Source: ' . $source,
        'New subscriber: ' . ($isNew ? 'yes' : 'no'),
        'Time: ' . gmdate('Y-m-d H:i:s') . ' UTC',
        'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    ];

    if (!empty($assessment)) {
        $bodyLines[] = '';
        $bodyLines[] = 'Assessment score: ' . ($assessment['score'] ?? 0) . '/' . ($assessment['total'] ?? 10);
        $bodyLines[] = 'Answered: ' . ($assessment['answered'] ?? 0) . '/' . ($assessment['total'] ?? 10);
        $bodyLines[] = 'Not sure: ' . ($assessment['unsure'] ?? 0);
    }

    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $siteName . ' <' . $toEmail . '>',
        'X-Mailer: PHP/' . phpversion(),
    ]);

    mail($toEmail, $subject, implode("\n", $bodyLines), $headers);
}

// ── Subscribe form handler ────────────────────────────────────────────────────

function site_process_subscribe_form(): never
{
    $honeypot     = trim((string) ($_POST['_hp'] ?? ''));
    $formStarted  = (int) ($_POST['form_started'] ?? 0);
    $rawEmail     = trim((string) ($_POST['email'] ?? ''));
    $source       = trim((string) ($_POST['source'] ?? 'optin'));
    $redirectBack = $source === 'assessment-results' ? '/checklist/#assessment-delivery' : '/';

    // Spam: honeypot filled or submitted too fast
    if ($honeypot !== '' || $formStarted <= 0 || (time() - $formStarted) < 3) {
        header('Location: /thank-you/', true, 303);
        exit;
    }

    if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
        site_flash_set('subscribe.error', 'Use a valid email address.');
        header('Location: ' . $redirectBack, true, 303);
        exit;
    }

    $email = strtolower($rawEmail);
    $isNew = true;
    $assessment = [];

    if ($source === 'assessment-results') {
        $assessment = site_checklist_assessment_from_post($_POST);

        if (!($assessment['is_complete'] ?? false)) {
            site_flash_set('subscribe.error', 'Complete all 10 questions before asking me to email your results.');
            header('Location: /checklist/#assessment-delivery', true, 303);
            exit;
        }
    }

    try {
        $isNew = site_db_record_subscriber($email, $source !== '' ? $source : 'optin');

        // Log to general submissions table regardless of duplicate
        site_db_record_submission('subscribe', [
            'email' => $email,
            'is_new' => $isNew,
            'source' => $source,
            'assessment' => $assessment,
        ]);
    } catch (\Throwable $e) {
        error_log('Subscribe form storage failed: ' . $e->getMessage());
    }

    $resultsSent = false;
    if ($source === 'assessment-results') {
        $resultsSent = site_send_assessment_results_email($email, $assessment);

        if (!$resultsSent) {
            site_flash_set('subscribe.error', 'I could not email your results right now. Please try again in a moment.');
            header('Location: /checklist/#assessment-delivery', true, 303);
            exit;
        }
    }

    site_send_subscribe_notification($email, $isNew, $source !== '' ? $source : 'optin', $assessment);

    site_flash_set('subscribe.delivery', [
        'email' => $email,
        'email_masked' => site_mask_email($email),
        'source' => $source,
        'results_sent' => $resultsSent,
        'score' => (int) ($assessment['score'] ?? 0),
        'total' => (int) ($assessment['total'] ?? 0),
    ]);

    header('Location: /thank-you/', true, 303);
    exit;
}

function site_download_flash_key(string $slug, string $type): string
{
    $safeSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug)));

    return 'download.' . $type . '.' . $safeSlug;
}

function site_download_claim_state(string $slug): array
{
    $claim = site_flash_consume(site_download_flash_key($slug, 'claim'));
    $error = site_flash_consume(site_download_flash_key($slug, 'error'));

    return [
        'claim' => is_array($claim) ? $claim : null,
        'error' => is_string($error) ? $error : '',
    ];
}

function site_safe_download_filename(string $filename, string $fallback): string
{
    $filename = trim(str_replace(['\\', '/'], '-', $filename));
    $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? '';
    $filename = trim($filename, '.-');

    if ($filename === '') {
        $filename = $fallback;
    }

    return $filename !== '' ? $filename : 'download.zip';
}

function site_path_within_root(string $candidatePath, string $rootPath): ?string
{
    if ($candidatePath === '') {
        return null;
    }

    $candidateReal = realpath($candidatePath);
    $rootReal = realpath($rootPath);

    if ($candidateReal === false || $rootReal === false) {
        return null;
    }

    $candidateNormalized = rtrim(str_replace('\\', '/', strtolower($candidateReal)), '/');
    $rootNormalized = rtrim(str_replace('\\', '/', strtolower($rootReal)), '/');

    if ($candidateNormalized !== $rootNormalized && !str_starts_with($candidateNormalized, $rootNormalized . '/')) {
        return null;
    }

    return $candidateReal;
}

function site_download_upload_root(): string
{
    $root = site_root_path('storage/downloads/files');

    if (!is_dir($root)) {
        mkdir($root, 0775, true);
    }

    return $root;
}

function site_download_access_url(array $download, string $token): string
{
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($download['slug'] ?? '')));

    return '/download/' . $slug . '/access/' . strtolower($token) . '/';
}

function site_download_source_info(array $download): array
{
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($download['slug'] ?? '')));
    $sourceKind = (string) ($download['source_kind'] ?? 'repo');
    $deliveryType = (string) ($download['delivery_type'] ?? 'file');
    $requestedFilename = trim((string) ($download['download_filename'] ?? ''));
    $sourcePath = trim((string) ($download['source_path'] ?? ''));
    $storagePath = trim((string) ($download['storage_path'] ?? ''));

    $resolvedPath = null;

    if ($sourceKind === 'upload') {
        if ($storagePath === '') {
            return [
                'exists' => false,
                'error' => 'No uploaded file is configured yet.',
            ];
        }

        $uploadRoot = site_download_upload_root();
        $resolvedPath = site_path_within_root(site_root_path($storagePath), $uploadRoot);
    } else {
        if ($sourcePath === '') {
            return [
                'exists' => false,
                'error' => 'No repo source path is configured yet.',
            ];
        }

        $resolvedPath = site_path_within_root(site_root_path($sourcePath), SITE_ROOT);
    }

    if ($resolvedPath === null) {
        return [
            'exists' => false,
            'error' => 'The download source is missing or outside the allowed path.',
        ];
    }

    if ($deliveryType === 'zip_directory') {
        if (!is_dir($resolvedPath)) {
            return [
                'exists' => false,
                'error' => 'The configured source must be a directory for zip delivery.',
            ];
        }

        return [
            'exists' => true,
            'source_kind' => $sourceKind,
            'delivery_type' => $deliveryType,
            'resolved_kind' => 'directory',
            'resolved_path' => $resolvedPath,
            'download_filename' => site_safe_download_filename($requestedFilename, ($slug !== '' ? $slug : 'download') . '.zip'),
        ];
    }

    if (!is_file($resolvedPath)) {
        return [
            'exists' => false,
            'error' => 'The configured source file does not exist.',
        ];
    }

    return [
        'exists' => true,
        'source_kind' => $sourceKind,
        'delivery_type' => 'file',
        'resolved_kind' => 'file',
        'resolved_path' => $resolvedPath,
        'download_filename' => site_safe_download_filename($requestedFilename, basename($resolvedPath)),
    ];
}

function site_send_download_access_email(string $email, array $download, string $accessUrl): bool
{
    $site = site_app()['site'];
    $siteName = (string) ($site['name'] ?? 'Krisada.com');
    $fromEmail = (string) ($site['contact']['email'] ?? 'hello@ainowguide.com');
    $subjectText = trim((string) ($download['email_subject'] ?? ''));

    if ($subjectText === '') {
        $subjectText = '[' . $siteName . '] Your download link: ' . ((string) ($download['title'] ?? 'Protected download'));
    }

    $subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';
    $title = (string) ($download['title'] ?? 'Protected download');
    $bodyLines = [
        'Your protected download is ready.',
        '',
        'Title: ' . $title,
        'Download link: ' . $accessUrl,
        '',
        'If the link expires or you lose it, come back to the download page and request a fresh one.',
    ];

    $headers = [
        'From: ' . $siteName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    return mail($email, $subject, implode("\n", $bodyLines), implode("\r\n", $headers));
}

function site_process_download_claim(array $resolved): never
{
    $download = $resolved['record'] ?? [];
    $slug = (string) ($download['slug'] ?? '');
    $canonicalUrl = (string) ($download['canonical_url'] ?? '/');
    $errorKey = site_download_flash_key($slug, 'error');
    $claimKey = site_download_flash_key($slug, 'claim');

    $honeypotValue = trim((string) ($_POST['_hp'] ?? ''));
    $formStarted = (int) ($_POST['form_started'] ?? 0);
    if ($honeypotValue !== '' || $formStarted <= 0 || (time() - $formStarted) < 2) {
        header('Location: ' . $canonicalUrl . '#download-access', true, 303);
        exit;
    }

    $rawEmail = trim((string) ($_POST['email'] ?? ''));
    if ($rawEmail === '' || !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
        site_flash_set($errorKey, 'Use a valid email address to unlock the download.');
        header('Location: ' . $canonicalUrl . '#download-access', true, 303);
        exit;
    }

    $sourceInfo = site_download_source_info($download);
    if (($sourceInfo['exists'] ?? false) !== true) {
        site_flash_set($errorKey, (string) ($sourceInfo['error'] ?? 'The download source is not available right now.'));
        header('Location: ' . $canonicalUrl . '#download-access', true, 303);
        exit;
    }

    $email = strtolower($rawEmail);
    site_db_record_subscriber($email, 'download:' . $slug);
    $access = site_db_issue_download_access($slug, $email);
    $accessUrl = site_download_access_url($download, (string) ($access['token'] ?? ''));
    $absoluteAccessUrl = site_absolute_url($accessUrl);
    $emailSent = site_send_download_access_email($email, $download, $absoluteAccessUrl);

    site_db_record_submission('download_claim', [
        'download_slug' => $slug,
        'email' => $email,
        'email_sent' => $emailSent,
        'access_url' => $absoluteAccessUrl,
    ]);

    site_flash_set($claimKey, [
        'email' => $email,
        'email_masked' => site_mask_email($email),
        'email_sent' => $emailSent,
        'access_url' => $accessUrl,
    ]);

    header('Location: ' . $canonicalUrl . '#download-access', true, 303);
    exit;
}

function site_build_zip_from_directory(string $sourceDirectory): string
{
    $tempBase = tempnam(sys_get_temp_dir(), 'krisada-download-');
    if ($tempBase === false) {
        throw new RuntimeException('Could not create a temporary file for the zip archive.');
    }

    if (is_file($tempBase)) {
        unlink($tempBase);
    }

    $zipPath = $tempBase . '.zip';
    $zip = new ZipArchive();
    $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if ($result !== true) {
        throw new RuntimeException('Could not open the zip archive for writing.');
    }

    $sourceDirectory = rtrim($sourceDirectory, DIRECTORY_SEPARATOR);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileInfo) {
        $pathName = $fileInfo->getPathname();
        $relativePath = str_replace('\\', '/', substr($pathName, strlen($sourceDirectory) + 1));

        if ($relativePath === '') {
            continue;
        }

        if ($fileInfo->isDir()) {
            $zip->addEmptyDir($relativePath);
            continue;
        }

        $zip->addFile($pathName, $relativePath);
    }

    $zip->close();

    return $zipPath;
}

function site_stream_download_response(string $filePath, string $downloadFilename, bool $deleteAfter = false): never
{
    $mimeType = 'application/octet-stream';

    if (is_file($filePath)) {
        $detected = mime_content_type($filePath);
        if (is_string($detected) && $detected !== '') {
            $mimeType = $detected;
        }
    }

    if (str_ends_with(strtolower($downloadFilename), '.zip')) {
        $mimeType = 'application/zip';
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string) filesize($filePath));
    header('Content-Disposition: attachment; filename="' . site_safe_download_filename($downloadFilename, 'download.zip') . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        readfile($filePath);
    }

    if ($deleteAfter && is_file($filePath)) {
        unlink($filePath);
    }

    exit;
}

function site_stream_download_access(array $resolved): never
{
    $download = $resolved['record'] ?? [];
    $slug = (string) ($download['slug'] ?? '');
    $token = (string) ($resolved['download_token'] ?? '');
    $canonicalUrl = (string) ($download['canonical_url'] ?? '/');
    $errorKey = site_download_flash_key($slug, 'error');

    $access = site_db_find_download_access($slug, $token);
    if ($access === null) {
        site_flash_set($errorKey, 'That download link is no longer valid. Request a fresh one below.');
        header('Location: ' . $canonicalUrl . '#download-access', true, 303);
        exit;
    }

    $sourceInfo = site_download_source_info($download);
    if (($sourceInfo['exists'] ?? false) !== true) {
        site_flash_set($errorKey, (string) ($sourceInfo['error'] ?? 'The download file is not available right now.'));
        header('Location: ' . $canonicalUrl . '#download-access', true, 303);
        exit;
    }

    site_db_touch_download_access((int) ($access['id'] ?? 0));

    if (($sourceInfo['resolved_kind'] ?? '') === 'directory') {
        $zipPath = site_build_zip_from_directory((string) $sourceInfo['resolved_path']);
        site_stream_download_response($zipPath, (string) ($sourceInfo['download_filename'] ?? 'download.zip'), true);
    }

    site_stream_download_response(
        (string) ($sourceInfo['resolved_path'] ?? ''),
        (string) ($sourceInfo['download_filename'] ?? 'download.bin')
    );
}

function site_collect_sitemap_entries(): array
{
    $entries = [];

    foreach (site_app()['published_pages'] as $page) {
        if (($page['include_in_sitemap'] ?? true) !== true) {
            continue;
        }

        $entries[] = [
            'loc' => (string) $page['canonical_url'],
            'lastmod' => (string) ($page['updated_at'] ?? date('c')),
        ];
    }

    foreach (site_app()['published_categories'] as $category) {
        if (($category['include_in_sitemap'] ?? true) !== true) {
            continue;
        }

        $entries[] = [
            'loc' => (string) $category['canonical_url'],
            'lastmod' => (string) ($category['updated_at'] ?? date('c')),
        ];
    }

    foreach (site_app()['published_articles'] as $article) {
        if (($article['include_in_sitemap'] ?? true) !== true) {
            continue;
        }

        $entries[] = [
            'loc' => (string) $article['canonical_url'],
            'lastmod' => (string) ($article['updated_at'] ?? $article['publish_date'] ?? date('c')),
        ];
    }

    foreach (site_app()['published_downloads'] as $download) {
        if (($download['include_in_sitemap'] ?? true) !== true) {
            continue;
        }

        $entries[] = [
            'loc' => (string) $download['canonical_url'],
            'lastmod' => (string) ($download['updated_at'] ?? date('c')),
        ];
    }

    return $entries;
}

// ================================================================================
// ADMIN BACKEND
// Session-based login with bcrypt passwords, CSRF, content editor, rate limiting.
// Admin data lives in storage/admin/ (blocked from web by .htaccess).
// ================================================================================

function admin_data_path(string $sub = ''): string
{
    $base = site_root_path('storage/admin');
    if (!is_dir($base)) {
        mkdir($base, 0775, true);
    }
    return $sub !== '' ? $base . DIRECTORY_SEPARATOR . $sub : $base;
}

function admin_ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ── HTML escaping alias ───────────────────────────────────────────────────────
if (!function_exists('e')) {
    function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// ── CSRF ──────────────────────────────────────────────────────────────────────

function admin_csrf_token(): string
{
    admin_ensure_session();
    if (!isset($_SESSION['_admin_csrf']) || !is_string($_SESSION['_admin_csrf'])) {
        $_SESSION['_admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_admin_csrf'];
}

function admin_verify_csrf(?string $token): bool
{
    admin_ensure_session();
    $stored = $_SESSION['_admin_csrf'] ?? null;
    return is_string($stored) && is_string($token) && hash_equals($stored, $token);
}

// ── Flash (re-use site flash, different namespace) ────────────────────────────

function admin_flash_set(string $key, mixed $value): void
{
    admin_ensure_session();
    $_SESSION['_admin_flash'][$key] = $value;
}

function admin_flash_consume(string $key, mixed $default = null): mixed
{
    admin_ensure_session();
    if (!isset($_SESSION['_admin_flash']) || !array_key_exists($key, $_SESSION['_admin_flash'])) {
        return $default;
    }
    $value = $_SESSION['_admin_flash'][$key];
    unset($_SESSION['_admin_flash'][$key]);
    if (empty($_SESSION['_admin_flash'])) {
        unset($_SESSION['_admin_flash']);
    }
    return $value;
}

// ── Session auth ──────────────────────────────────────────────────────────────

function admin_is_authenticated(): bool
{
    admin_ensure_session();
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

// ── Rate limiting (file-based, 5 attempts / 15 min per username+IP) ──────────

function _admin_rate_key(string $username): string
{
    $ip = preg_replace('/[^a-f0-9:.]/', '', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return 'login-' . preg_replace('/[^a-z0-9]/', '-', strtolower($username)) . '-' . md5($ip);
}

function _admin_rate_path(string $key): string
{
    $dir = admin_data_path('rate-limits');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9\-]/', '', $key) . '.json';
}

function _admin_is_rate_limited(string $key): bool
{
    $path = _admin_rate_path($key);
    if (!is_file($path)) {
        return false;
    }
    $state = json_decode((string) file_get_contents($path), true) ?? [];
    $first = strtotime((string)($state['first_at'] ?? ''));
    if ($first === false || (time() - $first) > 900) {
        return false;
    }
    return ((int)($state['attempts'] ?? 0)) >= 5;
}

function _admin_record_failed(string $key): void
{
    $path  = _admin_rate_path($key);
    $state = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?? []) : [];
    $now   = date('c');
    $first = strtotime((string)($state['first_at'] ?? ''));
    if ($first === false || (time() - $first) > 900) {
        $state = ['attempts' => 1, 'first_at' => $now];
    } else {
        $state['attempts'] = ((int)($state['attempts'] ?? 0)) + 1;
    }
    file_put_contents($path, json_encode($state));
}

function _admin_clear_rate(string $key): void
{
    $path = _admin_rate_path($key);
    if (is_file($path)) {
        unlink($path);
    }
}

// ── User management ───────────────────────────────────────────────────────────

function admin_get_users(): array
{
    $file = admin_data_path('admin-users.json');
    $users = [];

    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded) && isset($decoded['users'])) {
            foreach ($decoded['users'] as $u) {
                if (!is_array($u)) {
                    continue;
                }
                $name = strtolower(trim((string)($u['username'] ?? '')));
                $hash = (string)($u['passwordHash'] ?? '');
                if ($name === '' || $hash === '' || ($u['active'] ?? true) !== true) {
                    continue;
                }
                $users[$name] = $u;
            }
        }
    }

    $envUser = trim((string)(getenv('ADMIN_USERNAME') ?: ''));
    $envHash = trim((string)(getenv('ADMIN_PASSWORD_HASH') ?: ''));
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
    if ($needle === '') {
        return null;
    }
    return admin_get_users()[$needle] ?? null;
}

function admin_set_password(string $username, string $newPass): bool
{
    $file = admin_data_path('admin-users.json');
    if (!is_file($file)) {
        return false;
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        return false;
    }
    $found = false;
    foreach ($data['users'] as &$u) {
        if (strtolower(trim((string)($u['username'] ?? ''))) === strtolower(trim($username))) {
            $u['passwordHash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $found = true;
            break;
        }
    }
    unset($u);
    if (!$found) {
        return false;
    }
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return true;
}

function admin_update_password(string $username, string $currentPassword, string $newPassword): array
{
    $user = admin_find_user($username);
    if (!is_array($user)) {
        return ['ok' => false, 'message' => 'User not found.'];
    }
    if (!password_verify($currentPassword, (string)($user['passwordHash'] ?? ''))) {
        return ['ok' => false, 'message' => 'Current password is incorrect.'];
    }
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'message' => 'New password must be at least 8 characters.'];
    }
    return admin_set_password($username, $newPassword)
        ? ['ok' => true, 'message' => 'Password updated successfully.']
        : ['ok' => false, 'message' => 'Could not save new password.'];
}

// ── Login / logout ────────────────────────────────────────────────────────────

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
    $hash = is_array($user) ? (string)($user['passwordHash'] ?? '') : '';
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

// ════════════════════════════════════════════════════════════════════════════════
// ADMIN CONTENT EDITOR
// ════════════════════════════════════════════════════════════════════════════════

function admin_content_type_configs(): array
{
    return [
        'article'       => ['label' => 'Articles'],
        'page'          => ['label' => 'Pages'],
        'glossary-term' => ['label' => 'Glossary Terms'],
        'dir_listing'   => ['label' => 'Directory Listings'],
    ];
}

function admin_get_content_type_config(string $type): ?array
{
    return admin_content_type_configs()[$type] ?? null;
}

function admin_get_content_file_path(string $type, string $slug): ?string
{
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug)));
    if ($slug === '') {
        return null;
    }
    return match ($type) {
        'article'       => site_root_path('content/articles/' . $slug . '.json'),
        'page'          => site_root_path('content/pages/' . $slug . '.json'),
        'glossary-term' => site_root_path('content/glossary-terms/' . $slug . '.json'),
        'dir_listing'   => site_root_path('content/directory/listings/' . $slug . '.json'),
        default         => null,
    };
}

function admin_get_content_public_url(string $type, string $slug): ?string
{
    if ($type === 'dir_listing') {
        $p = admin_get_content_file_path('dir_listing', $slug);
        if ($p === null || !is_file($p)) {
            return null;
        }
        $r = json_decode((string) file_get_contents($p), true);
        return is_array($r) ? ($r['canonical_url'] ?? null) : null;
    }
    return match ($type) {
        'article'       => '/article/' . $slug . '/',
        'page'          => '/' . $slug . '/',
        'glossary-term' => '/glossary/' . $slug . '/',
        default         => null,
    };
}

function admin_get_content_items(?string $filter_type = null): array
{
    $items = [];
    $types = $filter_type !== null && $filter_type !== ''
        ? [$filter_type]
        : array_keys(admin_content_type_configs());

    foreach ($types as $type) {
        if (!admin_get_content_type_config($type)) {
            continue;
        }
        foreach (admin_get_content_items_for_type($type) as $item) {
            $items[] = $item;
        }
    }

    usort($items, function (array $a, array $b): int {
        $au = $a['updated_at'] ?? '';
        $bu = $b['updated_at'] ?? '';
        if ($au !== $bu) {
            return strcmp($bu, $au);
        }
        return strcmp($a['title'] ?? '', $b['title'] ?? '');
    });

    return $items;
}

function admin_get_content_items_for_type(string $type): array
{
    $items = [];

    if ($type === 'article') {
        $files = glob(site_root_path('content/articles/*.json')) ?: [];
        foreach ($files as $file) {
            $raw = json_decode((string) file_get_contents($file), true);
            if (!is_array($raw)) {
                continue;
            }
            $slug = basename($file, '.json');
            $items[] = [
                'type'       => 'article',
                'type_label' => 'Article',
                'slug'       => $slug,
                'title'      => (string) ($raw['title'] ?? 'Untitled Article'),
                'status'     => (string) ($raw['status'] ?? 'draft'),
                'updated_at' => (string) ($raw['updated_at'] ?? $raw['publish_date'] ?? ''),
                'public_url' => admin_get_content_public_url('article', $slug),
            ];
        }
    } elseif ($type === 'page') {
        $files = glob(site_root_path('content/pages/*.json')) ?: [];
        foreach ($files as $file) {
            $raw = json_decode((string) file_get_contents($file), true);
            if (!is_array($raw)) {
                continue;
            }
            $slug = basename($file, '.json');
            $items[] = [
                'type'       => 'page',
                'type_label' => 'Page',
                'slug'       => $slug,
                'title'      => (string) ($raw['title'] ?? ucfirst($slug)),
                'status'     => (string) ($raw['status'] ?? 'published'),
                'updated_at' => (string) ($raw['updated_at'] ?? ''),
                'public_url' => admin_get_content_public_url('page', $slug),
            ];
        }
    } elseif ($type === 'glossary-term') {
        $files = glob(site_root_path('content/glossary-terms/*.json')) ?: [];
        foreach ($files as $file) {
            $raw = json_decode((string) file_get_contents($file), true);
            if (!is_array($raw)) {
                continue;
            }
            $slug = basename($file, '.json');
            $items[] = [
                'type'       => 'glossary-term',
                'type_label' => 'Glossary Term',
                'slug'       => $slug,
                'title'      => (string) ($raw['term'] ?? ucfirst($slug)),
                'status'     => (string) ($raw['status'] ?? 'published'),
                'updated_at' => (string) ($raw['updated_at'] ?? $raw['created_at'] ?? ''),
                'public_url' => (string) ($raw['canonical_url'] ?? admin_get_content_public_url('glossary-term', $slug)),
            ];
        }
    } elseif ($type === 'dir_listing') {
        $files = glob(site_root_path('content/directory/listings/*.json')) ?: [];
        foreach ($files as $file) {
            $raw = json_decode((string) file_get_contents($file), true);
            if (!is_array($raw)) {
                continue;
            }
            $slug = basename($file, '.json');
            $items[] = [
                'type'       => 'dir_listing',
                'type_label' => 'Directory Listing',
                'slug'       => $slug,
                'title'      => (string) ($raw['title'] ?? ucfirst($slug)),
                'status'     => (string) ($raw['status'] ?? 'published'),
                'updated_at' => (string) ($raw['updated_at'] ?? ''),
                'public_url' => (string) ($raw['canonical_url'] ?? ''),
            ];
        }
    }

    return array_values(array_filter($items, fn(array $item): bool => ($item['slug'] ?? '') !== ''));
}

function admin_load_content_entry(string $type, string $slug): ?array
{
    $config = admin_get_content_type_config($type);
    $path   = admin_get_content_file_path($type, $slug);

    if ($config === null || $path === null || !is_file($path)) {
        return null;
    }

    $raw = json_decode((string) file_get_contents($path), true);
    if (!is_array($raw)) {
        return null;
    }

    $fields = admin_collect_content_fields($type, $raw);

    return [
        'type'         => $type,
        'type_label'   => $config['label'],
        'slug'         => $slug,
        'title'        => admin_content_display_title($type, $raw, $slug),
        'path'         => $path,
        'public_url'   => admin_get_content_public_url($type, $slug),
        'raw'          => $raw,
        'field_groups' => admin_group_content_fields($fields),
    ];
}

function admin_content_display_title(string $type, array $raw, string $slug): string
{
    return match ($type) {
        'article'       => (string) ($raw['title'] ?? 'Untitled Article'),
        'page'          => (string) ($raw['title'] ?? ucfirst($slug)),
        'glossary-term' => (string) ($raw['term'] ?? ucfirst($slug)),
        'dir_listing'   => (string) ($raw['title'] ?? ucfirst($slug)),
        default         => ucfirst($slug),
    };
}

function admin_collect_content_fields(string $type, array $raw): array
{
    $fields = [];
    admin_collect_editable_fields_recursive($type, $raw, [], $fields);

    if (in_array($type, ['article', 'page', 'glossary-term', 'dir_listing'], true)) {
        $existingPaths = array_map(fn($f) => implode('.', array_map('strval', $f['segments'])), $fields);
        // Inject authors field if not present (supports multi-author attribution)
        if (!in_array('authors', $existingPaths, true)) {
            $fields[] = ['segments' => ['authors'], 'kind' => 'list', 'value' => []];
        }
        foreach (['seo.title' => ['seo', 'title'], 'seo.description' => ['seo', 'description']] as $dotPath => $segs) {
            if (!in_array($dotPath, $existingPaths, true)) {
                $fields[] = ['segments' => $segs, 'kind' => 'string', 'value' => ''];
            }
        }
    }

    $prepared = [];
    foreach (array_values($fields) as $index => $field) {
        $segments = $field['segments'];
        $path     = implode('.', array_map('strval', $segments));
        $prepared[] = [
            'key'     => 'f' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
            'path'    => $path,
            'segments'=> $segments,
            'label'   => admin_field_label_from_segments($segments),
            'group'   => admin_field_group_from_segments($type, $segments),
            'input'   => admin_guess_field_input($type, $segments, $field['value'], $field['kind']),
            'options' => [],
            'kind'    => $field['kind'],
            'value'   => $field['value'],
        ];
    }

    return $prepared;
}

function admin_collect_editable_fields_recursive(string $type, mixed $node, array $segments, array &$fields): void
{
    if (!admin_is_allowed_content_path($type, $segments)) {
        return;
    }

    if (is_array($node)) {
        if (admin_is_scalar_list($node)) {
            $fields[] = [
                'segments' => $segments,
                'kind'     => 'list',
                'value'    => array_map(fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (string) $v, $node),
            ];
            return;
        }
        foreach ($node as $key => $value) {
            admin_collect_editable_fields_recursive($type, $value, [...$segments, $key], $fields);
        }
        return;
    }

    if (!is_scalar($node) && $node !== null) {
        return;
    }

    $fields[] = [
        'segments' => $segments,
        'kind'     => is_bool($node) ? 'bool' : (is_int($node) ? 'int' : (is_float($node) ? 'float' : 'string')),
        'value'    => $node,
    ];
}

function admin_is_allowed_content_path(string $type, array $segments): bool
{
    if ($segments === []) {
        return true;
    }

    $top  = (string) $segments[0];
    $path = implode('.', array_map('strval', $segments));

    if (str_starts_with($top, '_')) {
        return false;
    }

    return match ($type) {
        'article' => !in_array($top, ['type', 'slug', 'canonical_url', 'template', 'sidebar_profile',
                                       'related_content', 'category_primary', 'category_secondary',
                                       'include_in_sitemap', 'reading_time', 'schema', 'author'], true),
        'page'    => in_array($top, ['title', 'status', 'updated_at', 'seo', 'hero', 'authors'], true)
                     && !in_array($path, ['slug', 'canonical_url', 'template', 'extra_css', 'sidebar_profile'], true),
        'glossary-term' => !in_array($top, ['type', 'slug', 'glossary', 'canonical_url', 'related_terms', 'schema', 'author'], true),
        'dir_listing'   => !in_array($top, ['type', 'slug', 'path', 'category_path', 'canonical_url',
                                             'directory_type', 'template', 'sidebar_profile', 'author'], true),
        default   => false,
    };
}

function admin_is_scalar_list(array $node): bool
{
    if ($node === [] || !array_is_list($node)) {
        return false;
    }
    foreach ($node as $v) {
        if (!is_scalar($v) && $v !== null) {
            return false;
        }
    }
    return true;
}

function admin_guess_field_input(string $type, array $segments, mixed $value, string $kind): string
{
    $leaf = strtolower((string) end($segments));
    $path = strtolower(implode('.', array_map('strval', $segments)));

    // Path-specific overrides must come before kind-based fallbacks
    if ($path === 'authors') {
        return 'select-author-multi';
    }
    if ($kind === 'bool') {
        return 'checkbox';
    }
    if ($kind === 'int' || $kind === 'float') {
        return 'number';
    }
    if ($kind === 'list') {
        return 'textarea';
    }
    if ($top = (string)($segments[0] ?? '')) {
        if ($top === 'body' || $path === 'body') {
            return 'richtext';
        }
    }
    if (preg_match('/(^|\.)(publish_date|updated_at|created_at)$/', $path) && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return 'date';
    }
    if ($leaf === 'website' || str_ends_with($leaf, '_url')) {
        return 'url';
    }
    if ($path === 'status') {
        return 'select-status';
    }
    if (is_string($value) && (str_contains($value, "\n") || strlen($value) > 120)) {
        return 'textarea';
    }
    if (preg_match('/(body|excerpt|description|summary|intro|subheadline|headline|note)/', $leaf)) {
        return 'textarea';
    }
    return 'text';
}

function admin_field_label_from_segments(array $segments): string
{
    $parts = [];
    foreach ($segments as $seg) {
        if (is_int($seg) || ctype_digit((string) $seg)) {
            $parts[] = (string) ((int) $seg + 1);
            continue;
        }
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', (string) $seg);
        $text = str_replace(['_', '-'], ' ', $text);
        $parts[] = ucwords(trim($text));
    }
    return implode(' › ', $parts);
}

function admin_field_group_from_segments(string $type, array $segments): string
{
    $top = (string) ($segments[0] ?? 'Content');

    return match ($type) {
        'article' => match ($top) {
            'seo' => 'SEO',
            'tags' => 'Taxonomy',
            'pin_targets' => 'Placement',
            'body' => 'Body',
            default => 'Article',
        },
        'page' => match ($top) {
            'seo' => 'SEO',
            'hero' => 'Hero',
            default => 'Page',
        },
        'glossary-term' => match ($top) {
            'seo'          => 'SEO',
            'also_known_as' => 'Term',
            'images'        => 'Media',
            default         => 'Term',
        },
        'dir_listing' => match ($top) {
            'seo'    => 'SEO',
            'fields' => 'Details',
            default  => 'Listing',
        },
        default => ucwords(str_replace('_', ' ', $top)),
    };
}

function admin_group_content_fields(array $fields): array
{
    $groups = [];
    foreach ($fields as $field) {
        $groups[$field['group']][] = $field;
    }
    return $groups;
}

function admin_normalize_text_input(string $value): string
{
    return str_replace(["\r\n", "\r"], "\n", trim($value));
}

function admin_set_content_value(array &$data, array $segments, mixed $value): void
{
    $cursor     = &$data;
    $last_index = count($segments) - 1;
    foreach ($segments as $index => $segment) {
        if ($index === $last_index) {
            $cursor[$segment] = $value;
            return;
        }
        if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
            $cursor[$segment] = [];
        }
        $cursor = &$cursor[$segment];
    }
}

function admin_git_push_all(string $commit_msg): array
{
    $repo = SITE_ROOT;
    $msg  = escapeshellarg(trim($commit_msg) ?: 'admin: live edit');
    $env  = ['HOME' => '/home/webserver005', 'PATH' => '/usr/local/bin:/usr/bin:/bin'];

    $run = static function (string $cmd) use ($env): array {
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $desc, $pipes, null, $env);
        if (!is_resource($proc)) {
            return ['could not start process', 1];
        }
        fclose($pipes[0]);
        $out  = stream_get_contents($pipes[1]);
        $out .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [trim($out), proc_close($proc)];
    };

    $lines = [];

    [$out, ]            = $run("git -C " . escapeshellarg($repo) . " add -A");
    $lines[]            = $out;
    [$out, $commitCode] = $run("git -C " . escapeshellarg($repo) . " -c user.email=krisadaseo@gmail.com -c user.name=KrisadaSEO commit -m $msg");
    $lines[]            = $out;

    $flat            = implode(' ', $lines);
    $nothingToCommit = str_contains($flat, 'nothing to commit') || str_contains($flat, 'nothing added to commit');

    if ($nothingToCommit) {
        return ['status' => 'nothing', 'output' => implode("\n", array_filter($lines))];
    }

    if ($commitCode !== 0) {
        return ['status' => 'error', 'output' => implode("\n", array_filter($lines))];
    }

    [$out, $pushCode] = $run("git -C " . escapeshellarg($repo) . " push git@github.com:KrisadaSEO/www.ainowguide.com main");
    $lines[]          = $out;

    $status = ($pushCode === 0) ? 'success' : 'error';
    return ['status' => $status, 'output' => implode("\n", array_filter($lines))];
}

function admin_git_commit_and_push(string $abs_path, string $commit_msg): void
{
    $install = site_install_config();
    $token   = (string) ($install['github_token'] ?? '');
    if ($token === '') {
        return;
    }

    $root = SITE_ROOT;
    $rel  = str_replace('\\', '/', substr($abs_path, strlen($root) + 1));
    if ($rel === '' || strpos($rel, '..') !== false) {
        return;
    }

    $remote = 'https://' . rawurlencode($token) . '@github.com/KrisadaSEO/www.ainowguide.com.git';

    $cmd = '('
        . 'cd ' . escapeshellarg($root)
        . ' && git add ' . escapeshellarg($rel)
        . ' && git -c user.email=krisadaseo@gmail.com -c user.name=KrisadaSEO commit -m ' . escapeshellarg('[admin] ' . $commit_msg)
        . ' && git pull --rebase ' . escapeshellarg($remote) . ' main'
        . ' && git push ' . escapeshellarg($remote) . ' main'
        . ') 2>&1';

    exec($cmd, $output, $code);

    if ($code !== 0) {
        error_log('[admin git] ' . $commit_msg . ': ' . implode(' | ', $output));
    }
}

function admin_finalize_content_save(string $type, array &$raw): void
{
    $today = date('Y-m-d');
    if (in_array($type, ['article', 'page', 'glossary-term', 'dir_listing'], true)) {
        $raw['updated_at'] = $today;
    }
}

function admin_save_content_entry(string $type, string $slug, array $payload): array
{
    $entry = admin_load_content_entry($type, $slug);
    if ($entry === null) {
        return ['ok' => false, 'message' => 'Content entry not found.'];
    }

    $raw          = $entry['raw'];
    $field_paths  = $payload['field_paths'] ?? [];
    $field_kinds  = $payload['field_kinds'] ?? [];
    $field_values = $payload['fields'] ?? [];

    if (!is_array($field_paths) || !is_array($field_kinds) || !is_array($field_values)) {
        return ['ok' => false, 'message' => 'Invalid editor payload.'];
    }

    foreach ($field_paths as $key => $path) {
        if (!isset($field_kinds[$key])) {
            continue;
        }
        $segments = array_map(
            fn($s) => ctype_digit((string) $s) ? (int) $s : (string) $s,
            explode('.', (string) $path)
        );
        if (!admin_is_allowed_content_path($type, $segments)) {
            continue;
        }
        $kind  = (string) $field_kinds[$key];
        $value = $field_values[$key] ?? null;

        if ($kind === 'bool') {
            $normalized = $value === '1' || $value === 1 || $value === true || $value === 'on';
        } elseif ($kind === 'int') {
            $normalized = (int) $value;
        } elseif ($kind === 'float') {
            $normalized = (float) $value;
        } elseif ($kind === 'list') {
            $lines      = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];
            $normalized = array_values(array_filter(array_map('trim', $lines), fn(string $l): bool => $l !== ''));
        } else {
            $normalized = admin_normalize_text_input((string) $value);
        }

        admin_set_content_value($raw, $segments, $normalized);
    }

    admin_finalize_content_save($type, $raw);

    $encoded = json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return ['ok' => false, 'message' => 'Could not encode updated JSON.'];
    }
    if (file_put_contents($entry['path'], $encoded . PHP_EOL, LOCK_EX) === false) {
        return ['ok' => false, 'message' => 'Could not write the JSON file.'];
    }

    admin_git_commit_and_push($entry['path'], 'save ' . $type . '/' . $slug);

    return ['ok' => true, 'message' => 'Content updated successfully.'];
}

function admin_downloads_dir(): string
{
    $dir = site_root_path('content/downloads');

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
}

function admin_download_slug(string $value): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($value)));
}

function admin_download_file_path(string $slug): string
{
    return admin_downloads_dir() . DIRECTORY_SEPARATOR . $slug . '.json';
}

function admin_download_defaults(string $slug = ''): array
{
    $canonicalUrl = $slug !== '' ? '/download/' . $slug . '/' : '';

    return [
        'type' => 'download',
        'title' => '',
        'slug' => $slug,
        'canonical_url' => $canonicalUrl,
        'redirect_from' => [],
        'status' => 'draft',
        'summary' => '',
        'body' => '',
        'source_kind' => 'repo',
        'source_path' => '',
        'storage_path' => '',
        'delivery_type' => 'file',
        'download_filename' => '',
        'button_label' => 'Get the download',
        'gate_headline' => 'Unlock the download',
        'gate_description' => 'Enter your email and I will unlock the protected download for you right away.',
        'success_message' => 'Your protected download is ready below.',
        'email_subject' => '',
        'template' => 'download',
        'sidebar_profile' => 'authority-standard',
        'seo' => [
            'title' => '',
            'description' => '',
        ],
        'schema' => [
            'about' => [],
            'mentions' => [],
        ],
        'extra_css' => 'download',
        'updated_at' => date('Y-m-d'),
        'include_in_sitemap' => true,
    ];
}

function admin_load_download_entry(string $slug): ?array
{
    $slug = admin_download_slug($slug);
    if ($slug === '') {
        return null;
    }

    $path = admin_download_file_path($slug);
    if (!is_file($path)) {
        return null;
    }

    $raw = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($raw)) {
        return null;
    }

    $sourceInfo = site_download_source_info($raw);
    $stats = site_db_download_stats($slug);

    return [
        'slug' => $slug,
        'path' => $path,
        'raw' => $raw,
        'source_info' => $sourceInfo,
        'stats' => $stats,
        'recent_claims' => site_db_download_recent_claims($slug, 12),
    ];
}

function admin_list_download_items(): array
{
    $items = [];

    foreach (glob(admin_downloads_dir() . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
        $raw = json_decode((string) file_get_contents($file), true);
        if (!is_array($raw)) {
            continue;
        }

        $slug = admin_download_slug((string) ($raw['slug'] ?? basename($file, '.json')));
        if ($slug === '') {
            continue;
        }

        $sourceInfo = site_download_source_info($raw);
        $stats = site_db_download_stats($slug);

        $items[] = [
            'slug' => $slug,
            'title' => (string) ($raw['title'] ?? $slug),
            'status' => (string) ($raw['status'] ?? 'draft'),
            'updated_at' => (string) ($raw['updated_at'] ?? ''),
            'canonical_url' => (string) ($raw['canonical_url'] ?? ''),
            'source_kind' => (string) ($raw['source_kind'] ?? 'repo'),
            'delivery_type' => (string) ($raw['delivery_type'] ?? 'file'),
            'source_exists' => (bool) ($sourceInfo['exists'] ?? false),
            'source_error' => (string) ($sourceInfo['error'] ?? ''),
            'download_filename' => (string) ($sourceInfo['download_filename'] ?? ''),
            'claims' => (int) ($stats['claims'] ?? 0),
            'downloads' => (int) ($stats['downloads'] ?? 0),
        ];
    }

    usort($items, static function (array $left, array $right): int {
        $leftUpdated = (string) ($left['updated_at'] ?? '');
        $rightUpdated = (string) ($right['updated_at'] ?? '');

        if ($leftUpdated !== $rightUpdated) {
            return strcmp($rightUpdated, $leftUpdated);
        }

        return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
    });

    return $items;
}

function admin_download_uploaded_storage_path(string $slug, string $originalName): string
{
    $safeName = site_safe_download_filename($originalName, $slug . '.zip');

    return 'storage/downloads/files/' . $slug . '/' . $safeName;
}

function admin_save_download_entry(?string $existingSlug, array $payload, array $files): array
{
    $existingSlug = $existingSlug !== null ? admin_download_slug($existingSlug) : null;
    $raw = $existingSlug !== null ? (admin_load_download_entry($existingSlug)['raw'] ?? admin_download_defaults($existingSlug)) : admin_download_defaults();

    $title = trim((string) ($payload['title'] ?? ''));
    $slug = $existingSlug ?? admin_download_slug((string) ($payload['slug'] ?? ''));
    $status = in_array((string) ($payload['status'] ?? 'draft'), ['published', 'draft'], true) ? (string) ($payload['status'] ?? 'draft') : 'draft';
    $sourceKind = in_array((string) ($payload['source_kind'] ?? 'repo'), ['repo', 'upload'], true) ? (string) ($payload['source_kind'] ?? 'repo') : 'repo';
    $deliveryType = in_array((string) ($payload['delivery_type'] ?? 'file'), ['file', 'zip_directory'], true) ? (string) ($payload['delivery_type'] ?? 'file') : 'file';

    if ($title === '') {
        return ['ok' => false, 'message' => 'Add a title for the download.'];
    }

    if ($slug === '') {
        return ['ok' => false, 'message' => 'Use a valid lowercase slug with letters, numbers, and hyphens only.'];
    }

    if ($existingSlug === null && is_file(admin_download_file_path($slug))) {
        return ['ok' => false, 'message' => 'A download with that slug already exists.'];
    }

    $raw['title'] = $title;
    $raw['slug'] = $slug;
    $raw['canonical_url'] = '/download/' . $slug . '/';
    $raw['status'] = $status;
    $raw['summary'] = admin_normalize_text_input((string) ($payload['summary'] ?? ''));
    $raw['body'] = admin_normalize_text_input((string) ($payload['body'] ?? ''));
    $raw['source_kind'] = $sourceKind;
    $raw['source_path'] = trim((string) ($payload['source_path'] ?? ''));
    $raw['delivery_type'] = $deliveryType;
    $raw['download_filename'] = trim((string) ($payload['download_filename'] ?? ''));
    $raw['button_label'] = trim((string) ($payload['button_label'] ?? 'Get the download'));
    $raw['gate_headline'] = admin_normalize_text_input((string) ($payload['gate_headline'] ?? 'Unlock the download'));
    $raw['gate_description'] = admin_normalize_text_input((string) ($payload['gate_description'] ?? 'Enter your email and I will unlock the protected download for you right away.'));
    $raw['success_message'] = admin_normalize_text_input((string) ($payload['success_message'] ?? 'Your protected download is ready below.'));
    $raw['email_subject'] = admin_normalize_text_input((string) ($payload['email_subject'] ?? ''));
    $raw['sidebar_profile'] = trim((string) ($payload['sidebar_profile'] ?? 'authority-standard'));
    $raw['seo']['title'] = admin_normalize_text_input((string) ($payload['seo_title'] ?? ''));
    $raw['seo']['description'] = admin_normalize_text_input((string) ($payload['seo_description'] ?? ''));
    $raw['include_in_sitemap'] = isset($payload['include_in_sitemap']) && (string) $payload['include_in_sitemap'] === '1';
    $raw['template'] = 'download';
    $raw['extra_css'] = 'download';
    $raw['updated_at'] = date('Y-m-d');

    $upload = $files['asset_file'] ?? null;
    if (is_array($upload) && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ((int) ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'The uploaded file could not be saved.'];
        }

        $uploadDir = site_download_upload_root() . DIRECTORY_SEPARATOR . $slug;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $relativePath = admin_download_uploaded_storage_path($slug, (string) ($upload['name'] ?? ($slug . '.zip')));
        $targetPath = site_root_path($relativePath);

        if (!move_uploaded_file((string) ($upload['tmp_name'] ?? ''), $targetPath)) {
            return ['ok' => false, 'message' => 'The uploaded file could not be moved into storage.'];
        }

        $raw['storage_path'] = str_replace('\\', '/', $relativePath);
        $raw['source_kind'] = 'upload';
    }

    if (($raw['source_kind'] ?? 'repo') === 'upload' && trim((string) ($raw['storage_path'] ?? '')) === '') {
        return ['ok' => false, 'message' => 'Choose an uploaded file or switch the source kind back to repo path.'];
    }

    $encoded = json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        return ['ok' => false, 'message' => 'Could not encode the download record.'];
    }

    if (file_put_contents(admin_download_file_path($slug), $encoded . PHP_EOL, LOCK_EX) === false) {
        return ['ok' => false, 'message' => 'Could not write the download record.'];
    }

    admin_git_commit_and_push(admin_download_file_path($slug), 'save download/' . $slug);

    if ($existingSlug !== null && $existingSlug !== $slug) {
        $oldPath = admin_download_file_path($existingSlug);
        if (is_file($oldPath)) {
            unlink($oldPath);
        }
    }

    site_app(true);

    return ['ok' => true, 'message' => 'Download saved successfully.', 'slug' => $slug];
}

function admin_delete_download_entry(string $slug): array
{
    $slug = admin_download_slug($slug);
    if ($slug === '') {
        return ['ok' => false, 'message' => 'Invalid download slug.'];
    }

    $path = admin_download_file_path($slug);
    if (!is_file($path)) {
        return ['ok' => false, 'message' => 'Download record not found.'];
    }

    if (!unlink($path)) {
        return ['ok' => false, 'message' => 'Could not delete the download record.'];
    }

    site_db_delete_download_access($slug);

    site_app(true);

    return ['ok' => true, 'message' => 'Download deleted.'];
}
