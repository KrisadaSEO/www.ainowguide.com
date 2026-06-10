# Content Library and Master Directory System

## Objective

Create a content library that can grow indefinitely without collapsing into a mess.

It needs to support:

- broad categories
- unlimited subcategories
- articles
- featured content
- cross-linking
- directory-style indexing
- learning path behavior
- AI machine-readable metadata

## Key distinction

There are really two related systems here:

### 1. Content Library

The editorial system.
This includes articles, topic hubs, guides, essays, lessons, and cornerstone content.

### 2. Master Directory

The structural index.
This helps users and machines navigate the whole body of work.

The library is for reading.
The directory is for mapping.

## Data model

### Category record

Each category should include:

- `title`
- `slug`
- `parent_slug`
- `path`
- `description`
- `intro`
- `status`
- `sort_order`
- `featured_image`
- `template`
- `sidebar_profile`
- `seo`
- `relationships`

### Article record

Each article should include:

- `title`
- `slug`
- `excerpt`
- `body`
- `category_primary`
- `category_secondary`
- `tags`
- `author`
- `status`
- `publish_date`
- `updated_at`
- `featured`
- `template`
- `sidebar_profile`
- `related_content`
- `seo`

## Category path design

Use explicit category path values so routing and breadcrumbs remain stable.

Example:

- `online-digital-life`
- `online-digital-life/content-systems`
- `online-digital-life/content-systems/php-json-architecture`

## Front-end pages required

### Master library page

Purpose:

- top-level index into the knowledge base
- featured category entry points
- latest featured content
- learning path style navigation

### Category landing page

Purpose:

- explain the category
- surface child categories
- show featured articles
- show recent articles
- link sideways to related themes

### Subcategory landing page

Purpose:

- narrow topic hub
- support deep library navigation
- surface all relevant articles cleanly

### Directory page

Purpose:

- bird's-eye view of the whole site system
- helpful for humans and machines
- supports topic discovery and content governance

## Recommended UI components

- category hero block
- child category cards
- featured article strip
- recent article list
- related path block
- breadcrumb trail
- sidebar profile injection

## Content governance rules

### Rule 1

Every article must belong to at least one primary category.

### Rule 2

Every category must know its parent, even if null.

### Rule 3

No orphaned content.

### Rule 4

Featured content should be controlled by metadata, not manually duplicated in templates.

### Rule 5

Cross-links should be based on category, tags, or explicit relationships.

## Learning-path concept

A powerful differentiator would be letting categories double as learning paths.

Example paths:

- Start Here
- Online Digital Life Basics
- Content Systems
- AI and Visibility
- Actually Own What You Build

This lets the library feel more intentional than a blog archive.

## Suggested top-level categories for Krisada.com

These are directional, not mandatory final labels.

- Start Here
- Online Digital Life
- Visibility and Search
- Content Systems
- AI Website Systems
- Digital Independence
- Monetization and Offers
- Portfolio Lessons
- Tools and Workflows
- Thai / Local / Personal Context

## Master directory role

The directory should answer:

- what topics exist here
- what deeper branches exist under each topic
- what content is foundational
- what should a first-time visitor read first

## Recommended metadata fields for automation

Add these to both category and article models where useful:

- `is_cornerstone`
- `is_featured`
- `difficulty`
- `audience_stage`
- `cta_group`
- `sidebar_profile`
- `reading_time`
- `machine_summary`

## JSON example shape

```json
{
  "type": "article",
  "title": "Why a Content Library Beats a Blog Archive",
  "slug": "why-a-content-library-beats-a-blog-archive",
  "category_primary": "content-systems",
  "category_secondary": ["online-digital-life"],
  "tags": ["content architecture", "php json", "library systems"],
  "status": "published",
  "template": "article",
  "sidebar_profile": "authority-standard",
  "is_featured": true
}
```

## Build order

1. category schema
2. article schema
3. breadcrumb generator
4. category landing template
5. master library page
6. directory page
7. related content resolver
8. learning-path enhancements
