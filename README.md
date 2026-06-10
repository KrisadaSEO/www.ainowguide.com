# Krisada.com

PHP + JSON flat-file publishing system. No database. No CMS. Content lives in JSON, presentation lives in PHP, and deployment runs through GitHub Actions.

## What This Repo Is

Krisada.com is a data-driven authority site for teaching online digital life. The stack is intentionally simple:

- Content lives in `content/`
- Templates live in `templates/`
- Config lives in `config/`
- Routing, loading, and rendering logic live in `bootstrap.php`
- Generated AI machine-readable files live in `ai/`
- The generated sitemap lives at the repo root as `sitemap.xml`
- The public crawler file lives at the repo root as `robots.txt`

All requests flow through:

```text
index.php -> bootstrap.php -> templates/layouts/default.php
```

## Important Files

```text
/                     Repo root
|-- index.php         Entry point
|-- bootstrap.php     Routing, content loading, app state, helpers
|-- .htaccess         Required for routing and assets
|-- robots.txt        Public crawler rules
|-- sitemap.xml       Generated sitemap
|-- config/           Site config, routes, design tokens, federation config
|-- content/          Pages, articles, categories, sidebars, redirects
|-- templates/        Layouts, page templates, sidebar partials
|-- public/           CSS and other public assets
|-- scripts/          Sitemap, federation, redirect validation utilities
`-- ai/               Generated AI machine-readable outputs
```

## Local Development

Recommended: run under Apache with `.htaccess` enabled so routing behaves like production.

If you only need a quick read-only preview, you can use PHP's built-in server, but URL rewriting will not match production:

```sh
php -S localhost:8000
```

## Adding Content

### New article

Create `content/articles/{slug}.json`.

Key required fields include:

- `type`
- `title`
- `slug`
- `canonical_url`
- `status`
- `body`
- `category_primary`
- `template`
- `sidebar_profile`
- `updated_at`

See `docs/starter-prompts.md` for canonical article and category schemas.

### New category

Create `content/categories/{slug}.json`.

Notes:

- `slug` is only the final segment
- `path` is the full routable path
- `canonical_url` should be `/library/{path}/`
- subcategory routing is driven by `path`, not filename

### New redirect

Use the importer instead of hand-editing large redirect batches:

```sh
php scripts/import-redirects.php path/to/redirects.csv --dry-run
php scripts/import-redirects.php path/to/redirects.csv
```

## Build And Validation

```sh
# PHP syntax
php -l bootstrap.php

# Validate redirects
php scripts/validate-redirects.php

# Validate cross-file content references
php scripts/validate-content-integrity.php

# Check for mojibake / encoding corruption
php scripts/check-mojibake.php

# Regenerate sitemap
php scripts/build-sitemap.php

# Regenerate federation files
php scripts/build-federation.php
```

## Generated Files

Do not edit these manually:

- `sitemap.xml`
- `ai/llm.txt`
- `ai/llm.json`
- `ai/catalog.json`
- `ai/manifest.json`
- `ai/federation.json`

Regenerate them with the matching scripts in `scripts/`.

## Deployment

GitHub Actions deploys on push to `main`.

The validation workflow checks:

- JSON validity
- PHP syntax
- subcategory path integrity
- redirect validity
- content relationship integrity
- mojibake / encoding corruption
- sitemap generation
- federation generation

The deploy workflow then ships the site to production over rsync.

## Cloning To A New Domain

1. Copy the repo.
2. Confirm `.htaccess` is present at the repo root before testing anything.
3. Copy `config/install.example.json` to `config/install.json`.
4. Update `config/site.json` and `config/federation.json`.
5. Replace `content/` with the new site's content.
6. Set the deploy secrets in GitHub Actions if the new repo will deploy.

## Documentation

Start with:

- `AGENTS.md`
- `docs/project-memory-index.md`
- `docs/repo-operating-memory.md`
- `docs/starter-prompts.md`
