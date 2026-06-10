# Krisada.com ... Agent Instructions

Read this file before touching anything in this repo.

## Stack

PHP + JSON flat-file site. No database. No CMS. No WordPress.
Single entry point: `index.php` → `bootstrap.php` → `templates/layouts/default.php`.

## Files you must never edit manually

These are generated outputs. Edit the scripts, not the files:

| File | Regenerate with |
|---|---|
| `ai/llm.txt` | `php scripts/build-federation.php` |
| `ai/llm.json` | `php scripts/build-federation.php` |
| `ai/catalog.json` | `php scripts/build-federation.php` |
| `ai/manifest.json` | `php scripts/build-federation.php` |
| `ai/federation.json` | `php scripts/build-federation.php` |
| `sitemap.xml` | `php scripts/build-sitemap.php` |

## Routing rules

- The router lives in `bootstrap.php`. Do not create a second router.
- Category routing is driven by the `path` field inside the JSON file ... not the filename.
- Subcategory `path` must equal `{parent_slug}/{slug}`. Example: slug `php-json-architecture` with parent `content-systems` → path `content-systems/php-json-architecture`.
- Filename convention `parent--child.json` is for human navigation only. Routing ignores it.

## bootstrap.php is intentionally one file

Do not split `bootstrap.php` into a lib directory or multiple files. All functions, routing, content loading, and sidebar resolution live here by design. The single-file architecture makes the rendering path fully traceable and is what makes AI-assisted development reliable on this stack.

## Sidebars are never hardcoded in templates

Sidebar content is resolved via `site_resolve_sidebar_profile()` in bootstrap. The chain is:
`article/category JSON` → `sidebar_profile` slug → `content/sidebars/{profile}.json` → blocks → `templates/partials/sidebar-block.php`

Never put sidebar content directly in a template. Use the profile system.

## Category fields: slug vs path

- `slug` ... the last segment only, e.g. `php-json-architecture`
- `path` ... the full routable path, e.g. `content-systems/php-json-architecture`
- `canonical_url` ... derived from path: `/library/{path}/`

Routing uses `path`. Internal links use `canonical_url`. Never use `slug` for URL construction.

## Config: site.json vs install.json

`config/site.json` is the committed config. `config/install.json` is gitignored and machine-specific ... it merges over `site.json` at runtime if it exists. If values in `site.json` do not match what the site renders locally, check whether `install.json` is overriding them.

## .htaccess is required

`.htaccess` must exist at the repo root. Without it: no URL routing, no asset loading, everything breaks. It is committed to git ... do not gitignore it.

## After any change, run

```bash
# PHP syntax
php -l bootstrap.php

# Content integrity
php scripts/validate-content-integrity.php

# JSON validation
php -r "json_decode(file_get_contents('content/articles/your-file.json'), true, 512, JSON_THROW_ON_ERROR); echo 'OK';"

# Sitemap (confirms bootstrap loads)
php scripts/build-sitemap.php
```

## Content schemas

See `docs/starter-prompts.md` for canonical article and category schemas.
See `docs/project-memory-index.md` and `docs/repo-operating-memory.md` for current project memory and architecture context.
