<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function load_json_file(string $path): array
{
    $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    return is_array($decoded) ? $decoded : [];
}

function is_published_record(array $record): bool
{
    return ($record['status'] ?? 'published') === 'published';
}

function normalize_path(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value) === 1) {
        $value = (string) (parse_url($value, PHP_URL_PATH) ?? '');
    }

    if ($value === '') {
        return '';
    }

    if ($value[0] !== '/') {
        $value = '/' . $value;
    }

    if ($value !== '/') {
        $value = rtrim($value, '/') . '/';
    }

    return $value;
}

function add_error(array &$errors, string $file, string $message): void
{
    $errors[$file] ??= [];
    $errors[$file][] = $message;
}

function validate_schema_items(mixed $items, string $field, string $file, array $routePaths, array &$errors): void
{
    $items = is_array($items) ? $items : ($items === null ? [] : [$items]);

    foreach ($items as $index => $item) {
        $candidates = [];

        if (is_string($item)) {
            $candidates[] = $item;
        } elseif (is_array($item)) {
            foreach (['url', '@id'] as $key) {
                if (isset($item[$key]) && is_string($item[$key])) {
                    $candidates[] = $item[$key];
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || !str_starts_with(trim($candidate), '/')) {
                continue;
            }

            $normalized = normalize_path($candidate);
            if ($normalized !== '' && !isset($routePaths[$normalized])) {
                add_error($errors, $file, sprintf("schema.%s[%d] references missing internal path '%s'", $field, $index, $normalized));
            }
        }
    }
}

function validate_article_pin_targets(array $targets, array $article, string $file, array $categoryPaths, array $pageSlugs, array &$errors): void
{
    $articleCategories = [];
    $primary = trim((string) ($article['category_primary'] ?? ''), '/');
    if ($primary !== '') {
        $articleCategories[] = $primary;
    }
    foreach (($article['category_secondary'] ?? []) as $secondary) {
        if (is_string($secondary) && trim($secondary, '/') !== '') {
            $articleCategories[] = trim($secondary, '/');
        }
    }
    $articleCategories = array_values(array_unique($articleCategories));

    foreach ($targets as $index => $target) {
        if (!is_string($target)) {
            add_error($errors, $file, sprintf("pin_targets[%d] must be a string", $index));
            continue;
        }

        $normalized = strtolower(trim($target));
        if ($normalized === '') {
            add_error($errors, $file, sprintf("pin_targets[%d] cannot be empty", $index));
            continue;
        }

        if ($normalized === 'nav:articles') {
            continue;
        }

        if (str_starts_with($normalized, 'category:')) {
            $categoryTarget = trim(substr($normalized, strlen('category:')), '/');
            if ($categoryTarget === '' || !isset($categoryPaths[$categoryTarget])) {
                add_error($errors, $file, sprintf("pin_targets[%d] references unknown category '%s'", $index, $categoryTarget));
                continue;
            }

            $matches = false;
            foreach ($articleCategories as $articleCategory) {
                if ($articleCategory === $categoryTarget
                    || str_starts_with($articleCategory, $categoryTarget . '/')
                    || preg_match('#(^|/)' . preg_quote($categoryTarget, '#') . '$#', $articleCategory) === 1
                ) {
                    $matches = true;
                    break;
                }
            }

            if (!$matches) {
                add_error($errors, $file, sprintf("pin_targets[%d] category '%s' does not match the article's assigned categories", $index, $categoryTarget));
            }
            continue;
        }

        if (str_starts_with($normalized, 'page:')) {
            $pageTarget = trim(substr($normalized, strlen('page:')));
            if ($pageTarget === '' || !isset($pageSlugs[$pageTarget])) {
                add_error($errors, $file, sprintf("pin_targets[%d] references unknown page '%s'", $index, $pageTarget));
            }
            continue;
        }

        add_error($errors, $file, sprintf("pin_targets[%d] uses unsupported target '%s'", $index, $normalized));
    }
}

$errors = [];

$sidebars = [];
foreach (glob($root . '/content/sidebars/*.json') as $path) {
    $sidebars[basename($path, '.json')] = true;
}

$categories = [];
$categoryPaths = [];
$articles = [];
$articlesBySlug = [];
$downloads = [];
$pages = [];
$pageSlugs = [];
$routePaths = [
    '/'                   => true,
    '/directory/'         => true,
    '/table-of-contents/' => true,
];
$canonicalOwners = [];

foreach (glob($root . '/content/categories/*.json') as $path) {
    $record = load_json_file($path);
    if (!is_published_record($record)) {
        continue;
    }

    $record['_file'] = $path;
    $categories[] = $record;

    $slug = (string) ($record['slug'] ?? '');
    $categoryPath = (string) ($record['path'] ?? '');
    $canonicalUrl = normalize_path((string) ($record['canonical_url'] ?? ''));

    if ($slug !== '') {
        $categoryPaths[$slug] = true;
    }
    if ($categoryPath !== '') {
        $categoryPaths[$categoryPath] = true;
    }
    if ($canonicalUrl !== '') {
        $routePaths[$canonicalUrl] = true;
        $canonicalOwners[$canonicalUrl][] = basename($path);
    }
}

foreach (glob($root . '/content/articles/*.json') as $path) {
    $record = load_json_file($path);
    if (!is_published_record($record)) {
        continue;
    }

    $record['_file'] = $path;
    $articles[] = $record;

    $slug = (string) ($record['slug'] ?? '');
    $canonicalUrl = normalize_path((string) ($record['canonical_url'] ?? ''));

    if ($slug !== '') {
        $articlesBySlug[$slug] = true;
    }
    if ($canonicalUrl !== '') {
        $routePaths[$canonicalUrl] = true;
        $canonicalOwners[$canonicalUrl][] = basename($path);
    }
}

foreach (glob($root . '/content/pages/*.json') as $path) {
    $record = load_json_file($path);
    if (!is_published_record($record)) {
        continue;
    }

    $record['_file'] = $path;
    $pages[] = $record;

    $slug = (string) ($record['slug'] ?? '');
    if ($slug !== '') {
        $pageSlugs[$slug] = true;
    }

    $canonicalUrl = normalize_path((string) ($record['canonical_url'] ?? ''));
    if ($canonicalUrl !== '') {
        $routePaths[$canonicalUrl] = true;
        $canonicalOwners[$canonicalUrl][] = basename($path);
    }
}

foreach (glob($root . '/content/downloads/*.json') as $path) {
    $record = load_json_file($path);
    if (!is_published_record($record)) {
        continue;
    }

    $record['_file'] = $path;
    $downloads[] = $record;

    $canonicalUrl = normalize_path((string) ($record['canonical_url'] ?? ''));
    if ($canonicalUrl !== '') {
        $routePaths[$canonicalUrl] = true;
        $canonicalOwners[$canonicalUrl][] = basename($path);
    }
}

foreach ($canonicalOwners as $canonicalUrl => $owners) {
    if (count($owners) > 1) {
        foreach ($owners as $owner) {
            add_error($errors, $owner, sprintf("duplicate canonical_url '%s' also used by: %s", $canonicalUrl, implode(', ', array_diff($owners, [$owner]))));
        }
    }
}

foreach ($articles as $record) {
    $file = (string) ($record['_file'] ?? 'article');
    $primary = (string) ($record['category_primary'] ?? '');

    if ($primary === '' || !isset($categoryPaths[$primary])) {
        add_error($errors, $file, sprintf("category_primary '%s' does not match a published category path", $primary));
    }

    foreach (($record['category_secondary'] ?? []) as $secondary) {
        $secondary = (string) $secondary;
        if ($secondary === '' || !isset($categoryPaths[$secondary])) {
            add_error($errors, $file, sprintf("category_secondary '%s' does not match a published category path", $secondary));
        }
    }

    foreach (($record['related_content'] ?? []) as $slug) {
        $slug = (string) $slug;
        if ($slug === '' || !isset($articlesBySlug[$slug])) {
            add_error($errors, $file, sprintf("related_content '%s' does not match a published article slug", $slug));
        }
    }

    $sidebar = (string) ($record['sidebar_profile'] ?? '');
    if ($sidebar !== '' && !isset($sidebars[$sidebar])) {
        add_error($errors, $file, sprintf("sidebar_profile '%s' does not exist in content/sidebars", $sidebar));
    }

    $schema = is_array($record['schema'] ?? null) ? $record['schema'] : [];
    foreach (['mentions', 'has_part', 'is_part_of', 'is_based_on', 'citation'] as $field) {
        validate_schema_items($schema[$field] ?? [], $field, $file, $routePaths, $errors);
    }

    $pinTargets = $record['pin_targets'] ?? [];
    if (is_string($pinTargets)) {
        $pinTargets = [$pinTargets];
    }
    if (!is_array($pinTargets)) {
        add_error($errors, $file, 'pin_targets must be an array when present');
    } else {
        validate_article_pin_targets($pinTargets, $record, $file, $categoryPaths, $pageSlugs, $errors);
    }
}

foreach ($categories as $record) {
    $file = (string) ($record['_file'] ?? 'category');

    foreach (($record['relationships']['related_categories'] ?? []) as $slug) {
        $slug = (string) $slug;
        if ($slug === '' || !isset($categoryPaths[$slug])) {
            add_error($errors, $file, sprintf("relationships.related_categories '%s' does not match a published category slug or path", $slug));
        }
    }

    foreach (($record['featured_articles'] ?? []) as $slug) {
        $slug = (string) $slug;
        if ($slug === '' || !isset($articlesBySlug[$slug])) {
            add_error($errors, $file, sprintf("featured_articles '%s' does not match a published article slug", $slug));
        }
    }

    $sidebar = (string) ($record['sidebar_profile'] ?? '');
    if ($sidebar !== '' && !isset($sidebars[$sidebar])) {
        add_error($errors, $file, sprintf("sidebar_profile '%s' does not exist in content/sidebars", $sidebar));
    }

    $schema = is_array($record['schema'] ?? null) ? $record['schema'] : [];
    foreach (['mentions', 'has_part', 'is_part_of', 'is_based_on', 'citation'] as $field) {
        validate_schema_items($schema[$field] ?? [], $field, $file, $routePaths, $errors);
    }
}

foreach ($pages as $record) {
    $file = (string) ($record['_file'] ?? 'page');

    foreach (($record['featured_categories'] ?? []) as $slug) {
        $slug = (string) $slug;
        if ($slug === '' || !isset($categoryPaths[$slug])) {
            add_error($errors, $file, sprintf("featured_categories '%s' does not match a published category slug or path", $slug));
        }
    }

    foreach (($record['featured_articles'] ?? []) as $slug) {
        $slug = (string) $slug;
        if ($slug === '' || !isset($articlesBySlug[$slug])) {
            add_error($errors, $file, sprintf("featured_articles '%s' does not match a published article slug", $slug));
        }
    }

    $sidebar = (string) ($record['sidebar_profile'] ?? '');
    if ($sidebar !== '' && !isset($sidebars[$sidebar])) {
        add_error($errors, $file, sprintf("sidebar_profile '%s' does not exist in content/sidebars", $sidebar));
    }

    $schema = is_array($record['schema'] ?? null) ? $record['schema'] : [];
    foreach (['mentions', 'has_part', 'is_part_of', 'is_based_on', 'citation'] as $field) {
        validate_schema_items($schema[$field] ?? [], $field, $file, $routePaths, $errors);
    }
}

foreach ($downloads as $record) {
    $file = (string) ($record['_file'] ?? 'download');

    $sidebar = (string) ($record['sidebar_profile'] ?? '');
    if ($sidebar !== '' && !isset($sidebars[$sidebar])) {
        add_error($errors, $file, sprintf("sidebar_profile '%s' does not exist in content/sidebars", $sidebar));
    }

    $schema = is_array($record['schema'] ?? null) ? $record['schema'] : [];
    foreach (['mentions', 'has_part', 'is_part_of', 'is_based_on', 'citation'] as $field) {
        validate_schema_items($schema[$field] ?? [], $field, $file, $routePaths, $errors);
    }
}

if ($errors === []) {
    echo "Content integrity checks passed.\n";
    exit(0);
}

ksort($errors);

foreach ($errors as $file => $messages) {
    echo $file . PHP_EOL;
    foreach ($messages as $message) {
        echo '  - ' . $message . PHP_EOL;
    }
    echo PHP_EOL;
}

echo 'Content integrity checks failed in ' . count($errors) . " file(s).\n";
exit(1);
