# Branching Policy

This repo follows the standard Claude Code branching workflow used across all
KrisadaSEO repos.

## Working branch

- All work happens on a dedicated feature branch, typically named
  `claude/<short-description>-<session-id>`.
- Create the branch locally if it doesn't already exist.
- Never commit directly to `main` (or the repo's default branch).
- Never push to a different branch than the one assigned for the task without
  explicit permission.

## Commits

- Always create **new** commits — do not amend or rewrite commits that have
  already been pushed, unless explicitly asked.
- Write clear, descriptive commit messages focused on *why* the change was
  made.
- Only stage and commit files relevant to the task; never use `git add -A`
  or `git add .` blindly (avoid pulling in `.env`, credentials, build output,
  etc.).
- Never commit changes unless explicitly asked to.

## Pushing

- Push with `git push -u origin <branch-name>`.
- On network failure, retry up to 4 times with exponential backoff
  (2s, 4s, 8s, 16s).
- Prefer `git fetch origin <branch-name>` / `git pull origin <branch-name>`
  over fetching/pulling everything.

## Pull requests

- Do **not** open a pull request unless the user explicitly asks for one.
- When asked, base the PR on the repo's default branch, keep titles under
  ~70 characters, and put details in the description/body.

## Destructive operations

- Never use `--force`/`--force-with-lease` push, `git reset --hard`,
  `git checkout -- <file>`, `git clean -f`, `--no-verify`, or
  `--no-gpg-sign` unless the user explicitly requests it.
- If a pre-commit/pre-push hook fails, fix the underlying issue and create a
  new commit rather than bypassing the hook.
- Investigate unfamiliar local state (uncommitted changes, untracked files,
  unexpected branches) before overwriting or discarding it — it may be
  in-progress work.
