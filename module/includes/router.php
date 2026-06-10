<?php
declare(strict_types=1);

// ─── Parse the request URI ────────────────────────────────────────────────────
$request_uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri          = trim($request_uri ?? '/', '/');
$segments     = ($uri === '') ? [] : explode('/', $uri);

// Sanitize each segment against path traversal
$page_type = isset($segments[0]) ? sanitize_slug($segments[0]) : '';
$slug      = isset($segments[1]) ? sanitize_slug($segments[1]) : '';
$subslug   = isset($segments[2]) ? sanitize_slug($segments[2]) : '';
$cityslug  = isset($segments[3]) ? sanitize_slug($segments[3]) : '';

// ─── Sidebar hydration ────────────────────────────────────────────────────────
// After get_sidebar_cards_for_page() loads cards, some may declare a
// dynamic_source. This function overwrites those cards' data[] with live content
// so the renderer always has up-to-date information without editing JSON files.

function hydrate_sidebar_cards(array $cards): array
{
    foreach ($cards as &$card) {
        $source = $card['dynamic_source'] ?? null;
        if ($source === null) continue;

        if ($source === 'get_featured_case_studies') {
            $limit      = (int) ($card['display']['max_items'] ?? 3);
            $featured   = get_featured_case_studies($limit);
            $card['case_studies'] = array_map(function (array $cs) {
                return [
                    'case_study_slug' => $cs['core']['slug']   ?? '',
                    'display_name'    => $cs['core']['name']   ?? '',
                    'one_liner'       => $cs['content']['headline'] ?? '',
                ];
            }, $featured);
        }
    }
    unset($card);
    return $cards;
}

// ─── Route dispatch ───────────────────────────────────────────────────────────

switch ($page_type) {

    // ── Homepage ──────────────────────────────────────────────────────────────
    case '':
        $data          = [];
        $breadcrumbs   = [];
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('home'));
        $page_title    = SITE_NAME . ' ... ' . 'SEO Case Studies, Experiments & Concepts';
        $meta_desc     = SITE_META_DESC;
        $canonical_url = '/';
        require TEMPLATES_PATH . 'home.php';
        break;

    // ── Case Study ───────────────────────────────────────────────────────────
    case 'case-study':
        $data = get_case_study($slug);
        if ($data === null) { _send_404(); break; }
        $breadcrumbs   = build_breadcrumbs('case-study', $data);
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('case-study', $data['sidebar_overrides'] ?? null));
        $page_title    = ($data['seo']['meta_title'] ?? $data['core']['name'] . ' | ' . SITE_NAME);
        $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
        $canonical_url = $data['seo']['canonical_url'] ?? '/case-study/' . $slug;
        require TEMPLATES_PATH . 'case-study.php';
        break;

    // ── Concept ──────────────────────────────────────────────────────────────
    case 'concept':
        $data = get_concept($slug);
        if ($data === null) { _send_404(); break; }
        $breadcrumbs   = build_breadcrumbs('concept', $data);
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('concept', $data['sidebar_overrides'] ?? null));
        $page_title    = ($data['seo']['meta_title'] ?? $data['core']['name'] . ' | ' . SITE_NAME);
        $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
        $canonical_url = $data['seo']['canonical_url'] ?? '/concept/' . $slug;
        require TEMPLATES_PATH . 'concept.php';
        break;


    // ── SEO Proof ────────────────────────────────────────────────────────────
    case 'seo-proof':
        if ($slug === '') {
            $all_proof_entries = get_all_proof_entries();
            usort($all_proof_entries, fn($a, $b) =>
                strcmp($b['meta']['created_at'] ?? '', $a['meta']['created_at'] ?? '')
            );
            $data          = ['proof_entries' => $all_proof_entries];
            $breadcrumbs   = [['label' => 'Home', 'url' => '/'], ['label' => 'Proof Entries', 'url' => null]];
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('proof-entry'));
            $page_title    = 'Proof Entries ... Evidence & Validation | ' . SITE_NAME;
            $meta_desc     = 'Browse documented proof entries that validate SEO strategies with real evidence and measurable outcomes.';
            $canonical_url = '/seo-proof';
            require TEMPLATES_PATH . 'proof-entries.php';
        } else {
            $data = get_proof_entry($slug);
            if ($data === null) { _send_404(); break; }
            $breadcrumbs   = build_breadcrumbs('proof-entry', $data);
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('proof-entry', $data['sidebar_overrides'] ?? null));
            $page_title    = ($data['seo']['meta_title'] ?? get_item_title($data) . ' | ' . SITE_NAME);
            $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
            $canonical_url = $data['seo']['canonical_url'] ?? '/seo-proof/' . $slug;
            require TEMPLATES_PATH . 'proof-entry.php';
        }
        break;

    // ── About (index + individual team member pages) ──────────────────────────
    case 'about':
        if ($slug === '') {
            $members       = get_all_team_members();
            $data          = ['members' => $members];
            $breadcrumbs   = build_breadcrumbs('about');
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page(''));
            $page_title    = 'About ... Real SEO Life | ' . SITE_NAME;
            $meta_desc     = 'Meet the team behind RealSEOLife.com ... Krisada Eaton (Founder) and Kodi (AI Co-Architect).';
            $canonical_url = '/about';
            require TEMPLATES_PATH . 'about.php';
        } else {
            $data = get_team_member($slug);
            if ($data === null) { _send_404(); break; }
            $breadcrumbs   = build_breadcrumbs('team-member', $data);
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page(''));
            $page_title    = ($data['seo']['meta_title'] ?? $data['core']['name'] . ' | ' . SITE_NAME);
            $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
            $canonical_url = $data['seo']['canonical_url'] ?? '/about/' . $slug;
            require TEMPLATES_PATH . 'team-member.php';
        }
        break;

    // ── Contact ──────────────────────────────────────────────────────────────
    case 'contact':
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            process_contact_form_submission();
        }

        $page = get_page_data('contact') ?? [];
        $data = [
            'page'        => $page,
            'form_config' => get_contact_form_config() ?? [],
            'form_state'  => get_contact_form_state(),
        ];
        $breadcrumbs   = build_breadcrumbs('contact');
        $sidebar_cards = [];
        $page_title    = $page['seo']['meta_title'] ?? 'Contact | ' . SITE_NAME;
        $meta_desc     = $page['seo']['meta_description'] ?? SITE_META_DESC;
        $canonical_url = '/contact';
        require TEMPLATES_PATH . 'contact.php';
        break;

    // ── Stat ─────────────────────────────────────────────────────────────────
    case 'stat':
        $data = get_stat($slug);
        if ($data === null) { _send_404(); break; }
        $breadcrumbs   = build_breadcrumbs('stat', $data);
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('stat', $data['sidebar_overrides'] ?? null));
        $page_title    = ($data['seo']['meta_title'] ?? $data['core']['title'] . ' | ' . SITE_NAME);
        $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
        $canonical_url = $data['seo']['canonical_url'] ?? '/stat/' . $slug;
        require TEMPLATES_PATH . 'stat.php';
        break;

    // ── Stats index ───────────────────────────────────────────────────────────
    case 'stats':
        $all_stats = get_all_stats();
        usort($all_stats, fn($a, $b) =>
            strcmp($b['meta']['created_at'] ?? '', $a['meta']['created_at'] ?? '')
        );
        $stats_category = get_category_by_slug('interesting-stats');
        $data          = ['stats' => $all_stats, 'category' => $stats_category];
        $breadcrumbs   = [['label' => 'Home', 'url' => '/'], ['label' => 'Interesting Stats', 'url' => null]];
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('stat'));
        $page_title    = 'Interesting Stats & Infographics | ' . SITE_NAME;
        $meta_desc     = 'Visual statistics and data from SEO research, AI adoption trends, and content performance benchmarks.';
        $canonical_url = '/stats';
        require TEMPLATES_PATH . 'stats.php';
        break;

    // ── Article ──────────────────────────────────────────────────────────────
    case 'article':
        $data = get_article($slug);
        if ($data === null) { _send_404(); break; }
        $breadcrumbs   = build_breadcrumbs('article', $data);
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('article', $data['sidebar_overrides'] ?? null));
        $page_title    = ($data['seo']['meta_title'] ?? $data['core']['title'] . ' | ' . SITE_NAME);
        $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
        $canonical_url = $data['seo']['canonical_url'] ?? '/article/' . $slug;
        require TEMPLATES_PATH . 'article.php';
        break;

    // ── Category archive ──────────────────────────────────────────────────────
    case 'category':
        $data = get_category_by_slug($slug);
        if ($data === null) { _send_404(); break; }
        $data['case_studies'] = get_case_studies_by_category($slug);
        $data['articles']     = get_articles_by_category($slug);
        $data['stats']        = get_stats_by_category($slug);
        $breadcrumbs          = build_breadcrumbs('category', $data);
        $sidebar_cards        = hydrate_sidebar_cards(get_sidebar_cards_for_page('category'));
        $page_title           = ($data['name'] ?? 'Category') . ' | ' . SITE_NAME;
        $meta_desc            = $data['description'] ?? SITE_META_DESC;
        $canonical_url        = '/category/' . $slug;
        require TEMPLATES_PATH . 'category.php';
        break;

    // ── Subcategory archive ───────────────────────────────────────────────────
    case 'subcategory':
        $data = get_subcategory_by_slug($slug);
        if ($data === null) { _send_404(); break; }
        $data['case_studies'] = get_case_studies_by_subcategory($slug);
        $data['articles']     = get_articles_by_subcategory($slug);
        $data['stats']        = get_stats_by_subcategory($slug);
        $breadcrumbs          = build_breadcrumbs('subcategory', $data);
        $sidebar_cards        = hydrate_sidebar_cards(get_sidebar_cards_for_page('subcategory'));
        $page_title           = ($data['name'] ?? 'Subcategory') . ' | ' . SITE_NAME;
        $meta_desc            = $data['description'] ?? SITE_META_DESC;
        $canonical_url        = '/subcategory/' . $slug;
        require TEMPLATES_PATH . 'subcategory.php';
        break;

    // ── Tag archive ───────────────────────────────────────────────────────────
    case 'tag':
        $data = get_tag_by_slug($slug);
        if ($data === null) { _send_404(); break; }
        $data['case_studies']    = get_case_studies_by_tag($slug);
        $data['articles']        = get_articles_by_tag($slug);
        $data['concepts']        = get_concepts_by_tag($slug);
        $data['experiments']     = get_experiments_by_tag($slug);
        $data['proof_entries']   = get_proof_entries_by_tag($slug);
        $data['glossary_terms']  = get_glossary_terms_by_tag($slug);
        $data['stats']           = get_stats_by_tag($slug);
        $breadcrumbs          = build_breadcrumbs('tag', $data);
        $sidebar_cards        = hydrate_sidebar_cards(get_sidebar_cards_for_page('tag'));
        $page_title           = ($data['name'] ?? 'Tag') . ' ... Content Lab | ' . SITE_NAME;
        $meta_desc            = $data['description'] ?? SITE_META_DESC;
        $canonical_url        = '/tag/' . $slug;
        require TEMPLATES_PATH . 'tag.php';
        break;

    // ── Article index ─────────────────────────────────────────────────────────
    case 'articles':
        $all_articles  = get_all_articles();
        usort($all_articles, fn($a, $b) =>
            strcmp($b['meta']['created_at'] ?? '', $a['meta']['created_at'] ?? '')
        );
        $data          = ['articles' => $all_articles];
        $breadcrumbs   = [['label' => 'Home', 'url' => '/'], ['label' => 'Articles', 'url' => null]];
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('article'));
        $page_title    = 'Articles ... SEO Research & Analysis | ' . SITE_NAME;
        $meta_desc     = 'Guides and analysis on SEO experiments, case studies, and content strategy.';
        $canonical_url = '/articles';
        require TEMPLATES_PATH . 'articles.php';
        break;

    // ── Concept index ─────────────────────────────────────────────────────────
    case 'concepts':
        $all_concepts = get_all_concepts();
        $data         = ['concepts' => $all_concepts];
        $breadcrumbs  = [['label' => 'Home', 'url' => '/'], ['label' => 'Concepts', 'url' => null]];
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('concept'));
        $page_title    = 'SEO Concepts ... Core Ideas & Frameworks | ' . SITE_NAME;
        $meta_desc     = 'Explore foundational SEO concepts and frameworks documented through research and real-world testing.';
        $canonical_url = '/concepts';
        require TEMPLATES_PATH . 'concepts.php';
        break;

    // ── Tags index ────────────────────────────────────────────────────────────
    case 'tags':
        $all_tags  = get_tags();
        $data      = ['tags' => $all_tags];
        $breadcrumbs   = [['label' => 'Home', 'url' => '/'], ['label' => 'Tags', 'url' => null]];
        $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page(''));
        $page_title    = 'Browse by Tag | ' . SITE_NAME;
        $meta_desc     = 'Browse case studies, articles, and concepts by tag.';
        $canonical_url = '/tags';
        require TEMPLATES_PATH . 'tags.php';
        break;

    // ── Case Study directory index ────────────────────────────────────────────
    case 'case-studies':
        if ($slug === '' && $subslug === '') {
            $all_case_studies = sort_case_studies(get_all_case_studies());
            $data             = [
                'case_studies' => $all_case_studies,
                'categories'   => get_case_study_categories(),
                'tags'         => array_values(array_filter(
                    get_case_study_tags(),
                    fn($tag) => !empty($tag['seo_indexable'])
                )),
            ];
            $breadcrumbs      = [['label' => 'Home', 'url' => '/'], ['label' => 'Case Studies', 'url' => null]];
            $sidebar_cards    = hydrate_sidebar_cards(get_sidebar_cards_for_page('case-study'));
            $page_title       = 'Case Studies ... SEO Proof Library | ' . SITE_NAME;
            $meta_desc        = 'Browse proof-driven case studies documenting SEO strategy, AI visibility, and measurable digital asset outcomes.';
            $canonical_url    = '/case-studies';
            require TEMPLATES_PATH . 'case-studies.php';
            break;
        }

        if ($slug === 'category' && $subslug !== '') {
            $archive = get_case_study_category_by_slug($subslug);
            if ($archive === null) { _send_404(); break; }

            $data          = [
                'archive'      => $archive,
                'archive_type' => 'category',
                'case_studies' => sort_case_studies(get_case_studies_by_case_study_category($subslug)),
            ];
            $breadcrumbs   = build_breadcrumbs('case-study-category', $archive);
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('case-study'));
            $page_title    = ($archive['seo']['title'] ?? $archive['name'] ?? 'Case Study Category') . ' | ' . SITE_NAME;
            $meta_desc     = $archive['seo']['meta_description'] ?? $archive['description'] ?? SITE_META_DESC;
            $canonical_url = $archive['seo']['canonical'] ?? '/case-studies/category/' . $subslug;
            require TEMPLATES_PATH . 'case-study-taxonomy.php';
            break;
        }

        if ($slug === 'tag' && $subslug !== '') {
            $archive = get_case_study_tag_by_slug($subslug);
            if ($archive === null) { _send_404(); break; }

            $data          = [
                'archive'      => $archive,
                'archive_type' => 'tag',
                'case_studies' => sort_case_studies(get_case_studies_by_case_study_tag($subslug)),
            ];
            $breadcrumbs   = build_breadcrumbs('case-study-tag', $archive);
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('case-study'));
            $page_title    = ($archive['name'] ?? 'Case Study Tag') . ' Case Studies | ' . SITE_NAME;
            $meta_desc     = $archive['description'] ?? SITE_META_DESC;
            $canonical_url = '/case-studies/tag/' . $subslug;
            require TEMPLATES_PATH . 'case-study-taxonomy.php';
            break;
        }

        _send_404();
        break;


    // ── Glossary ──────────────────────────────────────────────────────────────
    case 'glossary':
        if ($slug === '') {
            // Index: list all terms A-Z
            $all_terms     = get_all_glossary_terms();
            $data          = ['terms' => $all_terms];
            $breadcrumbs   = build_breadcrumbs('glossary');
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('concept'));
            $page_title    = 'SEO Glossary ... Terms & Definitions | ' . SITE_NAME;
            $meta_desc     = 'A reference glossary of SEO and digital asset terms used across case studies, experiments, and concepts on RealSEOLife.com.';
            $canonical_url = '/glossary';
            require TEMPLATES_PATH . 'glossary.php';
        } else {
            // Individual term
            $data = get_glossary_term($slug);
            if ($data === null) { _send_404(); break; }
            $breadcrumbs   = build_breadcrumbs('glossary-term', $data);
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('concept'));
            $page_title    = ($data['seo']['meta_title'] ?? $data['core']['term'] . ' ... SEO Glossary | ' . SITE_NAME);
            $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
            $canonical_url = $data['seo']['canonical_url'] ?? '/glossary/' . $slug;
            require TEMPLATES_PATH . 'glossary-term.php';
        }
        break;

    // ── Best SEO (Experiments archive + individual entries + landing pages) ──────
    case 'best-seo':
        if ($slug === '') {
            // Experiments archive index
            $all_experiments = get_all_experiments();
            usort($all_experiments, fn($a, $b) =>
                strcmp($b['meta']['created_at'] ?? '', $a['meta']['created_at'] ?? '')
            );
            $data          = ['experiments' => $all_experiments];
            $breadcrumbs   = [['label' => 'Home', 'url' => '/'], ['label' => 'Experiments', 'url' => null]];
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('experiment'));
            $page_title    = 'Experiments ... SEO Testing Lab | ' . SITE_NAME;
            $meta_desc     = 'Browse documented SEO experiments with hypotheses, methodology, and verified results.';
            $canonical_url = '/best-seo';
            require TEMPLATES_PATH . 'experiments.php';
        } elseif ($slug === 'in-florida-near-me' && $subslug === '') {
            // Florida landing page
            $json_file = PAGES_PATH . 'best-seo-in-florida-near-me.json';
            if (!is_file($json_file)) { _send_404(); break; }
            $data          = json_decode(file_get_contents($json_file), true);
            $breadcrumbs   = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Best Local SEO in Florida', 'url' => null],
            ];
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('best-seo'));
            $page_title    = $data['seo']['meta_title'] ?? 'Best Local SEO in Florida Near Me | ' . SITE_NAME;
            $meta_desc     = $data['seo']['meta_description'] ?? '';
            $canonical_url = $data['seo']['canonical_url'] ?? '/best-seo/in-florida-near-me';
            require TEMPLATES_PATH . 'best-seo-florida.php';
        } elseif ($slug === 'in-florida-near-me' && $subslug !== '' && $cityslug !== '') {
            // Florida city landing page  e.g. /best-seo/in-florida-near-me/orlando/kissimmee
            $json_file = PAGES_PATH . 'best-seo-in-florida-near-me.json';
            if (!is_file($json_file)) { _send_404(); break; }
            $florida_data = json_decode(file_get_contents($json_file), true);

            // Find the parent metro
            $metro = null;
            foreach ($florida_data['metros'] ?? [] as $m) {
                if (($m['slug'] ?? '') === $subslug) {
                    $metro = $m;
                    break;
                }
            }
            if ($metro === null) { _send_404(); break; }

            // Look up the city object by JSON slug
            $city = null;
            foreach ($metro['cities'] ?? [] as $c) {
                if (($c['slug'] ?? '') === $cityslug) {
                    $city = $c;
                    break;
                }
            }
            if ($city === null) { _send_404(); break; }

            // Find any city spotlight for this city
            $spotlight = null;
            foreach ($florida_data['city_spotlight'] ?? [] as $s) {
                if (strtolower($s['city'] ?? '') === strtolower($city['name'])) {
                    $spotlight = $s;
                    break;
                }
            }

            $data          = [
                'city'       => $city,
                'metro'      => $metro,
                'spotlight'  => $spotlight,
                'cta'        => $florida_data['cta'] ?? [],
            ];
            $breadcrumbs   = [
                ['label' => 'Home',                              'url' => '/'],
                ['label' => 'Best Local SEO in Florida',         'url' => '/best-seo/in-florida-near-me'],
                ['label' => $metro['name'],                      'url' => '/best-seo/in-florida-near-me/' . $subslug],
                ['label' => 'Best SEO in ' . $city['name'],     'url' => null],
            ];
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('best-seo'));
            $page_title    = $city['meta_title']       ?? 'Best Local SEO in ' . $city['name'] . ', FL | ' . SITE_NAME;
            $meta_desc     = $city['meta_description'] ?? 'Find the best Local SEO professionals in ' . $city['name'] . ', Florida.';
            $canonical_url = '/best-seo/in-florida-near-me/' . $subslug . '/' . $cityslug;
            require TEMPLATES_PATH . 'best-seo-florida-city.php';
        } elseif ($slug === 'in-florida-near-me' && $subslug !== '') {
            // Florida metro sub-page
            $json_file = PAGES_PATH . 'best-seo-in-florida-near-me.json';
            if (!is_file($json_file)) { _send_404(); break; }
            $florida_data = json_decode(file_get_contents($json_file), true);

            // Find the matching metro by slug
            $metro = null;
            foreach ($florida_data['metros'] ?? [] as $m) {
                if (($m['slug'] ?? '') === $subslug) {
                    $metro = $m;
                    break;
                }
            }
            if ($metro === null) { _send_404(); break; }

            // Filter city spotlights that belong to this metro's towns
            $metro_towns    = array_map('strtolower', $metro['towns']);
            $all_spotlights = $florida_data['city_spotlight'] ?? [];
            $spotlights     = array_values(array_filter(
                $all_spotlights,
                fn($s) => in_array(strtolower($s['city'] ?? ''), $metro_towns, true)
            ));

            $data          = [
                'metro'      => $metro,
                'spotlights' => $spotlights,
                'cta'        => $florida_data['cta'] ?? [],
            ];
            $breadcrumbs   = [
                ['label' => 'Home',                       'url' => '/'],
                ['label' => 'Best Local SEO in Florida',  'url' => '/best-seo/in-florida-near-me'],
                ['label' => $metro['name'],                'url' => null],
            ];
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('best-seo'));
            $page_title    = 'Best Local SEO in ' . $metro['hub'] . ' | Florida Near Me | ' . SITE_NAME;
            $meta_desc     = $metro['meta_description'] ?? 'Find the best Local SEO professionals in ' . $metro['hub'] . ' and surrounding cities.';
            $canonical_url = '/best-seo/in-florida-near-me/' . $subslug;
            require TEMPLATES_PATH . 'best-seo-florida-metro.php';
        } else {
            // Individual experiment
            $data = get_experiment($slug);
            if ($data === null) { _send_404(); break; }
            $breadcrumbs   = build_breadcrumbs('experiment', $data);
            $sidebar_cards = hydrate_sidebar_cards(get_sidebar_cards_for_page('experiment', $data['sidebar_overrides'] ?? null));
            $page_title    = ($data['seo']['meta_title'] ?? get_item_title($data) . ' | ' . SITE_NAME);
            $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
            $canonical_url = $data['seo']['canonical_url'] ?? '/best-seo/' . $slug;
            require TEMPLATES_PATH . 'experiment.php';
        }
        break;

    // ── Admin: Redirect Manager ───────────────────────────────────────────────
    case 'admin':
        if (!verify_admin_token()) {
            http_response_code(403);
            echo 'Access denied.';
            break;
        }
        if ($slug === 'redirects') {
            // Handle POST actions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? '';
                if ($action === 'add-redirect') {
                    $from = trim($_POST['from'] ?? '');
                    $to   = trim($_POST['to'] ?? '');
                    $type = (int) ($_POST['type'] ?? 301);
                    $rules = get_redirects();
                    $existing_froms = array_column($rules, 'from');
                    if ($from !== '' && $to !== '' && !in_array($from, $existing_froms, true)) {
                        $rules[] = [
                            'from'       => $from,
                            'to'         => $to,
                            'type'       => $type === 302 ? 302 : 301,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        save_redirects($rules);
                        remove_from_404_log($from);
                    }
                } elseif ($action === 'delete-redirect') {
                    $idx   = (int) ($_POST['index'] ?? -1);
                    $rules = get_redirects();
                    if (isset($rules[$idx])) {
                        unset($rules[$idx]);
                        save_redirects($rules);
                    }
                } elseif ($action === 'dismiss-404') {
                    $url = $_POST['url'] ?? '';
                    if ($url !== '') {
                        remove_from_404_log($url);
                    }
                } elseif ($action === 'purge-all-errors') {
                    purge_404_log();
                } elseif ($action === 'purge-errors-below') {
                    $min_hits = max(1, (int) ($_POST['min_hits'] ?? 1));
                    purge_404_log_below($min_hits);
                }
                header('Location: /admin/redirects?token=' . urlencode($_GET['token'] ?? $_POST['token'] ?? ''));
                exit;
            }
            $redirects     = get_redirects();
            $log_entries   = get_404_log();
            $page_title    = 'Redirect Manager | ' . SITE_NAME;
            $meta_desc     = '';
            $canonical_url = '';
            $extra_css     = 'admin';
            require TEMPLATES_PATH . 'admin-redirects.php';
        } else {
            _send_404();
        }
        break;

    // ── 404 catch-all ─────────────────────────────────────────────────────────
    default:
        _send_404();
        break;
}

// ─── Internal helpers ─────────────────────────────────────────────────────────

function _send_404(): void
{
    // Log the 404 for the redirect manager
    log_404($_SERVER['REQUEST_URI'] ?? '/');

    http_response_code(404);
    global $breadcrumbs, $sidebar_cards, $data, $page_title, $meta_desc, $canonical_url;
    $data          = [];
    $breadcrumbs   = [];
    $sidebar_cards = [];
    $page_title    = 'Page Not Found | ' . SITE_NAME;
    $meta_desc     = '';
    $canonical_url = '';
    require TEMPLATES_PATH . '404.php';
}
