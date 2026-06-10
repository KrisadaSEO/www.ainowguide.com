<?php
declare(strict_types=1);

$root       = dirname(__DIR__);
$registryDir = $root . '/downloads/krisada-constellation-system/krisada-constellation-system/website-registry';
$constDir    = $root . '/downloads/krisada-constellation-system/krisada-constellation-system/constellations';
$catOut      = $root . '/content/directory/categories';
$listOut     = $root . '/content/directory/listings';

function slug_from_name(string $name): string
{
    return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
}

function domain_to_slug(string $domain): string
{
    return str_replace('.', '-', strtolower($domain));
}

function write_json(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "  wrote: " . basename($path) . "\n";
}

// -- Load registry --
$registry = [];
foreach (glob($registryDir . '/*.json') as $file) {
    $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (is_array($data)) {
        $registry[(string) ($data['site'] ?? '')] = $data;
    }
}
echo "Loaded " . count($registry) . " registry entries.\n";

// -- Load constellation domain maps --
$constellationDomains = [];
$constellationMeta    = [];
foreach (glob($constDir . '/*/domains.json') as $file) {
    $data   = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $id     = (string) ($data['constellation_id'] ?? '');
    $name   = (string) ($data['constellation'] ?? '');
    $slug   = slug_from_name($name);
    $flagship = (string) ($data['flagship'] ?? '');

    $constellationMeta[$id] = [
        'id'       => $id,
        'name'     => $name,
        'slug'     => $slug,
        'flagship' => $flagship,
    ];

    foreach ((array) ($data['domains'] ?? []) as $entry) {
        $domain = (string) ($entry['domain'] ?? '');
        if ($domain !== '') {
            $constellationDomains[$domain] = array_merge($entry, [
                'constellation_id'   => $id,
                'constellation_name' => $name,
                'constellation_slug' => $slug,
            ]);
        }
    }
}
echo "Loaded " . count($constellationDomains) . " constellation domain entries.\n";

// Satellite constellation (domains not in any constellation)
$constellationMeta['satellite'] = [
    'id'       => 'satellite',
    'name'     => 'Satellites',
    'slug'     => 'satellites',
    'flagship' => '',
];

// -- Write portfolio root category --
echo "\nWriting categories...\n";
write_json($catOut . '/portfolio.json', [
    'type'            => 'dir_category',
    'slug'            => 'portfolio',
    'path'            => 'portfolio',
    'parent_path'     => '',
    'title'           => 'Portfolio',
    'canonical_url'   => '/directory/portfolio/',
    'description'     => 'The full Krisada domain portfolio organized by constellation - related-niche clusters of sites built to own search territory.',
    'directory_type'  => 'portfolio',
    'status'          => 'published',
    'template'        => 'directory-category',
    'sidebar_profile' => 'authority-standard',
    'updated_at'      => '2026-05-31',
]);

// -- Write constellation categories --
$constellationDescriptions = [
    'c1' => 'AI data, semantic AI, and machine-readable web - the infrastructure layer of the AI Digital Karma brand.',
    'c2' => 'AI-built websites, automated publishing systems, and the business of AI web infrastructure.',
    'c3' => 'Digital properties as investable assets - buying, ranking, and monetizing web real estate.',
    'c4' => 'SEO consulting, marketing education, and the personal brand hub. One Mouse. Maximum leverage.',
    'c5' => 'Healthcare AI, longevity, natural health, and the aging well vertical.',
    'c6' => 'Original art, digital galleries, print marketplaces, and the human creativity space.',
    'c7' => 'Niche professional verticals - medical marketing, spa business, local SEO, and specialty B2B.',
    'satellite' => 'Standalone domains outside the main constellations - held for future development or passive play.',
];

foreach ($constellationMeta as $id => $meta) {
    $slug = $meta['slug'];
    $path = 'portfolio/' . $slug;
    write_json($catOut . '/portfolio--' . $slug . '.json', [
        'type'            => 'dir_category',
        'slug'            => $slug,
        'path'            => $path,
        'parent_path'     => 'portfolio',
        'title'           => $meta['name'],
        'canonical_url'   => '/directory/' . $path . '/',
        'description'     => $constellationDescriptions[$id] ?? $meta['name'] . ' constellation.',
        'directory_type'  => 'portfolio',
        'status'          => 'published',
        'template'        => 'directory-category',
        'sidebar_profile' => 'authority-standard',
        'constellation_id' => $id,
        'updated_at'      => '2026-05-31',
    ]);
}

// -- Write listing JSONs --
echo "\nWriting listings...\n";
$written  = 0;
$skipped  = 0;

foreach ($registry as $domain => $reg) {
    if ($domain === '') {
        $skipped++;
        continue;
    }

    // Determine which constellation this domain belongs to
    if (isset($constellationDomains[$domain])) {
        $constEntry = $constellationDomains[$domain];
        $constSlug  = (string) ($constEntry['constellation_slug'] ?? 'satellites');
        $tier       = (string) ($constEntry['website_tier'] ?? $reg['website_tier'] ?? '');
        $priority   = (int) ($constEntry['priority'] ?? $reg['priority'] ?? 3);
        $domainNotes = (string) ($constEntry['notes'] ?? '');
        $crossovers  = [];
        // Collect crossover constellations from registry
        foreach ((array) ($reg['crossover_constellations'] ?? []) as $cid) {
            if (isset($constellationMeta[$cid])) {
                $crossovers[] = $constellationMeta[$cid]['name'];
            }
        }
    } else {
        $constSlug   = 'satellites';
        $tier        = (string) ($reg['website_tier'] ?? '');
        $priority    = (int) ($reg['priority'] ?? 5);
        $domainNotes = '';
        $crossovers  = [];
    }

    $domainSlug    = domain_to_slug($domain);
    $categoryPath  = 'portfolio/' . $constSlug;
    $listingPath   = $categoryPath . '/' . $domainSlug;
    $canonicalUrl  = '/directory/' . $listingPath . '/';

    // Build fields object
    $status      = (string) ($reg['status'] ?? 'domain-only');
    $monStatus   = (string) ($reg['monetization_status'] ?? 'none');
    $monPlan     = (string) ($reg['monetization_plan'] ?? '');
    $registrar   = (string) ($reg['registrar'] ?? '');
    $expiry      = (string) ($reg['expiry_date'] ?? '');
    $acqCost     = $reg['acquisition_cost'] !== null ? (string) $reg['acquisition_cost'] : '';
    $estValue    = $reg['estimated_value'] !== null ? (string) $reg['estimated_value'] : '';
    $revenue     = $reg['revenue_monthly'] !== null ? (string) $reg['revenue_monthly'] : '';
    $notes       = (string) ($reg['notes'] ?? '');
    // Merge domain-level notes from constellation file if registry notes empty
    $updateText  = $notes !== '' ? $notes : ($domainNotes !== '' ? $domainNotes : '');

    $liveUrl = '';
    if ($status === 'website') {
        $liveUrl = 'https://' . $domain;
    }

    $crossoverText = implode(', ', $crossovers);

    $fields = array_filter([
        'website'             => $liveUrl,
        'status'              => $status,
        'tier'                => $tier,
        'priority'            => $priority > 0 ? (string) $priority : '',
        'update'              => $updateText,
        'monetization_status' => $monStatus !== 'none' ? $monStatus : '',
        'expiry_date'         => $expiry,
        'registrar'           => $registrar,
        'crossover'           => $crossoverText,
        'acquisition_cost'    => $acqCost,
        'estimated_value'     => $estValue,
        'revenue_monthly'     => $revenue,
    ], fn($v) => $v !== '' && $v !== null);

    write_json($listOut . '/' . $domainSlug . '.json', [
        'type'            => 'dir_listing',
        'slug'            => $domainSlug,
        'path'            => $listingPath,
        'category_path'   => $categoryPath,
        'title'           => $domain,
        'canonical_url'   => $canonicalUrl,
        'directory_type'  => 'portfolio',
        'status'          => 'published',
        'template'        => 'directory-listing',
        'sidebar_profile' => 'authority-standard',
        'fields'          => $fields,
        'updated_at'      => '2026-05-31',
    ]);
    $written++;
}

echo "\nDone. Categories: " . (count($constellationMeta) + 1) . ", Listings: {$written}, Skipped: {$skipped}\n";
