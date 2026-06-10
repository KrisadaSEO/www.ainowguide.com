# GitHub Sitemap Action Rail

## Goal

Automate sitemap generation so Krisada.com always reflects current content and category structure.

## Why this matters

Your new site structure is data-driven.
That means the sitemap should also be data-driven.

## Recommended scope

Include:

- homepage
- major pages
- library page
- category pages
- subcategory pages
- article pages
- selected directory pages

Exclude:

- drafts
- private or utility routes
- duplicate parameterized URLs
- thin system routes

## Build script recommendation

Create `scripts/build-sitemap.php` that:

1. loads all published content
2. resolves canonical URLs
3. assigns priority and change frequency if you want them
4. writes `sitemap.xml`
5. optionally writes category-specific sitemap files later

## Data rules

Sitemap entries should only use:

- canonical URLs
- published items
- stable routes

## Suggested metadata fields to support sitemap logic

- `status`
- `canonical_url`
- `updated_at`
- `include_in_sitemap`

## Workflow recommendation

Run sitemap generation:

- on push to main
- and/or after deployment on server

## Preferred location

- root `sitemap.xml`
- optional future support for `sitemap-index.xml`

## Validation rule

If content validation fails, sitemap generation should fail too.

## Long-term option

Once the library gets large, split the sitemap into:

- pages sitemap
- articles sitemap
- categories sitemap
- directory sitemap

But do not overbuild that on day one.
