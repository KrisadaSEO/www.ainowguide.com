# Krisada.com PHP JSON Rebuild Pack

This pack is the architecture handoff for rebuilding **Krisada.com** from a CMS-driven site into a **PHP + JSON flat-file system** that is easier to maintain, automate, clone, and scale across your portfolio.

## Core direction

You are keeping two things intact:

1. **Krisada.com becomes the automation-friendly front-end brand hub**.
2. **The existing strategic lane stays**: _I'm teaching the next generation online digital life._

The old CMS-driven version moves to **CMS.krisada.com**.

## What this pack is for

This pack gives Copilot or Codex enough structure to build the first production version without making strategic decisions on the fly.

It covers:

- brand and visual direction
- marketing positioning and offers
- file structure and coding guard rails
- content library architecture
- master directory and deep category support
- right sidebar system with 3 highlight styles
- Thai/legal/local content handling
- redirect admin system requirements
- federation v7.0 integration rail
- GitHub deploy action rail
- GitHub sitemap action rail
- phased implementation order
- a clean handoff brief for AI coding assistants

## Recommended build philosophy

Build Krisada.com as a **system**, not a theme.

That means:

- content lives in JSON files
- templates stay thin
- routing is predictable
- design tokens are centralized
- sidebars are data-driven
- directories and category pages are generated from content metadata
- federation files are versioned as first-class assets
- deployment and sitemap generation are automated

## Suggested first milestone

Build the minimum durable foundation first:

1. shared layout shell
2. content library structure
3. category and subcategory routing
4. article rendering
5. right sidebar system
6. redirect admin
7. federation output files
8. deployment and sitemap actions

## Suggested domain role

**Krisada.com** should act like:

- the personal authority site
- the philosophy layer
- the teaching and mentoring layer
- the index into the broader portfolio
- the public-facing gateway to your content library

It should not feel like a generic agency brochure.

## File list

- `style-guide.md`
- `marketing.md`
- `architecture.md`
- `content-library.md`
- `sidebar-system.md`
- `redirects-admin.md`
- `federation-v7-integration.md`
- `github-deploy-action.md`
- `github-sitemap-action.md`
- `implementation-phases.md`
- `copilot-codex-handoff.md`

## Notes

The federation v7.0 file in this pack is written as an **integration rail**, not as a rewrite of your existing federation spec. Since your actual v7.0 markdown is in the `www.digitalkarmaweb.com` root, this pack assumes that file remains the source of truth and tells the build how to consume and expose it.
