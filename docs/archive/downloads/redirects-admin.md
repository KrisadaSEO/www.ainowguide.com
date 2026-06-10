# Redirect Admin System

## Goal

Create a redirect management system that is structured, importable, and safe.

You already have a redirect system in use and plan to provide the current version. This spec defines how the new PHP JSON site should handle it.

## Redirect philosophy

Redirects are content governance, not random server debris.

They should be:

- centralized
- editable
- importable
- validated
- exportable if needed

## Minimum requirements

The redirect system should support:

- old URL to new URL mappings
- status codes: 301 and 302 at minimum
- notes or reason fields
- active/inactive state
- bulk import from CSV or JSON
- duplicate detection
- loop prevention

## Recommended data model

Each redirect record should include:

- `from`
- `to`
- `type`
- `active`
- `note`
- `source`
- `created_at`
- `updated_at`

## Source examples

- migrated from CMS
- manual entry
- imported legacy list
- SEO cleanup

## Processing options

### Preferred for simplicity

Load redirects from a structured JSON file during bootstrap and match early.

### Better long term

Compile redirect data into a fast lookup array or generated PHP cache file.

## Validation rules

### Required checks

- `from` must be unique
- `to` must not be empty
- no self-redirects
- no redirect chains if avoidable
- no loops
- status code must be allowed

## Admin approach

Since this is a flat-file system, admin can be lightweight.

Recommended options:

### Option A

Manage redirects in a JSON file plus import script.
Good enough for v1.

### Option B

Simple protected admin interface later.
Only build this if the file-based workflow becomes annoying.

## Import script requirement

Build a script that accepts your current redirect source file and normalizes it into the house JSON format.

## Matching priority

Process redirects before route resolution.

Request order:

1. normalize incoming URL
2. check redirect table
3. if match found, issue redirect
4. otherwise continue route handling

## Suggested file location

- `/content/redirects/redirects.json`
- `/scripts/import-redirects.php`
- `/scripts/validate-redirects.php`

## Migration note

When you provide the current redirect admin source, map it into this model rather than trying to preserve every old implementation detail.
