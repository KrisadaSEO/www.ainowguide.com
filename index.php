<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$resolved = site_resolve_request($requestUri);

if ($resolved['type'] === 'redirect') {
    header('Location: ' . $resolved['location'], true, $resolved['status_code']);
    exit;
}

if ($resolved['type'] === 'download_access') {
    site_stream_download_access($resolved);
}

// Admin backend
if ($resolved['type'] === 'admin') {
    $admin_slug  = $resolved['admin_slug'] ?? '';
    $admin_sub   = $resolved['admin_sub'] ?? '';
    $admin_child = $resolved['admin_child'] ?? '';
    $method      = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    header('Content-Type: text/html; charset=UTF-8');

    if ($admin_slug === 'login') {
        if (admin_is_authenticated()) {
            header('Location: /admin/');
            exit;
        }

        $login_error = null;
        if ($method === 'POST') {
            if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                $login_error = 'Invalid form submission. Please try again.';
            } else {
                $result = admin_attempt_login(
                    (string) ($_POST['username'] ?? ''),
                    (string) ($_POST['password'] ?? '')
                );

                if ($result['ok']) {
                    header('Location: /admin/');
                    exit;
                }

                $login_error = $result['message'];
            }
        }

        require site_root_path('templates/admin-login.php');
        exit;
    }

    if ($admin_slug === 'logout') {
        unset($_SESSION['admin_auth']);
        session_regenerate_id(true);
        header('Location: /admin/login');
        exit;
    }

    admin_require_auth();

    if ($admin_slug === '' || $admin_slug === 'admin') {
        $dash_counts = [
            'articles'      => count(glob(site_root_path('content/articles/*.json')) ?: []),
            'pages'         => count(glob(site_root_path('content/pages/*.json')) ?: []),
            'downloads'     => count(glob(site_root_path('content/downloads/*.json')) ?: []),
            'redirects'     => count(site_load_redirect_rules()),
            'dir_listings'  => count(glob(site_root_path('content/directory/listings/*.json')) ?: []),
        ];
        $log_entries = array_slice(site_get_404_log(), 0, 8);
        $admin_section = 'dashboard';
        require site_root_path('templates/admin-dashboard.php');
        exit;
    }

    if ($admin_slug === 'content') {
        $content_filter = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($_GET['type'] ?? '')));
        $content_filter = admin_get_content_type_config($content_filter) ? $content_filter : '';

        if ($admin_sub !== '' && $admin_child !== '') {
            $content_entry = admin_load_content_entry($admin_sub, $admin_child);
            if ($content_entry === null) {
                http_response_code(404);
                echo 'Content not found.';
                exit;
            }

            if ($method === 'POST') {
                if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                    admin_flash_set('content_error', 'Invalid form submission. Please try again.');
                } else {
                    $result = admin_save_content_entry($admin_sub, $admin_child, $_POST);
                    admin_flash_set($result['ok'] ? 'content_success' : 'content_error', $result['message']);
                }

                header('Location: /admin/content/' . $admin_sub . '/' . $admin_child);
                exit;
            }

            $content_success = admin_flash_consume('content_success');
            $content_error   = admin_flash_consume('content_error');
            $admin_section   = 'content';
            require site_root_path('templates/admin-content-edit.php');
            exit;
        }

        $content_items   = admin_get_content_items($content_filter !== '' ? $content_filter : null);
        $content_success = admin_flash_consume('content_success');
        $content_error   = admin_flash_consume('content_error');
        $admin_section   = 'content';
        require site_root_path('templates/admin-content.php');
        exit;
    }

    if ($admin_slug === 'downloads') {
        if ($admin_sub === 'new') {
            if ($method === 'POST') {
                if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                    admin_flash_set('download_error', 'Invalid form submission. Please try again.');
                    header('Location: /admin/downloads/new');
                    exit;
                }

                $result = admin_save_download_entry(null, $_POST, $_FILES);
                admin_flash_set($result['ok'] ? 'download_success' : 'download_error', $result['message']);
                header('Location: ' . ($result['ok'] ? '/admin/downloads/' . ($result['slug'] ?? '') : '/admin/downloads/new'));
                exit;
            }

            $download_entry = [
                'slug' => '',
                'raw' => admin_download_defaults(),
                'source_info' => ['exists' => false, 'error' => 'No source configured yet.'],
                'stats' => ['claims' => 0, 'downloads' => 0, 'last_claimed_at' => '', 'last_downloaded_at' => ''],
                'recent_claims' => [],
            ];
            $download_success = admin_flash_consume('download_success');
            $download_error = admin_flash_consume('download_error');
            $admin_section = 'downloads';
            $is_new_download = true;
            require site_root_path('templates/admin-download-edit.php');
            exit;
        }

        if ($admin_sub !== '') {
            $download_entry = admin_load_download_entry($admin_sub);
            if ($download_entry === null) {
                http_response_code(404);
                echo 'Download not found.';
                exit;
            }

            if ($method === 'POST') {
                if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                    admin_flash_set('download_error', 'Invalid form submission. Please try again.');
                } elseif (($_POST['action'] ?? 'save') === 'delete') {
                    $result = admin_delete_download_entry($admin_sub);
                    admin_flash_set($result['ok'] ? 'download_success' : 'download_error', $result['message']);
                    header('Location: /admin/downloads');
                    exit;
                } else {
                    $result = admin_save_download_entry($admin_sub, $_POST, $_FILES);
                    admin_flash_set($result['ok'] ? 'download_success' : 'download_error', $result['message']);
                }

                header('Location: /admin/downloads/' . $admin_sub);
                exit;
            }

            $download_success = admin_flash_consume('download_success');
            $download_error = admin_flash_consume('download_error');
            $admin_section = 'downloads';
            $is_new_download = false;
            require site_root_path('templates/admin-download-edit.php');
            exit;
        }

        $download_items = admin_list_download_items();
        $download_success = admin_flash_consume('download_success');
        $download_error = admin_flash_consume('download_error');
        $admin_section = 'downloads';
        require site_root_path('templates/admin-downloads.php');
        exit;
    }

    if ($admin_slug === 'redirects') {
        if ($method === 'POST') {
            if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                admin_flash_set('redirect_error', 'Invalid form submission. Please try again.');
                header('Location: /admin/redirects');
                exit;
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'add-redirect') {
                $from = trim((string) ($_POST['from'] ?? ''));
                $to   = trim((string) ($_POST['to'] ?? ''));
                $type = (int) ($_POST['type'] ?? 301);

                if ($from !== '' && $to !== '') {
                    $rules   = site_load_redirect_rules();
                    $rules[] = [
                        'from' => site_normalize_path($from),
                        'to' => $to,
                        'type' => $type === 302 ? 302 : 301,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    site_save_redirect_rules($rules);
                    site_remove_404_log_entry($from);
                    admin_flash_set('redirect_success', 'Redirect saved.');
                }
            } elseif ($action === 'delete-redirect') {
                $idx   = (int) ($_POST['index'] ?? -1);
                $rules = site_load_redirect_rules();
                if (isset($rules[$idx])) {
                    unset($rules[$idx]);
                    site_save_redirect_rules(array_values($rules));
                    admin_flash_set('redirect_success', 'Redirect deleted.');
                }
            } elseif ($action === 'dismiss-404') {
                $url = (string) ($_POST['url'] ?? '');
                if ($url !== '') {
                    site_remove_404_log_entry($url);
                    admin_flash_set('redirect_success', '404 entry dismissed.');
                }
            } elseif ($action === 'purge-all-errors') {
                site_purge_404_log();
                admin_flash_set('redirect_success', '404 log cleared.');
            } elseif ($action === 'purge-errors-below') {
                $min_hits = max(1, (int) ($_POST['min_hits'] ?? 1));
                site_purge_404_log_below($min_hits);
                admin_flash_set('redirect_success', 'Low-hit 404 entries removed.');
            }

            header('Location: /admin/redirects');
            exit;
        }

        $redirects        = site_load_redirect_rules();
        $log_entries      = site_get_404_log();
        $redirect_success = admin_flash_consume('redirect_success');
        $redirect_error   = admin_flash_consume('redirect_error');
        $admin_section    = 'redirects';
        require site_root_path('templates/admin-redirects.php');
        exit;
    }

    if ($admin_slug === 'profile') {
        $current_user    = admin_current_user();
        $profile_success = null;
        $profile_error   = null;

        if ($method === 'POST') {
            if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                $profile_error = 'Invalid form submission. Please try again.';
            } else {
                $result = admin_update_password(
                    (string) ($current_user['username'] ?? ''),
                    (string) ($_POST['current_password'] ?? ''),
                    (string) ($_POST['new_password'] ?? '')
                );
                $result['ok'] ? $profile_success = $result['message'] : $profile_error = $result['message'];
            }
        }

        $admin_section = 'profile';
        require site_root_path('templates/admin-profile.php');
        exit;
    }

    if ($admin_slug === 'push') {
        if ($method === 'POST') {
            if (!admin_verify_csrf($_POST['_csrf'] ?? null)) {
                admin_flash_set('push_error', 'Invalid form submission. Please try again.');
            } else {
                $msg    = trim((string) ($_POST['commit_message'] ?? '')) ?: 'admin: live edit';
                $result = admin_git_push_all($msg);
                if ($result['status'] === 'success') {
                    admin_flash_set('push_success', 'Pushed to GitHub successfully.');
                } elseif ($result['status'] === 'nothing') {
                    admin_flash_set('push_success', 'Nothing to commit ... no changes since last push.');
                } else {
                    admin_flash_set('push_error', 'Push failed: ' . $result['output']);
                }
            }
            header('Location: /admin/push');
            exit;
        }

        $push_success  = admin_flash_consume('push_success');
        $push_error    = admin_flash_consume('push_error');
        $admin_section = 'push';
        require site_root_path('templates/admin-push.php');
        exit;
    }

    http_response_code(404);
    echo 'Admin section not found.';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $resolved['type'] === 'redirect_admin') {
    site_process_redirect_admin_request();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $resolved['path'] === '/contact/') {
    site_process_contact_form();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $resolved['path'] === '/subscribe/') {
    site_process_subscribe_form();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $resolved['type'] === 'download') {
    site_process_download_claim($resolved);
}

if ((int) ($resolved['status_code'] ?? 200) === 404 && in_array(($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['GET', 'HEAD'], true)) {
    site_log_404($requestUri);
}

$pageViews = site_track_page_view($resolved);
$view = site_prepare_view($resolved);
$view['page_views'] = $pageViews;
$view['page_views_display'] = site_format_view_count($pageViews);

http_response_code($view['status_code']);
header('Content-Type: text/html; charset=UTF-8');

require site_root_path('templates/layouts/default.php');
