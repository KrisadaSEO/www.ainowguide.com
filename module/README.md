# Redirect Admin Module

This package contains the exact redirect admin implementation copied from `www.realseolife.com`.

## Included files

- `module/includes/functions.php`
- `module/includes/router.php`
- `module/includes/config.php`
- `module/templates/admin-redirects.php`
- `module/assets/css/admin.css`
- `module/index.php`
- `module/data/redirects.json`
- `module/data/404-log.json`

## Important

This is not a plug-and-play Composer package. It is a copyable module for repos that already have a similar PHP app structure.

You will usually do one of these:

1. Copy the matching code blocks into the target repo's existing files.
2. Compare this package against the target repo and merge the redirect admin pieces in.

## What must exist in the target repo

- `DATA_PATH`, `TEMPLATES_PATH`, and `PARTIALS_PATH` constants
- an `index.php` or front controller where `check_redirects(...)` can run before the router
- a router with an `/admin/redirects` route
- a 404 handler where `log_404(...)` can run
- admin CSS loading for `admin.css`

## Required setup in each repo

1. Generate a new `ADMIN_TOKEN` for that repo.
2. Put the token in the environment instead of hardcoding a shared fallback.
3. Create empty files:
   - `data/redirects.json`
   - `data/404-log.json`
4. Make sure PHP can write to those JSON files.

## Minimum integration points

- `check_redirects(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');`
- `verify_admin_token()`
- `log_404($_SERVER['REQUEST_URI'] ?? '/');`
- `/admin/redirects?token=YOUR_TOKEN`

## Recommended workflow

- open the target repo
- copy the redirect-related functions from `module/includes/functions.php`
- merge the admin route and 404 logging from `module/includes/router.php`
- add the pre-router redirect check from `module/index.php`
- copy the admin template and CSS
- add the two JSON files
- test a fake 404, then create a redirect from the admin screen

