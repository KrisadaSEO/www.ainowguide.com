<?php
declare(strict_types=1);

function admin_content_type_configs(): array
{
    return [
        'channel' => ['label' => 'Channels'],
        'session' => ['label' => 'Sessions'],
        'page'    => ['label' => 'Pages'],
    ];
}

function admin_get_content_type_config(string $type): ?array
{
    $type = sanitize_slug($type);
    return admin_content_type_configs()[$type] ?? null;
}

function admin_get_content_public_url(string $type, string $slug): ?string
{
    $slug = sanitize_slug($slug);
    return match ($type) {
        'channel' => '/channels/' . $slug,
        'session' => '/sessions/' . $slug,
        'page'    => match ($slug) {
            'home'  => '/',
            'about' => '/about',
            default => null,
        },
        default => null,
    };
}

function admin_get_content_file_path(string $type, string $slug): ?string
{
    $slug = sanitize_slug($slug);
    if ($slug === '') return null;
    return match ($type) {
        'channel' => CHANNELS_PATH . $slug . '.json',
        'session' => SESSIONS_PATH . $slug . '.json',
        'page'    => PAGES_PATH . $slug . '.json',
        default   => null,
    };
}

function admin_get_content_items(?string $filter_type = null): array
{
    $items = [];
    $types = $filter_type !== null && $filter_type !== ''
        ? [$filter_type]
        : array_keys(admin_content_type_configs());

    foreach ($types as $type) {
        if (!admin_get_content_type_config($type)) continue;
        foreach (admin_get_content_items_for_type($type) as $item) {
            $items[] = $item;
        }
    }

    usort($items, function (array $a, array $b): int {
        $a_updated = $a['updated_at'] ?? '';
        $b_updated = $b['updated_at'] ?? '';
        if ($a_updated !== $b_updated) return strcmp($b_updated, $a_updated);
        if (($a['type_label'] ?? '') !== ($b['type_label'] ?? '')) {
            return strcmp($a['type_label'] ?? '', $b['type_label'] ?? '');
        }
        return strcmp($a['title'] ?? '', $b['title'] ?? '');
    });

    return $items;
}

function admin_get_content_items_for_type(string $type): array
{
    $dir = match ($type) {
        'channel' => CHANNELS_PATH,
        'session' => SESSIONS_PATH,
        'page'    => PAGES_PATH,
        default   => null,
    };

    if ($dir === null) return [];

    $files = glob($dir . '*.json') ?: [];
    $items = [];

    foreach ($files as $file) {
        $data = load_json($file);
        if (!is_array($data)) continue;
        $slug = basename($file, '.json');

        $items[] = [
            'type'       => $type,
            'type_label' => admin_content_type_configs()[$type]['label'] ?? $type,
            'slug'       => $slug,
            'title'      => $data['core']['title'] ?? $data['seo']['meta_title'] ?? $slug,
            'published'  => (bool) ($data['meta']['published'] ?? false),
            'updated_at' => $data['meta']['updated_at'] ?? '',
            'public_url' => admin_get_content_public_url($type, $slug),
            'file_path'  => $file,
        ];
    }

    return $items;
}

function admin_prepare_content_entry(string $type, string $slug): ?array
{
    $file = admin_get_content_file_path($type, $slug);
    if ($file === null || !is_file($file)) return null;

    $data = load_json($file);
    if (!is_array($data)) return null;

    $field_groups = _admin_build_field_groups($type, $data);

    return [
        'type'        => $type,
        'type_label'  => admin_content_type_configs()[$type]['label'] ?? $type,
        'slug'        => $slug,
        'title'       => $data['core']['title'] ?? $data['seo']['meta_title'] ?? $slug,
        'public_url'  => admin_get_content_public_url($type, $slug),
        'file_path'   => $file,
        'field_groups' => $field_groups,
    ];
}

function _admin_build_field_groups(string $type, array $data): array
{
    return match ($type) {
        'channel' => [
            'Core'    => _admin_fields_from_paths($data, [
                ['key' => 'title',       'path' => 'core.title',       'label' => 'Title',       'kind' => 'string',  'input' => 'text'],
                ['key' => 'description', 'path' => 'core.description', 'label' => 'Description', 'kind' => 'string',  'input' => 'textarea'],
                ['key' => 'icon',        'path' => 'core.icon',        'label' => 'Icon Emoji',  'kind' => 'string',  'input' => 'text'],
                ['key' => 'sort_order',  'path' => 'core.sort_order',  'label' => 'Sort Order',  'kind' => 'int',     'input' => 'number'],
            ]),
            'Content' => _admin_fields_from_paths($data, [
                ['key' => 'about',           'path' => 'content.about',           'label' => 'About',           'kind' => 'string', 'input' => 'textarea'],
                ['key' => 'what_to_expect',  'path' => 'content.what_to_expect',  'label' => 'What to Expect',  'kind' => 'string', 'input' => 'textarea'],
            ]),
            'SEO'     => _admin_fields_from_paths($data, [
                ['key' => 'meta_title',       'path' => 'seo.meta_title',       'label' => 'Meta Title',       'kind' => 'string', 'input' => 'text'],
                ['key' => 'meta_description', 'path' => 'seo.meta_description', 'label' => 'Meta Description', 'kind' => 'string', 'input' => 'textarea'],
            ]),
            'Meta'    => _admin_fields_from_paths($data, [
                ['key' => 'published',  'path' => 'meta.published',  'label' => 'Published', 'kind' => 'bool', 'input' => 'checkbox'],
            ]),
        ],
        'session' => [
            'Core'    => _admin_fields_from_paths($data, [
                ['key' => 'title',      'path' => 'core.title',      'label' => 'Title',      'kind' => 'string', 'input' => 'text'],
                ['key' => 'date',       'path' => 'core.date',       'label' => 'Date',       'kind' => 'string', 'input' => 'date'],
                ['key' => 'channel',    'path' => 'core.channel',    'label' => 'Channel Slug', 'kind' => 'string', 'input' => 'text'],
                ['key' => 'visibility', 'path' => 'core.visibility', 'label' => 'Visibility (public/member/private)', 'kind' => 'string', 'input' => 'text'],
                ['key' => 'duration',   'path' => 'core.duration',   'label' => 'Duration',   'kind' => 'string', 'input' => 'text'],
                ['key' => 'video_url',  'path' => 'core.video_url',  'label' => 'Video URL',  'kind' => 'string', 'input' => 'text'],
                ['key' => 'thumbnail',  'path' => 'core.thumbnail',  'label' => 'Thumbnail URL', 'kind' => 'string', 'input' => 'text'],
            ]),
            'Content' => _admin_fields_from_paths($data, [
                ['key' => 'summary',      'path' => 'content.summary',      'label' => 'Summary',      'kind' => 'string', 'input' => 'textarea'],
                ['key' => 'description',  'path' => 'content.description',  'label' => 'Description',  'kind' => 'string', 'input' => 'textarea'],
                ['key' => 'build_notes',  'path' => 'content.build_notes',  'label' => 'Build Notes',  'kind' => 'string', 'input' => 'textarea'],
            ]),
            'SEO'     => _admin_fields_from_paths($data, [
                ['key' => 'meta_title',       'path' => 'seo.meta_title',       'label' => 'Meta Title',       'kind' => 'string', 'input' => 'text'],
                ['key' => 'meta_description', 'path' => 'seo.meta_description', 'label' => 'Meta Description', 'kind' => 'string', 'input' => 'textarea'],
            ]),
            'Meta'    => _admin_fields_from_paths($data, [
                ['key' => 'related_property', 'path' => 'meta.related_property', 'label' => 'Related Property', 'kind' => 'string', 'input' => 'text'],
                ['key' => 'published',        'path' => 'meta.published',        'label' => 'Published',        'kind' => 'bool',   'input' => 'checkbox'],
            ]),
        ],
        'page' => [
            'Hero'    => _admin_fields_from_paths($data, [
                ['key' => 'headline',    'path' => 'hero.headline',    'label' => 'Headline',    'kind' => 'string', 'input' => 'text'],
                ['key' => 'subheadline', 'path' => 'hero.subheadline', 'label' => 'Subheadline', 'kind' => 'string', 'input' => 'textarea'],
            ]),
            'SEO'     => _admin_fields_from_paths($data, [
                ['key' => 'meta_title',       'path' => 'seo.meta_title',       'label' => 'Meta Title',       'kind' => 'string', 'input' => 'text'],
                ['key' => 'meta_description', 'path' => 'seo.meta_description', 'label' => 'Meta Description', 'kind' => 'string', 'input' => 'textarea'],
            ]),
        ],
        default => [],
    };
}

function _admin_fields_from_paths(array $data, array $field_defs): array
{
    $fields = [];
    foreach ($field_defs as $def) {
        $value = _admin_get_nested($data, $def['path']);
        $fields[] = [
            'key'   => $def['key'],
            'path'  => $def['path'],
            'label' => $def['label'],
            'kind'  => $def['kind'],
            'input' => $def['input'],
            'value' => $value,
        ];
    }
    return $fields;
}

function _admin_get_nested(array $data, string $dot_path): mixed
{
    $keys    = explode('.', $dot_path);
    $current = $data;
    foreach ($keys as $key) {
        if (!is_array($current) || !array_key_exists($key, $current)) return null;
        $current = $current[$key];
    }
    return $current;
}

function admin_save_content_entry(
    string $type,
    string $slug,
    array  $field_paths,
    array  $field_kinds,
    array  $field_values,
    string $commit_message = ''
): array {
    $file = admin_get_content_file_path($type, $slug);
    if ($file === null || !is_file($file)) {
        return ['ok' => false, 'message' => 'File not found.'];
    }

    $data = load_json($file);
    if (!is_array($data)) return ['ok' => false, 'message' => 'Could not read file.'];

    foreach ($field_paths as $i => $dot_path) {
        $kind  = (string) ($field_kinds[$i] ?? 'string');
        $raw   = (string) ($field_values[$i] ?? '');
        $value = match ($kind) {
            'bool'   => $raw === '1',
            'int'    => (int) $raw,
            'float'  => (float) $raw,
            default  => admin_normalize_text_input($raw),
        };
        $data = _admin_set_nested($data, (string) $dot_path, $value);
    }

    $data['meta']['updated_at'] = date('Y-m-d');

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($file, $json) === false) {
        return ['ok' => false, 'message' => 'Failed to write file.'];
    }

    if (GITHUB_TOKEN !== '' && $commit_message !== '') {
        github_commit_file($file, $json, $commit_message);
    }

    return ['ok' => true, 'message' => 'Saved successfully.'];
}

function _admin_set_nested(array $data, string $dot_path, mixed $value): array
{
    $keys = explode('.', $dot_path);
    $ref  = &$data;
    foreach ($keys as $key) {
        if (!is_array($ref)) $ref = [];
        if (!array_key_exists($key, $ref) || !is_array($ref[$key])) {
            $ref[$key] = [];
        }
        $ref = &$ref[$key];
    }
    $ref = $value;
    return $data;
}

function admin_save_site_settings(array $post): array
{
    $file = DATA_PATH . 'site-settings.json';
    $data = load_json($file);
    if (!is_array($data)) return ['ok' => false, 'message' => 'Could not read settings file.'];

    if (isset($post['site_name']))    $data['site']['name']    = admin_normalize_text_input((string) $post['site_name']);
    if (isset($post['site_tagline'])) $data['site']['tagline'] = admin_normalize_text_input((string) $post['site_tagline']);
    if (isset($post['site_email']))   $data['site']['email']   = admin_normalize_text_input((string) $post['site_email']);

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($file, $json) === false) {
        return ['ok' => false, 'message' => 'Failed to write settings.'];
    }

    return ['ok' => true, 'message' => 'Settings saved.'];
}
