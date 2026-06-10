# Federation v7.0 Integration Rail

## Purpose

Krisada.com should expose federation-aware files and metadata without making the website feel like a machine-only property.

The human site stays readable.
The machine layer stays structured.

## Source of truth

Your existing **Federation v7.0 markdown** in the `www.digitalkarmaweb.com` root should remain the source of truth for the broader federation spec.

This Krisada.com rebuild should integrate with that spec, not invent a competing one.

## Krisada.com role in the federation

Krisada.com should function as:

- a branded human-facing authority node
- a AI machine-readable content node
- a portfolio-connected discovery point
- a possible teaching/documentation node

## Required federation-facing files

At minimum, prepare the site to output:

- `ai/llm.txt`
- `ai/llm.json`
- `ai/catalog.json`
- `ai/manifest.json`
- `ai/federation.json`

Optional later:

- `ai/journal.json`
- `ai/entities.json`
- `ai/karma.json`
- `ai/health.json`

## Build rule

Federation files should be generated from shared config where possible, not manually edited in scattered places.

## Suggested config inputs

Create a site-level federation config such as:

- site name
- canonical domain
- node role
- supported content types
- related properties
- AI machine-readable summaries
- version reference
- compatibility notes

## Integration approach

### Human layer

Use the site to explain your philosophy and systems.

### Machine layer

Use `/ai/` endpoints and metadata injection to expose structured information cleanly.

## Metadata injection

Support these where relevant:

- canonical metadata
- JSON-LD for articles and site identity
- alternate links to AI machine-readable files
- robots references where useful

## Content relationship strategy

Allow categories, articles, or directory items to declare federation relevance fields such as:

- `federation_role`
- `machine_summary`
- `related_nodes`
- `datasets_exposed`

## Build script recommendation

Include a script like `scripts/build-federation.php` that:

1. loads base federation config
2. loads site identity config
3. compiles `/ai/` output files
4. validates required fields
5. writes build artifacts

## Versioning rule

The Krisada.com repo should reference the federation version currently in force, but the deeper spec still lives in your main federation documentation system.

## Minimum v1 expectation

A good v1 should:

- expose the core `/ai/` files
- declare Krisada.com's node identity clearly
- include AI machine-readable summaries on important content
- remain easy to update when federation v7.x evolves
