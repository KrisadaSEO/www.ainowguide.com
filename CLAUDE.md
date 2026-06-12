# Repository directives

- This is a private, single-developer repository. The owner reviews changes live.
- **No git.** This site is no longer deployed via GitHub Actions or `git push`. The working copy here is edited directly and is the live production copy.
- **SFTP / Remote-SSH to `webserver005`** ... connection details are in `.vscode/sftp.json` (gitignored, machine-specific). Target: `/home/webserver005/public_html/ainowguide.com` on `webserver005` (`15.204.210.138:1966`).
- Changes saved/uploaded here go live immediately ... there is no build or review step. Double-check edits before uploading.
- **Before uploading**, run PHP lint (`php -l`), JSON validation, and `php scripts/validate-content-integrity.php` as applicable. Regenerate `sitemap.xml` with `php scripts/build-sitemap.php` if content changed.
- The old GitHub Actions "Deploy Static Site" workflow is disabled but kept in the repo for reference/history.
