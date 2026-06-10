# GitHub Deploy Action Rail

## Goal

Create a stable deployment workflow from GitHub to the server with minimal manual friction.

## Philosophy

Deployment should be boring.
That means predictable, reversible, and easy to debug.

## Recommended model

Use GitHub Actions to deploy on push to the production branch.

Typical flow:

1. push to `main`
2. action runs validation
3. action connects to server
4. repo updates on server
5. optional post-deploy tasks run

## Recommended pre-deploy checks

Before deploy, run:

- JSON validation
- PHP linting on relevant files
- sitemap build if part of pipeline
- federation build if part of pipeline

## Post-deploy tasks

After deploy, optionally run:

- cache clear
- federation file build
- sitemap refresh
- permission normalization if needed

## Secrets needed

Typically:

- host
- username
- SSH private key
- deploy path

## Recommended workflow files

- `.github/workflows/deploy.yml`
- optional `.github/workflows/validate.yml`

## Guard rails

- never deploy from multiple branches to the same live target unless intentional
- fail fast on invalid JSON
- keep build scripts idempotent
- log enough detail to debug

## Suggested deployment steps

1. checkout repo
2. set up PHP if needed for validation scripts
3. run validation scripts
4. establish SSH agent
5. run remote deploy commands
6. run post-deploy build commands

## Remote deploy strategy

Preferred remote pattern:

- `git fetch`
- `git reset --hard origin/main`
- run `php scripts/build-federation.php`
- run `php scripts/build-sitemap.php`

This is generally cleaner than pushing raw files via FTP-style sync when the repo is already on the server.

## Notes for your stack

Since you already use GitHub deploy patterns, the first implementation should adapt your existing base files rather than invent a new philosophy.

This doc exists to define the rule set Copilot or Codex should follow when wiring your provided base files into Krisada.com.
