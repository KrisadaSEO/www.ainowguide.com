# AI Now Guide

PHP and JSON flat-file media platform for documenting an AI-first digital asset portfolio in public.

## Architecture

- Content lives in `content/`
- Templates live in `templates/`
- Config lives in `config/`
- Routing, loading, schema, admin, and sidebar resolution live in `bootstrap.php`
- Generated machine-readable files live in `ai/`
- The complete portfolio directory lives in `content/directory/`

All public requests flow through:

```text
index.php -> bootstrap.php -> templates/layouts/default.php
```

## Content Model

The inherited article, category, page, glossary, directory, sidebar, and download systems remain intact.

AI Now Guide adds JSON collections for:

- Session channels
- Sessions
- Membership levels
- Build history

The portfolio directory is preserved as a first-class content type and includes the full property registry.

## Validation

```sh
php -l bootstrap.php
php scripts/validate-content-integrity.php
php scripts/validate-blocks.php
php scripts/validate-redirects.php
php scripts/check-mojibake.php
php deploy/generate-sitemap.php
php scripts/build-federation.php
```

## Generated Files

Do not edit these manually:

- `sitemap.xml`
- `llm.txt`
- `ai/llm.txt`
- `ai/llm.json`
- `ai/catalog.json`
- `ai/manifest.json`
- `ai/federation.json`
- `ai/karma.json`
- `ai/health.json`

## Deployment

GitHub Actions deploys pushes to `main` to AINowGuide.com.
