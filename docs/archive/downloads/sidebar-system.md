# Right Sidebar System

## Goal

Create a right sidebar that is modular, useful, and easy to reuse across page types.

It must support:

1. three highlighted sidebar styles
2. normal supporting blocks
3. special Thai/local/legal blocks
4. assignment by page, template, category, or profile

## Sidebar philosophy

The sidebar is not decoration.
It is a guided relevance system.

Its jobs are:

- move readers deeper into the site
- present relevant calls to action
- surface authority/proof
- support special context content like Thai links or legal notes

## Three highlight styles

### 1. Strategic Highlight

Use for:

- cornerstone content
- system explanations
- featured guides
- key offers

Visual feel:

- slightly premium
- darker or accent-backed panel
- strong heading
- single focused CTA

### 2. Practical Highlight

Use for:

- tools
- checklists
- starter steps
- implementation resources

Visual feel:

- lighter utility-card style
- clean and action-oriented
- compact, scan-friendly

### 3. Personal / Mentor Highlight

Use for:

- about Krisada
- mentoring angle
- personal message
- audience reassurance

Visual feel:

- more human
- editorial or note-card feel
- can include photo or signature block later

## Base block types

Recommended block types:

- featured-link
- cta
- mini-bio
- related-articles
- topic-list
- legal-note
- thai-links
- proof-card
- download-card
- newsletter-card

## Data-driven assignment

Each page or article should declare a `sidebar_profile`.

Examples:

- `authority-standard`
- `library-learning`
- `offer-soft`
- `thai-context`
- `personal-editorial`

The renderer then loads a matching sidebar stack from JSON.

## Thai/local/legal requirement

Since you need Thai-specific material on the site, do not hack that into random page templates.

Instead:

- create dedicated Thai/local/legal block types
- assign them conditionally by profile or route
- make their presence explicit in data

### Suggested special block categories

- Thai business or family context
- Thai-language or Thai-relevant resource links
- legal disclaimers
- personal context notes
- affiliate or compensation notes if needed

## Sidebar config model

Each sidebar profile should define:

- `name`
- `slug`
- `blocks`
- `display_rules`
- `fallback_profile`

Each block should define:

- `type`
- `style`
- `title`
- `body`
- `links`
- `cta_label`
- `cta_url`
- `visibility_rules`

## Example profile idea

```json
{
  "slug": "authority-standard",
  "blocks": [
    { "type": "featured-link", "style": "strategic-highlight" },
    { "type": "related-articles", "style": "practical-highlight" },
    { "type": "mini-bio", "style": "personal-highlight" }
  ]
}
```

## Display rules

Allow assignment by:

- template type
- category
- specific slug
- explicit page override

Priority order:

1. page-level explicit assignment
2. article or category metadata
3. template default
4. global fallback

## Design rule

No sidebar should exceed the purpose of the page.
If the page is editorial, keep the sidebar editorial.
If the page is transactional, allow stronger CTAs.

## Recommended v1 build

Build these first:

- strategic highlight block
- practical highlight block
- personal highlight block
- thai/legal support block
- related articles block
- sidebar profile resolver
