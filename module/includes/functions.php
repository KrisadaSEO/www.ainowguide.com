<?php
declare(strict_types=1);

// ════════════════════════════════════════════════════════════════════════════════
// CORE JSON LOADER
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Load and decode a JSON file. Returns null on failure.
 * Never throws - always returns array or null.
 */
function load_json(string $path): ?array
{
    if (!file_exists($path) || !is_readable($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if (DEBUG) {
            error_log('JSON parse error in ' . $path . ': ' . json_last_error_msg());
        }
        return null;
    }

    return $data;
}

/**
 * Sanitize a slug: lowercase, alphanumeric and hyphens only.
 * Prevents path traversal attacks.
 */
function sanitize_slug(string $slug): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug)));
}

/**
 * Convert a display string to a URL slug.
 * "AI SEO" → "ai-seo", "Source Strategy" → "source-strategy"
 */
function make_slug(string $str): string
{
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s\-]/', '', $str);
    $str = preg_replace('/[\s\-]+/', '-', $str);
    return trim($str, '-');
}

/**
 * HTML-escape output. Use on every variable echoed to HTML.
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Replace known brand names with linked versions in already-escaped HTML.
 */
function linkify_brands(string $escaped): string
{
    return str_replace(
        'AI Digital Karma™ Web',
        '<a href="https://www.digitalkarmaweb.com" target="_blank" rel="noopener noreferrer">AI Digital Karma™ Web</a>',
        $escaped
    );
}

/**
 * Truncate a string to $length chars, appending ellipsis.
 */
function truncate(string $str, int $length = 160): string
{
    if (mb_strlen($str) <= $length) return $str;
    return mb_substr($str, 0, $length) . '…';
}


// ════════════════════════════════════════════════════════════════════════════════
// ARTICLE SCHEMA NORMALIZATION
// Converts v2 article schema (contentBlocks-based) to the unified internal format
// the renderer expects. V1 articles (core/taxonomy/content keys) pass through.
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Normalize an article to the unified rendering format.
 * Detects v2 schema by presence of 'contentBlocks' key.
 * V1 articles are returned unchanged.
 */
function normalize_article(array $data): array
{
    // V1: already has core/taxonomy/content structure
    if (isset($data['core'])) {
        return $data;
    }

    // V2: contentBlocks-based schema
    $slug           = $data['slug'] ?? '';
    $content_blocks = $data['contentBlocks'] ?? [];

    // Extract intro block; everything else becomes a section
    $intro    = '';
    $sections = [];
    foreach ($content_blocks as $block) {
        if (($block['type'] ?? '') === 'intro') {
            $intro = $block['body'] ?? '';
        } else {
            $sections[] = [
                'type'    => $block['type']    ?? 'section',
                'heading' => $block['heading'] ?? '',
                'body'    => $block['body']    ?? '',
            ];
        }
    }

    // Convert display-name tags to slugs
    $tags = array_map('make_slug', $data['tags'] ?? []);

    // Convert flat relatedArticles slugs to the {article_slug, display_name} format
    $related_articles = array_map(
        fn($s) => ['article_slug' => $s, 'display_name' => $s],
        $data['relatedArticles'] ?? []
    );

    return [
        '_schema' => 'article/v2',
        '_type'   => 'article',

        'core' => [
            'title'    => $data['title']    ?? '',
            'slug'     => $slug,
            'excerpt'  => $data['excerpt']  ?? $data['summary'] ?? '',
            'featured' => $data['featured'] ?? false,
        ],

        'taxonomy' => [
            'category_slug'    => make_slug($data['category']    ?? ''),
            'subcategory_slug' => make_slug($data['subcategory'] ?? ''),
            'tags'             => $tags,
        ],

        'content' => [
            'intro'          => $intro,
            'sections'       => $sections,
            'content_blocks' => $content_blocks,  // full typed blocks for rich rendering
        ],

        'meta' => [
            'author'                => $data['author']      ?? '',
            'created_at'            => $data['publishDate'] ?? '',
            'updated_at'            => $data['updatedDate'] ?? $data['publishDate'] ?? '',
            'published'             => ($data['status'] ?? '') === 'published',
            'reading_time_minutes'  => _estimate_reading_time($content_blocks),
        ],

        'seo' => [
            'meta_title'       => $data['metaTitle']       ?? $data['title']   ?? '',
            'meta_description' => $data['metaDescription'] ?? $data['summary'] ?? '',
            'canonical_url'    => '/article/' . $slug,
        ],

        'related_articles'  => $related_articles,
        'sidebar_modules'   => $data['sidebarModules'] ?? [],
        'json_ld'           => $data['schema']         ?? null,
    ];
}

/**
 * Estimate reading time from an array of contentBlocks.
 * Assumes ~200 words per minute.
 */
function _estimate_reading_time(array $content_blocks): int
{
    $text = '';
    foreach ($content_blocks as $block) {
        $text .= ' ' . ($block['body'] ?? '');
    }
    $words = str_word_count(strip_tags($text));
    return max(1, (int) round($words / 200));
}

/**
 * Normalize legacy single-value case-study taxonomy into the new array-based
 * case-study taxonomy so archive helpers can work safely across old data.
 */
function normalize_case_study(array $data): array
{
    $taxonomy = $data['taxonomy'] ?? [];

    $category_slugs = $taxonomy['category_slugs'] ?? [];
    if (!is_array($category_slugs)) {
        $category_slugs = [];
    }
    if (!empty($taxonomy['category_slug'])) {
        $category_slugs[] = (string) $taxonomy['category_slug'];
    }

    $tag_slugs = $taxonomy['tag_slugs'] ?? [];
    if (!is_array($tag_slugs)) {
        $tag_slugs = [];
    }
    if (!empty($taxonomy['tags']) && is_array($taxonomy['tags'])) {
        $tag_slugs = array_merge($tag_slugs, $taxonomy['tags']);
    }

    $data['taxonomy']['category_slugs'] = array_values(array_unique(array_filter(array_map(
        fn($slug) => sanitize_slug((string) $slug),
        $category_slugs
    ))));

    $data['taxonomy']['tag_slugs'] = array_values(array_unique(array_filter(array_map(
        fn($slug) => sanitize_slug((string) $slug),
        $tag_slugs
    ))));

    return $data;
}


// ════════════════════════════════════════════════════════════════════════════════
// SINGLE-ITEM LOADERS
// ════════════════════════════════════════════════════════════════════════════════

function get_case_study(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    $data = load_json(CASE_STUDIES_PATH . $slug . '.json');
    return $data !== null ? normalize_case_study($data) : null;
}

function get_concept(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(CONCEPTS_PATH . $slug . '.json');
}

function get_experiment(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(EXPERIMENTS_PATH . $slug . '.json');
}

function get_proof_entry(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(PROOF_ENTRIES_PATH . $slug . '.json');
}

function get_article(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    $data = load_json(ARTICLES_PATH . $slug . '.json');
    return $data !== null ? normalize_article($data) : null;
}

function get_team_member(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(TEAM_PATH . $slug . '.json');
}

function get_all_team_members(): array
{
    static $cache = null;
    if ($cache === null) {
        $files = glob(TEAM_PATH . '*.json');
        $cache = [];
        if ($files) {
            foreach ($files as $file) {
                $data = load_json($file);
                if ($data && ($data['meta']['published'] ?? false)) {
                    $cache[] = $data;
                }
            }
            usort($cache, fn($a, $b) =>
                ($a['meta']['sort_order'] ?? 99) <=> ($b['meta']['sort_order'] ?? 99)
            );
        }
    }
    return $cache;
}

function get_stat(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(STATS_PATH . $slug . '.json');
}

function get_glossary_term(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(GLOSSARY_PATH . $slug . '.json');
}

function get_site_settings(): ?array
{
    static $settings = null;
    if ($settings === null) {
        $settings = load_json(DATA_PATH . 'site-settings.json');
    }
    return $settings;
}

function get_sidebar_card(string $card_id): ?array
{
    $card_id = sanitize_slug($card_id);
    if ($card_id === '') return null;
    return load_json(SIDEBAR_DATA_PATH . $card_id . '.json');
}


// ════════════════════════════════════════════════════════════════════════════════
// COLLECTION LOADERS
// Load all published items from a directory. Each item = one JSON file.
// ════════════════════════════════════════════════════════════════════════════════

function get_all_case_studies(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $files = glob(CASE_STUDIES_PATH . '*.json');
        $items = [];
        if ($files) {
            foreach ($files as $file) {
                $data = load_json($file);
                if ($data === null) continue;
                $data = normalize_case_study($data);
                if ($published_only && !($data['meta']['published'] ?? false)) continue;
                $items[] = $data;
            }
        }
        $cache[$key] = $items;
    }
    return $cache[$key];
}

function get_case_study_categories(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $items = load_json(DATA_PATH . 'case-study-categories.json');
        $items = is_array($items) ? $items : [];
        if ($published_only) {
            $items = array_values(array_filter($items, fn($item) => ($item['status'] ?? 'published') === 'published'));
        }
        usort($items, fn($a, $b) =>
            (($a['sort_order'] ?? 999) <=> ($b['sort_order'] ?? 999))
            ?: strcmp($a['name'] ?? '', $b['name'] ?? '')
        );
        $cache[$key] = $items;
    }
    return $cache[$key];
}

function get_case_study_tags(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $items = load_json(DATA_PATH . 'case-study-tags.json');
        $items = is_array($items) ? $items : [];
        if ($published_only) {
            $items = array_values(array_filter($items, fn($item) => ($item['status'] ?? 'published') === 'published'));
        }
        usort($items, fn($a, $b) =>
            (($a['sort_order'] ?? 999) <=> ($b['sort_order'] ?? 999))
            ?: strcmp($a['name'] ?? '', $b['name'] ?? '')
        );
        $cache[$key] = $items;
    }
    return $cache[$key];
}

function get_case_study_category_by_slug(string $slug): ?array
{
    foreach (get_case_study_categories() as $category) {
        if (($category['slug'] ?? '') === $slug) return $category;
    }
    return null;
}

function get_case_study_tag_by_slug(string $slug): ?array
{
    foreach (get_case_study_tags() as $tag) {
        if (($tag['slug'] ?? '') === $slug) return $tag;
    }
    return null;
}

function get_all_concepts(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = _load_all_from_dir(CONCEPTS_PATH, $published_only);
    }
    return $cache[$key];
}

function get_all_experiments(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = _load_all_from_dir(EXPERIMENTS_PATH, $published_only);
    }
    return $cache[$key];
}

function get_all_proof_entries(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = _load_all_from_dir(PROOF_ENTRIES_PATH, $published_only);
    }
    return $cache[$key];
}

function get_all_glossary_terms(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = _load_all_from_dir(GLOSSARY_PATH, $published_only);
    }
    return $cache[$key];
}

function get_all_stats(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = _load_all_from_dir(STATS_PATH, $published_only);
    }
    return $cache[$key];
}

function get_all_articles(bool $published_only = true): array
{
    static $cache = [];
    $key = $published_only ? 'pub' : 'all';
    if (!array_key_exists($key, $cache)) {
        // Custom loader: normalize before the published check so v2 articles
        // (which store status rather than meta.published) are handled correctly.
        $files = glob(ARTICLES_PATH . '*.json');
        $items = [];
        if ($files) {
            foreach ($files as $file) {
                $data = load_json($file);
                if ($data === null) continue;
                $data = normalize_article($data);
                if ($published_only && !($data['meta']['published'] ?? false)) continue;
                $items[] = $data;
            }
        }
        $cache[$key] = $items;
    }
    return $cache[$key];
}

/**
 * Internal: scan a directory for *.json files and load each.
 */
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


// ════════════════════════════════════════════════════════════════════════════════
// TAXONOMY LOADERS
// ════════════════════════════════════════════════════════════════════════════════

function get_categories(): array
{
    static $data = null;
    if ($data === null) {
        $raw  = load_json(TAXONOMIES_PATH . 'categories.json');
        $data = $raw['categories'] ?? [];
    }
    return $data;
}

function get_category_by_slug(string $slug): ?array
{
    foreach (get_categories() as $cat) {
        if (($cat['slug'] ?? '') === $slug) return $cat;
    }
    return null;
}

function get_subcategory_by_slug(string $slug): ?array
{
    foreach (get_categories() as $cat) {
        foreach ($cat['subcategories'] ?? [] as $sub) {
            if (($sub['slug'] ?? '') === $slug) return $sub;
        }
    }
    return null;
}

function get_tags(): array
{
    static $data = null;
    if ($data === null) {
        $raw  = load_json(TAXONOMIES_PATH . 'tags.json');
        $data = $raw['tags'] ?? [];
    }
    return $data;
}

function get_tag_by_slug(string $slug): ?array
{
    foreach (get_tags() as $tag) {
        if (($tag['slug'] ?? '') === $slug) return $tag;
    }
    return null;
}


// ════════════════════════════════════════════════════════════════════════════════
// RELATIONSHIP LOOKUPS
// Filter collections by taxonomy fields.
// ════════════════════════════════════════════════════════════════════════════════

function get_case_studies_by_concept(string $concept_slug): array
{
    return array_values(array_filter(
        get_all_case_studies(),
        fn($cs) => ($cs['taxonomy']['concept_slug'] ?? '') === $concept_slug
    ));
}

function get_case_studies_by_category(string $category_slug): array
{
    return array_values(array_filter(
        get_all_case_studies(),
        fn($cs) => ($cs['taxonomy']['category_slug'] ?? '') === $category_slug
    ));
}

function get_case_studies_by_subcategory(string $subcategory_slug): array
{
    return array_values(array_filter(
        get_all_case_studies(),
        fn($cs) => ($cs['taxonomy']['subcategory_slug'] ?? '') === $subcategory_slug
    ));
}

function get_case_studies_by_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_case_studies(),
        fn($cs) => in_array($tag_slug, $cs['taxonomy']['tags'] ?? [], true)
    ));
}

function get_case_studies_by_case_study_category(string $category_slug): array
{
    return array_values(array_filter(
        get_all_case_studies(),
        fn($cs) => in_array($category_slug, $cs['taxonomy']['category_slugs'] ?? [], true)
    ));
}

function get_case_studies_by_case_study_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_case_studies(),
        fn($cs) => in_array($tag_slug, $cs['taxonomy']['tag_slugs'] ?? [], true)
    ));
}

function get_articles_by_category(string $category_slug): array
{
    return array_values(array_filter(
        get_all_articles(),
        fn($a) => ($a['taxonomy']['category_slug'] ?? '') === $category_slug
    ));
}

function get_articles_by_subcategory(string $subcategory_slug): array
{
    return array_values(array_filter(
        get_all_articles(),
        fn($a) => ($a['taxonomy']['subcategory_slug'] ?? '') === $subcategory_slug
    ));
}

function get_articles_by_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_articles(),
        fn($a) => in_array($tag_slug, $a['taxonomy']['tags'] ?? [], true)
    ));
}

function get_concepts_by_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_concepts(),
        fn($c) => in_array($tag_slug, $c['taxonomy']['tags'] ?? [], true)
    ));
}

function get_experiments_by_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_experiments(),
        fn($ex) => in_array($tag_slug, $ex['taxonomy']['tags'] ?? [], true)
    ));
}

function get_proof_entries_by_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_proof_entries(),
        fn($pe) => in_array($tag_slug, $pe['taxonomy']['tags'] ?? [], true)
    ));
}

function get_stats_by_category(string $category_slug): array
{
    return array_values(array_filter(
        get_all_stats(),
        fn($s) => ($s['taxonomy']['category_slug'] ?? '') === $category_slug
    ));
}

function get_stats_by_subcategory(string $subcategory_slug): array
{
    return array_values(array_filter(
        get_all_stats(),
        fn($s) => ($s['taxonomy']['subcategory_slug'] ?? '') === $subcategory_slug
    ));
}

function get_stats_by_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_stats(),
        fn($s) => in_array($tag_slug, $s['taxonomy']['tags'] ?? [], true)
    ));
}

function get_glossary_terms_by_tag(string $tag_slug): array
{
    return array_values(array_filter(
        get_all_glossary_terms(),
        fn($gt) => in_array($tag_slug, $gt['taxonomy']['tags'] ?? [], true)
    ));
}

function get_featured_stats(int $limit = 6): array
{
    $all = array_filter(
        get_all_stats(),
        fn($s) => !empty($s['core']['featured'])
    );
    return array_slice(array_values($all), 0, $limit);
}

function get_featured_case_studies(int $limit = 3): array
{
    $all = array_filter(
        get_all_case_studies(),
        fn($cs) => !empty($cs['core']['featured'])
    );
    return array_slice(array_values($all), 0, $limit);
}

function get_featured_concepts(): array
{
    return array_values(array_filter(
        get_all_concepts(),
        fn($c) => !empty($c['core']['featured'])
    ));
}

/**
 * Sort case studies for directory display.
 * Order: featured first → alphabetical by name.
 */
function sort_case_studies(array $items): array
{
    usort($items, function (array $a, array $b): int {
        $a_featured = empty($a['core']['featured']) ? 1 : 0;
        $b_featured = empty($b['core']['featured']) ? 1 : 0;
        if ($a_featured !== $b_featured) return $a_featured - $b_featured;
        return strcmp($a['core']['name'] ?? '', $b['core']['name'] ?? '');
    });
    return $items;
}

// ════════════════════════════════════════════════════════════════════════════════
// RELATED CONTENT RESOLVERS
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Resolve related case study slugs into full data objects.
 */
function resolve_related_case_studies(array $related_refs): array
{
    $resolved = [];
    foreach ($related_refs as $ref) {
        $slug = $ref['case_study_slug'] ?? '';
        if ($slug === '') continue;
        $case_study = get_case_study($slug);
        if ($case_study) {
            $resolved[] = array_merge($ref, ['_data' => $case_study]);
        }
    }
    return $resolved;
}

/**
 * Resolve an array of glossary term slugs into full term data objects.
 */
function resolve_related_glossary_terms(array $slugs): array
{
    $resolved = [];
    foreach ($slugs as $slug) {
        if (!is_string($slug) || $slug === '') continue;
        $term = get_glossary_term($slug);
        if ($term !== null) $resolved[] = $term;
    }
    return $resolved;
}

/**
 * Resolve related article slugs into full article data objects.
 */
function resolve_related_articles(array $related_articles): array
{
    $resolved = [];
    foreach ($related_articles as $ref) {
        $slug = $ref['article_slug'] ?? '';
        if ($slug === '') continue;
        $article = get_article($slug);
        if ($article) {
            $resolved[] = array_merge($ref, ['_data' => $article]);
        }
    }
    return $resolved;
}

/**
 * Resolve tag slugs into full tag objects.
 */
function resolve_tags(array $tag_slugs): array
{
    $resolved = [];
    foreach ($tag_slugs as $slug) {
        $tag = get_tag_by_slug($slug);
        if ($tag) $resolved[] = $tag;
    }
    return $resolved;
}

function resolve_case_study_categories(array $category_slugs): array
{
    $resolved = [];
    foreach ($category_slugs as $slug) {
        $category = get_case_study_category_by_slug((string) $slug);
        if ($category !== null) $resolved[] = $category;
    }
    return $resolved;
}

function resolve_case_study_tags(array $tag_slugs): array
{
    $resolved = [];
    foreach ($tag_slugs as $slug) {
        $tag = get_case_study_tag_by_slug((string) $slug);
        if ($tag !== null) $resolved[] = $tag;
    }
    return $resolved;
}

function get_case_study_proof_snippet(array $case_study): string
{
    $content = $case_study['content'] ?? [];
    $candidates = [
        $content['findings'] ?? '',
        $content['key_takeaway'] ?? '',
        $content['headline'] ?? '',
        $content['summary'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim(strip_tags((string) $candidate));
        if ($candidate === '') continue;
        $parts = preg_split('/(?<=[.!?])\s+/', $candidate, 2);
        return truncate($parts[0] ?? $candidate, 120);
    }

    return '';
}


/**
 * Load a single content item by type and slug.
 * Returns the full JSON data or null.
 */
function get_content_item(string $type, string $slug): ?array
{
    return match ($type) {
        'case-study'    => get_case_study($slug),
        'concept'       => get_concept($slug),
        'experiment'    => get_experiment($slug),
        'proof-entry'   => get_proof_entry($slug),
        'article'       => get_article($slug),
        'glossary-term' => get_glossary_term($slug),
        'stat'          => get_stat($slug),
        default         => null,
    };
}

/**
 * Resolve a universal related_items[] array into full data objects.
 *
 * Input:  [{"type": "case-study", "slug": "...", "context": "..."}, ...]
 * Output: same array with '_data' key merged for each resolved item.
 * Items that fail to resolve are silently excluded.
 */
function resolve_related_items(array $items): array
{
    $resolved = [];
    foreach ($items as $ref) {
        $type = $ref['type'] ?? '';
        $slug = $ref['slug'] ?? '';
        if ($type === '' || $slug === '') continue;

        $data = get_content_item($type, $slug);
        if ($data !== null) {
            $resolved[] = array_merge($ref, ['_data' => $data]);
        }
    }
    return $resolved;
}

/**
 * Return the title for a content item, normalizing across types.
 * Case studies and concepts use core.name; articles/experiments/proof-entries use core.title.
 */
function get_item_title(array $item): string
{
    return $item['core']['title']
        ?? $item['core']['name']
        ?? 'Untitled';
}

/**
 * Return the URL path for a content item by type and slug.
 */
function get_item_url(string $type, string $slug): string
{
    return '/' . $type . '/' . $slug;
}


// ════════════════════════════════════════════════════════════════════════════════
// SIDEBAR LOADER
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Return the ordered array of loaded sidebar card data for a given page.
 *
 * Priority chain:
 *   1. Page-level sidebar_overrides (if enabled: true and cards is non-empty)
 *      - mode "replace"  → use only the page-level cards (ignores type defaults)
 *      - mode "prepend"  → page-level cards first, then type-default cards behind them
 *                          (duplicates removed; page-level order preserved)
 *   2. Page-type default from site-settings.json  (e.g. case-study_sidebar_cards)
 *   3. Global default from site-settings.json     (default_sidebar_cards)
 *
 * sidebar_overrides schema (in any content JSON file):
 *   "sidebar_overrides": {
 *     "enabled": true,
 *     "mode":    "replace" | "prepend",
 *     "cards":   ["card-id-1", "card-id-2"]
 *   }
 */
function get_sidebar_cards_for_page(string $page_type, ?array $overrides = null): array
{
    // Resolve type-level + global defaults regardless - needed for prepend merge
    $settings  = get_site_settings();
    $defaults  = $settings['defaults'] ?? [];
    $type_key  = $page_type . '_sidebar_cards';
    $type_ids  = $defaults[$type_key] ?? $defaults['default_sidebar_cards'] ?? [];

    if (!empty($overrides['enabled']) && !empty($overrides['cards'])) {
        $page_ids = $overrides['cards'];
        $mode     = $overrides['mode'] ?? 'replace';

        if ($mode === 'prepend') {
            // Page cards lead; type defaults follow - no duplicates
            $seen     = [];
            $card_ids = [];
            foreach (array_merge($page_ids, $type_ids) as $id) {
                if (!isset($seen[$id])) {
                    $seen[$id]  = true;
                    $card_ids[] = $id;
                }
            }
        } else {
            // replace (default): page cards only
            $card_ids = $page_ids;
        }
    } else {
        $card_ids = $type_ids;
    }

    $cards = [];
    foreach ($card_ids as $id) {
        $card = get_sidebar_card($id);
        if ($card !== null) $cards[] = $card;
    }
    return $cards;
}


// ════════════════════════════════════════════════════════════════════════════════
// BREADCRUMB BUILDER
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Build a breadcrumb trail for any page type.
 * Returns: array of ['label' => string, 'url' => string|null]
 * Last item always has url = null (current page, not linked).
 */
function build_breadcrumbs(string $page_type, array $data = []): array
{
    $home = [['label' => 'Home', 'url' => '/']];

    switch ($page_type) {
        case 'home':
            return [];

        case 'case-study':
            $crumbs = [['label' => 'Case Studies', 'url' => '/case-studies']];
            $concept_slug = $data['taxonomy']['concept_slug'] ?? '';
            if ($concept_slug) {
                $concept = get_concept($concept_slug);
                if ($concept) {
                    $crumbs[] = [
                        'label' => $concept['core']['name'],
                        'url'   => '/concept/' . $concept['core']['slug'],
                    ];
                }
            }
            $crumbs[] = ['label' => $data['core']['name'] ?? 'Case Study', 'url' => null];
            return array_merge($home, $crumbs);

        case 'concept':
            return array_merge($home, [
                ['label' => 'Concepts', 'url' => '/concepts'],
                ['label' => $data['core']['name'] ?? 'Concept', 'url' => null],
            ]);

        case 'experiment':
            return array_merge($home, [
                ['label' => 'Experiments', 'url' => '/best-seo'],
                ['label' => $data['core']['title'] ?? $data['core']['name'] ?? 'Experiment', 'url' => null],
            ]);

        case 'proof-entry':
            return array_merge($home, [
                ['label' => 'Proof Entries', 'url' => '/seo-proof'],
                ['label' => $data['core']['title'] ?? $data['core']['name'] ?? 'Proof Entry', 'url' => null],
            ]);

        case 'article':
            $crumbs = [['label' => 'Articles', 'url' => '/articles']];
            $cat_slug = $data['taxonomy']['category_slug'] ?? '';
            if ($cat_slug) {
                $cat = get_category_by_slug($cat_slug);
                if ($cat) {
                    $crumbs[] = ['label' => $cat['name'], 'url' => '/category/' . $cat['slug']];
                }
            }
            $crumbs[] = ['label' => $data['core']['title'] ?? 'Article', 'url' => null];
            return array_merge($home, $crumbs);

        case 'category':
            return array_merge($home, [
                ['label' => 'Case Studies', 'url' => '/case-studies'],
                ['label' => $data['name'] ?? 'Category', 'url' => null],
            ]);

        case 'subcategory':
            $crumbs = [['label' => 'Case Studies', 'url' => '/case-studies']];
            $parent_slug = $data['parent_category_slug'] ?? '';
            if ($parent_slug) {
                $parent = get_category_by_slug($parent_slug);
                if ($parent) {
                    $crumbs[] = ['label' => $parent['name'], 'url' => '/category/' . $parent['slug']];
                }
            }
            $crumbs[] = ['label' => $data['name'] ?? 'Subcategory', 'url' => null];
            return array_merge($home, $crumbs);

        case 'tag':
            return array_merge($home, [
                ['label' => 'Tags', 'url' => '/tags'],
                ['label' => $data['name'] ?? 'Tag', 'url' => null],
            ]);

        case 'about':
            return array_merge($home, [
                ['label' => 'About', 'url' => null],
            ]);

        case 'case-study-category':
            return array_merge($home, [
                ['label' => 'Case Studies', 'url' => '/case-studies'],
                ['label' => $data['name'] ?? 'Category', 'url' => null],
            ]);

        case 'case-study-tag':
            return array_merge($home, [
                ['label' => 'Case Studies', 'url' => '/case-studies'],
                ['label' => $data['name'] ?? 'Tag', 'url' => null],
            ]);

        case 'contact':
            return array_merge($home, [
                ['label' => 'Contact', 'url' => null],
            ]);

        case 'team-member':
            return array_merge($home, [
                ['label' => 'About', 'url' => '/about'],
                ['label' => $data['core']['name'] ?? 'Team Member', 'url' => null],
            ]);

        case 'stat':
            $crumbs = [['label' => 'Interesting Stats', 'url' => '/stats']];
            $cat_slug = $data['taxonomy']['category_slug'] ?? '';
            if ($cat_slug && $cat_slug !== 'interesting-stats') {
                $cat = get_category_by_slug($cat_slug);
                if ($cat) {
                    $crumbs[] = ['label' => $cat['name'], 'url' => '/category/' . $cat['slug']];
                }
            }
            $crumbs[] = ['label' => $data['core']['title'] ?? 'Stat', 'url' => null];
            return array_merge($home, $crumbs);

        case 'glossary':
            return array_merge($home, [
                ['label' => 'Glossary', 'url' => null],
            ]);

        case 'glossary-term':
            return array_merge($home, [
                ['label' => 'Glossary', 'url' => '/glossary'],
                ['label' => $data['core']['term'] ?? 'Term', 'url' => null],
            ]);

        default:
            return $home;
    }
}


// ════════════════════════════════════════════════════════════════════════════════
// VALIDATION
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Check that all dot-notation required fields exist and are non-empty in $data.
 * 'core.name' checks $data['core']['name'].
 */
function validate_required_fields(array $data, array $required_fields): bool
{
    foreach ($required_fields as $field) {
        $keys  = explode('.', $field);
        $value = $data;
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) return false;
            $value = $value[$key];
        }
        if ($value === null || $value === '' || $value === []) return false;
    }
    return true;
}

function get_page_data(string $slug): ?array
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return load_json(PAGES_PATH . $slug . '.json');
}

function get_contact_form_config(): ?array
{
    static $config = null;
    if ($config === null) {
        $config = load_json(DATA_PATH . 'contact-form-config.json');
    }
    return $config;
}

function flash_set(string $key, mixed $value): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    $_SESSION['_flash'][$key] = $value;
}

function flash_consume(string $key, mixed $default = null): mixed
{
    if (session_status() !== PHP_SESSION_ACTIVE) return $default;

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

function redirect(string $path, int $status = 303): never
{
    header('Location: ' . $path, true, $status);
    exit;
}

function get_contact_form_state(): array
{
    $config = get_contact_form_config() ?? [];
    $values = flash_consume('contact.old', []);
    $errors = flash_consume('contact.errors', []);
    $status = flash_consume('contact.status', null);

    if (!is_array($values)) $values = [];
    if (!is_array($errors)) $errors = [];

    $intent = sanitize_slug((string) ($_GET['intent'] ?? ''));
    $intent_map = $config['intent_map'] ?? [];
    if (!isset($values['inquiry_type']) && $intent !== '' && isset($intent_map[$intent])) {
        $values['inquiry_type'] = (string) $intent_map[$intent];
    }

    return [
        'values'       => $values,
        'errors'       => $errors,
        'status'       => is_array($status) ? $status : null,
        'form_started' => (string) time(),
    ];
}

function process_contact_form_submission(): never
{
    $page = get_page_data('contact') ?? [];
    $copy = $page['form'] ?? [];
    $config = get_contact_form_config() ?? [];

    $fields = $config['fields'] ?? [];
    $inquiry_types = $config['inquiry_types'] ?? [];
    $stages = $config['stages'] ?? [];
    $delivery = $config['delivery'] ?? [];
    $spam = $config['spam_protection'] ?? [];

    $values = [];
    foreach ($fields as $field) {
        $name = (string) ($field['name'] ?? '');
        if ($name === '') continue;
        $values[$name] = trim((string) ($_POST[$name] ?? ''));
    }

    $honeypot_field = (string) ($spam['honeypot_field'] ?? 'company');
    $honeypot_value = trim((string) ($_POST[$honeypot_field] ?? ''));
    $form_started = (int) ($_POST['form_started'] ?? 0);
    $minimum_seconds = max(1, (int) ($spam['minimum_seconds'] ?? 3));
    $success_message = (string) ($copy['success_message'] ?? 'Message received.');

    if ($honeypot_value !== '' || $form_started <= 0 || (time() - $form_started) < $minimum_seconds) {
        flash_set('contact.status', ['type' => 'success', 'message' => $success_message]);
        redirect('/contact');
    }

    $errors = [];
    $allowed_inquiry_types = [];
    foreach ($inquiry_types as $option) {
        $value = (string) ($option['value'] ?? '');
        $label = (string) ($option['label'] ?? $value);
        if ($value !== '') $allowed_inquiry_types[$value] = $label;
    }

    $allowed_stages = [];
    foreach ($stages as $option) {
        $value = (string) ($option['value'] ?? '');
        $label = (string) ($option['label'] ?? $value);
        if ($value !== '') $allowed_stages[$value] = $label;
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
        $normalized_website = $website;
        if (!preg_match('#^https?://#i', $normalized_website)) {
            $normalized_website = 'https://' . $normalized_website;
        }
        if (!filter_var($normalized_website, FILTER_VALIDATE_URL)) {
            $errors['website'] = 'Use a valid website or project URL, or leave it blank.';
        } else {
            $values['website'] = $normalized_website;
        }
    }

    $inquiry_type = $values['inquiry_type'] ?? '';
    if ($inquiry_type === '' || !isset($allowed_inquiry_types[$inquiry_type])) {
        $errors['inquiry_type'] = 'Choose the option that best matches the inquiry.';
    }

    $stage = $values['stage'] ?? '';
    if ($stage !== '' && !isset($allowed_stages[$stage])) {
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
        flash_set('contact.errors', $errors);
        flash_set('contact.old', $values);
        flash_set('contact.status', [
            'type' => 'error',
            'message' => (string) ($copy['error_message'] ?? 'Please fix the highlighted fields and try again.'),
        ]);
        redirect('/contact');
    }

    $to_email = (string) ($delivery['to_email'] ?? 'hello@realseolife.com');
    $from_email = (string) ($delivery['from_email'] ?? $to_email);
    $from_name = (string) ($delivery['from_name'] ?? SITE_NAME . ' Contact');

    $clean_name = trim(preg_replace('/[\r\n]+/', ' ', $values['name'] ?? ''));
    $clean_email = trim(preg_replace('/[\r\n]+/', '', $email));
    $clean_from_name = trim(preg_replace('/[\r\n]+/', ' ', $from_name));
    $clean_from_email = trim(preg_replace('/[\r\n]+/', '', $from_email));

    $subject = '[' . SITE_NAME . '] ' . ($allowed_inquiry_types[$inquiry_type] ?? 'Contact Inquiry') . ' - ' . $clean_name;
    $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $body_lines = [
        'New contact form submission from ' . SITE_NAME,
        '',
        'Name: ' . $clean_name,
        'Email: ' . $clean_email,
        'Website or project URL: ' . ($values['website'] ?: 'Not provided'),
        'Inquiry type: ' . ($allowed_inquiry_types[$inquiry_type] ?? $inquiry_type),
        'Stage: ' . ($stage !== '' ? ($allowed_stages[$stage] ?? $stage) : 'Not provided'),
        '',
        'What they are trying to accomplish:',
        $goal,
    ];

    if ($message !== '') {
        $body_lines[] = '';
        $body_lines[] = 'Additional context:';
        $body_lines[] = $message;
    }

    $body_lines[] = '';
    $body_lines[] = 'Submitted at: ' . gmdate('Y-m-d H:i:s') . ' UTC';
    $body_lines[] = 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $body_lines[] = 'User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $clean_from_name . ' <' . $clean_from_email . '>',
        'Reply-To: ' . $clean_name . ' <' . $clean_email . '>',
        'X-Mailer: PHP/' . phpversion(),
    ];

    $sent = mail($to_email, $encoded_subject, implode("\n", $body_lines), implode("\r\n", $headers));

    if ($sent) {
        flash_set('contact.status', ['type' => 'success', 'message' => $success_message]);
        redirect('/contact');
    }

    flash_set('contact.old', $values);
    flash_set('contact.status', [
        'type' => 'error',
        'message' => (string) ($copy['fallback_note'] ?? 'The form could not be sent right now. Please email hello@realseolife.com directly.'),
    ]);
    redirect('/contact');
}


// ═══════════════════════════════════════════════════════════════════════════════
// REDIRECT SYSTEM
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Check if the current request URI matches a redirect rule.
 * If matched, sends 301/302 and exits. Call before the router.
 */
function check_redirects(string $request_uri): void
{
    $file = DATA_PATH . 'redirects.json';
    if (!is_file($file)) return;

    $rules = json_decode(file_get_contents($file), true);
    if (!is_array($rules)) return;

    $path = rtrim(strtolower($request_uri), '/');
    if ($path === '') $path = '/';

    foreach ($rules as $rule) {
        $from = rtrim(strtolower($rule['from'] ?? ''), '/');
        if ($from === '') continue;
        if ($from === $path) {
            $to   = $rule['to'] ?? '/';
            $code = ($rule['type'] ?? 301) === 302 ? 302 : 301;
            header('Location: ' . $to, true, $code);
            exit;
        }
    }
}

/**
 * Log a 404 hit. Deduplicates by URL and increments hit count.
 * Caps the log at 500 entries (oldest low-hit entries pruned first).
 */
function log_404(string $request_uri): void
{
    $file = DATA_PATH . '404-log.json';

    $log = [];
    if (is_file($file)) {
        $log = json_decode(file_get_contents($file), true);
        if (!is_array($log)) $log = [];
    }

    $path = $request_uri;
    $now  = date('Y-m-d H:i:s');

    // Find existing entry
    $found = false;
    foreach ($log as &$entry) {
        if (($entry['url'] ?? '') === $path) {
            $entry['hits']     = ($entry['hits'] ?? 1) + 1;
            $entry['last_hit'] = $now;
            $entry['referrer'] = $_SERVER['HTTP_REFERER'] ?? '';
            $found = true;
            break;
        }
    }
    unset($entry);

    if (!$found) {
        $log[] = [
            'url'        => $path,
            'hits'       => 1,
            'first_hit'  => $now,
            'last_hit'   => $now,
            'referrer'   => $_SERVER['HTTP_REFERER'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            'resolved'   => false,
        ];
    }

    // Prune to 500 entries - keep highest-hit entries
    if (count($log) > 500) {
        usort($log, fn($a, $b) => ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0));
        $log = array_slice($log, 0, 500);
    }

    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Load all redirect rules.
 */
function get_redirects(): array
{
    $file = DATA_PATH . 'redirects.json';
    if (!is_file($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/**
 * Save redirect rules to disk.
 */
function save_redirects(array $rules): void
{
    $file = DATA_PATH . 'redirects.json';
    file_put_contents($file, json_encode(array_values($rules), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Load the 404 log, sorted by hits descending.
 */
function get_404_log(): array
{
    $file = DATA_PATH . '404-log.json';
    if (!is_file($file)) return [];
    $log = json_decode(file_get_contents($file), true);
    if (!is_array($log)) return [];
    usort($log, fn($a, $b) => ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0));
    return $log;
}

/**
 * Remove a single URL from the 404 log (after creating a redirect for it).
 */
function remove_from_404_log(string $url): void
{
    $file = DATA_PATH . '404-log.json';
    if (!is_file($file)) return;
    $log = json_decode(file_get_contents($file), true);
    if (!is_array($log)) return;
    $log = array_values(array_filter($log, fn($e) => ($e['url'] ?? '') !== $url));
    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Purge the entire 404 log.
 */
function purge_404_log(): void
{
    $file = DATA_PATH . '404-log.json';
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
}

/**
 * Remove all 404 log entries whose hit count is strictly below $min_hits.
 */
function purge_404_log_below(int $min_hits): void
{
    $file = DATA_PATH . '404-log.json';
    if (!is_file($file)) return;
    $log = json_decode(file_get_contents($file), true);
    if (!is_array($log)) return;
    $log = array_values(array_filter($log, fn($e) => (int) ($e['hits'] ?? 0) >= $min_hits));
    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Validate the admin token from config or environment.
 */
function verify_admin_token(): bool
{
    $token = defined('ADMIN_TOKEN') ? ADMIN_TOKEN : '';
    if ($token === '') return false;
    return hash_equals($token, $_GET['token'] ?? $_POST['token'] ?? '');
}
