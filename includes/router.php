<?php
declare(strict_types=1);

$request_uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri         = trim($request_uri ?? '/', '/');
$segments    = ($uri === '') ? [] : explode('/', $uri);

$page_type = isset($segments[0]) ? sanitize_slug($segments[0]) : '';
$slug      = isset($segments[1]) ? sanitize_slug($segments[1]) : '';
$subslug   = isset($segments[2]) ? sanitize_slug($segments[2]) : '';

switch ($page_type) {

    // ── Homepage ──────────────────────────────────────────────────────────────
    case '':
        $data          = get_page_data('home') ?? [];
        $breadcrumbs   = [];
        $page_title    = $data['seo']['meta_title']       ?? SITE_NAME;
        $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
        $preserve_meta_desc = true;
        $canonical_url = '/';
        $json_ld       = build_home_json_ld($page_title, $meta_desc);
        require TEMPLATES_PATH . 'home.php';
        break;

    // ── Channels ──────────────────────────────────────────────────────────────
    case 'channels':
        if ($slug === '') {
            $data          = ['channels' => get_all_channels()];
            $breadcrumbs   = build_breadcrumbs('channels');
            $page_title    = 'Channels | ' . SITE_NAME;
            $meta_desc     = 'Browse all session channels: Build Log, AI Search Lab, Website Rebuilds, Federation Architecture, and more.';
            $canonical_url = '/channels';
            $json_ld       = build_page_json_ld($page_title, $meta_desc, $canonical_url);
            require TEMPLATES_PATH . 'channels.php';
        } else {
            $data = get_channel($slug);
            if ($data === null) { _send_404(); break; }
            $sessions      = get_sessions_for_channel($slug);
            $breadcrumbs   = build_breadcrumbs('channel', $data);
            $page_title    = $data['seo']['meta_title'] ?? ($data['core']['title'] . ' | ' . SITE_NAME);
            $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
            $canonical_url = $data['seo']['canonical_url'] ?? '/channels/' . $slug;
            $json_ld       = build_channel_json_ld($data, $sessions, $page_title, $meta_desc, $canonical_url);
            require TEMPLATES_PATH . 'channel.php';
        }
        break;

    // ── Sessions ──────────────────────────────────────────────────────────────
    case 'sessions':
        if ($slug === '') {
            $data          = ['sessions' => get_all_sessions()];
            $breadcrumbs   = build_breadcrumbs('sessions');
            $page_title    = 'Session Archive | ' . SITE_NAME;
            $meta_desc     = 'Full archive of build-in-public working sessions. AI workflows, SEO experiments, website builds, and portfolio decisions in real time.';
            $canonical_url = '/sessions';
            $json_ld       = build_page_json_ld($page_title, $meta_desc, $canonical_url);
            require TEMPLATES_PATH . 'sessions.php';
        } else {
            $data = get_session($slug);
            if ($data === null) { _send_404(); break; }
            $channel       = !empty($data['core']['channel']) ? get_channel($data['core']['channel']) : null;
            $breadcrumbs   = build_breadcrumbs('session', $data);
            $page_title    = $data['seo']['meta_title'] ?? ($data['core']['title'] . ' | ' . SITE_NAME);
            $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
            $canonical_url = $data['seo']['canonical_url'] ?? '/sessions/' . $slug;
            $json_ld       = build_session_json_ld($data, $page_title, $meta_desc, $canonical_url);
            require TEMPLATES_PATH . 'session.php';
        }
        break;

    // ── About ─────────────────────────────────────────────────────────────────
    case 'about':
        $data          = get_page_data('about') ?? [];
        $breadcrumbs   = build_breadcrumbs('about');
        $page_title    = $data['seo']['meta_title'] ?? 'About | ' . SITE_NAME;
        $meta_desc     = $data['seo']['meta_description'] ?? SITE_META_DESC;
        $canonical_url = '/about';
        $json_ld       = build_page_json_ld($page_title, $meta_desc, $canonical_url);
        require TEMPLATES_PATH . 'about.php';
        break;

    // ── Admin ─────────────────────────────────────────────────────────────────
    case 'admin':
        switch ($slug) {

            case '':
            case 'dashboard':
                admin_require_auth();
                $page_type     = 'admin-dashboard';
                $page_title    = 'Dashboard | Admin | ' . SITE_NAME;
                $canonical_url = '/admin/dashboard';
                $log_404       = get_404_log();
                require TEMPLATES_PATH . 'admin-dashboard.php';
                break;

            case 'login':
                if (admin_is_authenticated()) { redirect('/admin/dashboard'); }
                $page_type     = 'admin-login';
                $page_title    = 'Login | Admin | ' . SITE_NAME;
                $canonical_url = '/admin/login';
                $login_error   = null;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                        $login_error = 'Invalid request. Please try again.';
                    } else {
                        $result = admin_attempt_login(
                            (string) ($_POST['username'] ?? ''),
                            (string) ($_POST['password'] ?? '')
                        );
                        if ($result['ok']) {
                            redirect('/admin/dashboard');
                        }
                        $login_error = $result['message'];
                    }
                }
                require TEMPLATES_PATH . 'admin-login.php';
                break;

            case 'logout':
                session_destroy();
                redirect('/admin/login');
                break;

            case 'content':
                admin_require_auth();
                $page_type = 'admin-content';
                if ($subslug !== '' && isset($segments[3])) {
                    $content_type_raw = sanitize_slug($subslug);
                    $content_slug_raw = sanitize_slug($segments[3]);
                    $content_entry    = admin_prepare_content_entry($content_type_raw, $content_slug_raw);
                    if ($content_entry === null) { _send_404(); break; }
                    $content_success = null;
                    $content_error   = null;
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                            $content_error = 'Invalid request token.';
                        } else {
                            $save_result = admin_save_content_entry(
                                $content_type_raw,
                                $content_slug_raw,
                                $_POST['field_paths']    ?? [],
                                $_POST['field_kinds']    ?? [],
                                $_POST['fields']         ?? [],
                                (string) ($_POST['commit_message'] ?? '')
                            );
                            if ($save_result['ok']) {
                                $content_success = $save_result['message'];
                                $content_entry   = admin_prepare_content_entry($content_type_raw, $content_slug_raw);
                            } else {
                                $content_error = $save_result['message'];
                            }
                        }
                    }
                    $page_title    = 'Edit ' . e($content_entry['title'] ?? '') . ' | Admin';
                    $canonical_url = '/admin/content/' . $content_type_raw . '/' . $content_slug_raw;
                    require TEMPLATES_PATH . 'admin-content-edit.php';
                } else {
                    $filter_type   = sanitize_slug((string) ($_GET['type'] ?? ''));
                    $content_items = admin_get_content_items($filter_type ?: null);
                    $content_types = admin_content_type_configs();
                    $page_title    = 'Content | Admin | ' . SITE_NAME;
                    $canonical_url = '/admin/content';
                    require TEMPLATES_PATH . 'admin-content.php';
                }
                break;

            case 'settings':
                admin_require_auth();
                $page_type       = 'admin-settings';
                $settings_data   = get_site_settings() ?? [];
                $settings_success = null;
                $settings_error   = null;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                        $settings_error = 'Invalid request token.';
                    } else {
                        $save_result = admin_save_site_settings($_POST);
                        if ($save_result['ok']) {
                            $settings_success = $save_result['message'];
                            $settings_data    = get_site_settings() ?? [];
                        } else {
                            $settings_error = $save_result['message'];
                        }
                    }
                }
                $page_title    = 'Settings | Admin | ' . SITE_NAME;
                $canonical_url = '/admin/settings';
                require TEMPLATES_PATH . 'admin-settings.php';
                break;

            case 'redirects':
                admin_require_auth();
                $page_type        = 'admin-redirects';
                $redirects_list   = get_redirects();
                $redirects_success = null;
                $redirects_error   = null;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                        $redirects_error = 'Invalid request token.';
                    } else {
                        $action = (string) ($_POST['action'] ?? '');
                        if ($action === 'add') {
                            $ok = admin_add_redirect(
                                (string) ($_POST['from'] ?? ''),
                                (string) ($_POST['to'] ?? ''),
                                (int) ($_POST['type'] ?? 301)
                            );
                            $redirects_success = $ok ? 'Redirect added.' : null;
                            $redirects_error   = $ok ? null : 'Could not add redirect.';
                        } elseif ($action === 'delete') {
                            $ok = admin_delete_redirect((string) ($_POST['redirect_id'] ?? ''));
                            $redirects_success = $ok ? 'Redirect deleted.' : null;
                            $redirects_error   = $ok ? null : 'Could not delete redirect.';
                        } elseif ($action === 'purge404') {
                            $min = max(1, (int) ($_POST['min_hits'] ?? 1));
                            purge_404_log_below($min);
                            $redirects_success = 'Low-hit 404 entries purged.';
                        } elseif ($action === 'remove404') {
                            remove_from_404_log((string) ($_POST['url'] ?? ''));
                            $redirects_success = 'Entry removed.';
                        }
                        $redirects_list = get_redirects();
                    }
                }
                $log_404       = get_404_log();
                $page_title    = 'Redirects | Admin | ' . SITE_NAME;
                $canonical_url = '/admin/redirects';
                require TEMPLATES_PATH . 'admin-redirects.php';
                break;

            case 'profile':
                admin_require_auth();
                $page_type       = 'admin-profile';
                $profile_success = null;
                $profile_error   = null;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                        $profile_error = 'Invalid request token.';
                    } else {
                        $current_user = admin_current_user();
                        $result = admin_update_password(
                            (string) ($current_user['username'] ?? ''),
                            (string) ($_POST['current_password'] ?? ''),
                            (string) ($_POST['new_password'] ?? '')
                        );
                        if ($result['ok']) {
                            $profile_success = $result['message'];
                        } else {
                            $profile_error = $result['message'];
                        }
                    }
                }
                $page_title    = 'Profile | Admin | ' . SITE_NAME;
                $canonical_url = '/admin/profile';
                require TEMPLATES_PATH . 'admin-profile.php';
                break;

            default:
                _send_404();
                break;
        }
        break;

    // ── 404 ───────────────────────────────────────────────────────────────────
    default:
        _send_404();
        break;
}

// ── 404 helper ────────────────────────────────────────────────────────────────
function _send_404(): never
{
    log_404($_SERVER['REQUEST_URI'] ?? '/');
    http_response_code(404);
    $page_type     = '404';
    $page_title    = 'Page Not Found | ' . SITE_NAME;
    $meta_desc     = SITE_META_DESC;
    $canonical_url = '';
    $breadcrumbs   = [];
    $json_ld       = null;
    require TEMPLATES_PATH . '404.php';
    exit;
}
