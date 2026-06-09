<?php
declare(strict_types=1);

function build_home_json_ld(string $page_title, string $meta_desc): array
{
    $site_url = rtrim(SITE_URL, '/');
    return [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@id'         => $site_url . '/#organization',
                '@type'       => 'Organization',
                'name'        => SITE_NAME,
                'url'         => $site_url . '/',
                'description' => SITE_META_DESC,
                'founder'     => ['@id' => 'https://www.krisada.com/#person'],
            ],
            [
                '@id'         => $site_url . '/#website',
                '@type'       => 'WebSite',
                'url'         => $site_url . '/',
                'name'        => SITE_NAME,
                'description' => SITE_META_DESC,
                'publisher'   => ['@id' => $site_url . '/#organization'],
                'inLanguage'  => 'en',
            ],
            [
                '@id'         => 'https://www.krisada.com/#person',
                '@type'       => 'Person',
                'name'        => 'Krisada',
                'url'         => 'https://www.krisada.com/',
                'knowsAbout'  => ['AI', 'SEO', 'digital assets', 'PHP', 'build in public', 'website acquisition'],
            ],
            [
                '@id'         => $site_url . '/#webpage',
                '@type'       => 'WebPage',
                'url'         => $site_url . '/',
                'name'        => $page_title,
                'description' => $meta_desc,
                'isPartOf'    => ['@id' => $site_url . '/#website'],
                'about'       => ['@id' => $site_url . '/#organization'],
            ],
        ],
    ];
}

function build_page_json_ld(string $page_title, string $meta_desc, string $canonical_url): array
{
    $site_url = rtrim(SITE_URL, '/');
    $full_url = $site_url . $canonical_url;
    return [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@id'         => $full_url . '#webpage',
                '@type'       => 'WebPage',
                'url'         => $full_url,
                'name'        => $page_title,
                'description' => $meta_desc,
                'isPartOf'    => ['@id' => $site_url . '/#website'],
                'publisher'   => ['@id' => $site_url . '/#organization'],
                'inLanguage'  => 'en',
            ],
        ],
    ];
}

function build_channel_json_ld(array $data, array $sessions, string $page_title, string $meta_desc, string $canonical_url): array
{
    $site_url = rtrim(SITE_URL, '/');
    $full_url = $site_url . $canonical_url;

    $item_list = [];
    foreach ($sessions as $i => $session) {
        $sess_url = $site_url . '/sessions/' . ($session['core']['slug'] ?? '');
        $item_list[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'url'      => $sess_url,
            'name'     => $session['core']['title'] ?? '',
        ];
    }

    $graph = [
        [
            '@id'         => $full_url . '#webpage',
            '@type'       => 'CollectionPage',
            'url'         => $full_url,
            'name'        => $page_title,
            'description' => $meta_desc,
            'isPartOf'    => ['@id' => $site_url . '/#website'],
            'publisher'   => ['@id' => $site_url . '/#organization'],
            'inLanguage'  => 'en',
        ],
    ];

    if (!empty($item_list)) {
        $graph[] = [
            '@type'           => 'ItemList',
            'name'            => $data['core']['title'] ?? '',
            'description'     => $data['core']['description'] ?? '',
            'url'             => $full_url,
            'numberOfItems'   => count($item_list),
            'itemListElement' => $item_list,
        ];
    }

    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

function build_session_json_ld(array $data, string $page_title, string $meta_desc, string $canonical_url): array
{
    $site_url = rtrim(SITE_URL, '/');
    $full_url = $site_url . $canonical_url;

    $graph = [
        [
            '@id'         => $full_url . '#webpage',
            '@type'       => 'WebPage',
            'url'         => $full_url,
            'name'        => $page_title,
            'description' => $meta_desc,
            'isPartOf'    => ['@id' => $site_url . '/#website'],
            'publisher'   => ['@id' => $site_url . '/#organization'],
            'inLanguage'  => 'en',
            'datePublished' => $data['core']['date'] ?? '',
        ],
    ];

    $video_url = trim((string) ($data['core']['video_url'] ?? ''));
    if ($video_url !== '') {
        $graph[] = [
            '@type'       => 'VideoObject',
            'name'        => $data['core']['title'] ?? '',
            'description' => $data['content']['summary'] ?? $meta_desc,
            'contentUrl'  => $video_url,
            'uploadDate'  => $data['core']['date'] ?? '',
            'author'      => ['@id' => 'https://www.krisada.com/#person'],
            'publisher'   => ['@id' => $site_url . '/#organization'],
        ];
    }

    return ['@context' => 'https://schema.org', '@graph' => $graph];
}
