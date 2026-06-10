<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($is_new_download ? 'Create Download' : 'Edit Download') ?> ... AI Now Guide Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<?php
$download = $download_entry['raw'] ?? [];
$sourceInfo = $download_entry['source_info'] ?? ['exists' => false, 'error' => 'No source configured yet.'];
$stats = $download_entry['stats'] ?? ['claims' => 0, 'downloads' => 0, 'last_claimed_at' => '', 'last_downloaded_at' => ''];
$recentClaims = $download_entry['recent_claims'] ?? [];
?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title"><?= e($is_new_download ? 'Create Download' : ((string) ($download['title'] ?? 'Edit Download'))) ?></h1>
      <p class="admin-page-desc">
        <?php if ($is_new_download): ?>
          Create a protected landing page and connect it to either an uploaded file or a repo directory that will be zipped on demand.
        <?php else: ?>
          <span class="admin-table__mono"><?= e((string) ($download['slug'] ?? '')) ?></span>
          <?php if (!empty($download['canonical_url'])): ?>
            - <a href="<?= e((string) ($download['canonical_url'] ?? '#')) ?>" target="_blank" rel="noopener">View public page</a>
          <?php endif; ?>
        <?php endif; ?>
      </p>
    </div>

    <?php if (!empty($download_success)): ?>
      <div class="admin-alert admin-alert--success" role="alert"><?= e($download_success) ?></div>
    <?php endif; ?>

    <?php if (!empty($download_error)): ?>
      <div class="admin-alert admin-alert--error" role="alert"><?= e($download_error) ?></div>
    <?php endif; ?>

    <div class="admin-download-layout">
      <form method="post" enctype="multipart/form-data" class="admin-editor-form">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
        <input type="hidden" name="action" value="save">

        <section class="admin-panel">
          <h2 class="admin-panel__heading">Identity</h2>
          <div class="admin-editor-grid">
            <div class="admin-form-field">
              <label for="download-title" class="admin-form-label">Title</label>
              <input id="download-title" type="text" name="title" class="admin-form-input" value="<?= e((string) ($download['title'] ?? '')) ?>" required>
            </div>

            <div class="admin-form-field">
              <label for="download-slug" class="admin-form-label">Slug</label>
              <input id="download-slug" type="text" name="slug" class="admin-form-input" value="<?= e((string) ($download['slug'] ?? '')) ?>" <?= $is_new_download ? 'required' : 'readonly' ?>>
            </div>

            <div class="admin-form-field">
              <label for="download-status" class="admin-form-label">Status</label>
              <select id="download-status" name="status" class="admin-form-input admin-form-select">
                <option value="draft"<?= (($download['status'] ?? 'draft') === 'draft') ? ' selected' : '' ?>>Draft</option>
                <option value="published"<?= (($download['status'] ?? '') === 'published') ? ' selected' : '' ?>>Published</option>
              </select>
            </div>

            <div class="admin-form-field">
              <label for="download-filename" class="admin-form-label">Download Filename</label>
              <input id="download-filename" type="text" name="download_filename" class="admin-form-input" value="<?= e((string) ($download['download_filename'] ?? '')) ?>" placeholder="bundle.zip">
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="download-summary" class="admin-form-label">Summary</label>
              <textarea id="download-summary" name="summary" class="admin-form-textarea" rows="4"><?= e((string) ($download['summary'] ?? '')) ?></textarea>
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="download-body" class="admin-form-label">Body HTML</label>
              <textarea id="download-body" name="body" class="admin-form-textarea" rows="12"><?= e((string) ($download['body'] ?? '')) ?></textarea>
            </div>
          </div>
        </section>

        <section class="admin-panel">
          <h2 class="admin-panel__heading">Protected Source</h2>
          <div class="admin-editor-grid">
            <div class="admin-form-field">
              <label for="source-kind" class="admin-form-label">Source Kind</label>
              <select id="source-kind" name="source_kind" class="admin-form-input admin-form-select">
                <option value="repo"<?= (($download['source_kind'] ?? 'repo') === 'repo') ? ' selected' : '' ?>>Repo path</option>
                <option value="upload"<?= (($download['source_kind'] ?? '') === 'upload') ? ' selected' : '' ?>>Uploaded file</option>
              </select>
            </div>

            <div class="admin-form-field">
              <label for="delivery-type" class="admin-form-label">Delivery Type</label>
              <select id="delivery-type" name="delivery_type" class="admin-form-input admin-form-select">
                <option value="file"<?= (($download['delivery_type'] ?? 'file') === 'file') ? ' selected' : '' ?>>Direct file</option>
                <option value="zip_directory"<?= (($download['delivery_type'] ?? '') === 'zip_directory') ? ' selected' : '' ?>>Zip directory on demand</option>
              </select>
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="source-path" class="admin-form-label">Repo Source Path</label>
              <input id="source-path" type="text" name="source_path" class="admin-form-input" value="<?= e((string) ($download['source_path'] ?? '')) ?>" placeholder="downloads/2026-website-problems-bundle">
              <p class="admin-inline-help">Use a repo-relative file or directory path. Direct web access stays blocked; the system serves it through a protected route.</p>
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="asset-file" class="admin-form-label">Upload File</label>
              <input id="asset-file" type="file" name="asset_file" class="admin-form-input">
              <?php if (!empty($download['storage_path'])): ?>
                <p class="admin-inline-help">Current uploaded asset: <code><?= e((string) ($download['storage_path'] ?? '')) ?></code></p>
              <?php else: ?>
                <p class="admin-inline-help">Leave this empty if the download should come from a repo path instead.</p>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <section class="admin-panel">
          <h2 class="admin-panel__heading">Gate Copy</h2>
          <div class="admin-editor-grid">
            <div class="admin-form-field">
              <label for="button-label" class="admin-form-label">Button Label</label>
              <input id="button-label" type="text" name="button_label" class="admin-form-input" value="<?= e((string) ($download['button_label'] ?? 'Get the download')) ?>">
            </div>

            <div class="admin-form-field">
              <label for="email-subject" class="admin-form-label">Email Subject</label>
              <input id="email-subject" type="text" name="email_subject" class="admin-form-input" value="<?= e((string) ($download['email_subject'] ?? '')) ?>">
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="gate-headline" class="admin-form-label">Gate Headline</label>
              <input id="gate-headline" type="text" name="gate_headline" class="admin-form-input" value="<?= e((string) ($download['gate_headline'] ?? 'Unlock the download')) ?>">
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="gate-description" class="admin-form-label">Gate Description</label>
              <textarea id="gate-description" name="gate_description" class="admin-form-textarea" rows="4"><?= e((string) ($download['gate_description'] ?? '')) ?></textarea>
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="success-message" class="admin-form-label">Success Message</label>
              <textarea id="success-message" name="success_message" class="admin-form-textarea" rows="3"><?= e((string) ($download['success_message'] ?? '')) ?></textarea>
            </div>
          </div>
        </section>

        <section class="admin-panel">
          <h2 class="admin-panel__heading">SEO and Visibility</h2>
          <div class="admin-editor-grid">
            <div class="admin-form-field">
              <label for="seo-title" class="admin-form-label">SEO Title</label>
              <input id="seo-title" type="text" name="seo_title" class="admin-form-input" value="<?= e((string) ($download['seo']['title'] ?? '')) ?>">
            </div>

            <div class="admin-form-field">
              <label for="sidebar-profile" class="admin-form-label">Sidebar Profile</label>
              <input id="sidebar-profile" type="text" name="sidebar_profile" class="admin-form-input" value="<?= e((string) ($download['sidebar_profile'] ?? 'authority-standard')) ?>">
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label for="seo-description" class="admin-form-label">SEO Description</label>
              <textarea id="seo-description" name="seo_description" class="admin-form-textarea" rows="3"><?= e((string) ($download['seo']['description'] ?? '')) ?></textarea>
            </div>

            <div class="admin-form-field admin-form-field--full">
              <label class="admin-checkbox">
                <input type="checkbox" name="include_in_sitemap" value="1" <?= !empty($download['include_in_sitemap']) ? 'checked' : '' ?>>
                <span>Include this landing page in the sitemap</span>
              </label>
            </div>
          </div>
        </section>

        <div class="admin-form-actions">
          <button type="submit" class="btn btn--gold"><?= e($is_new_download ? 'Create Download' : 'Save Changes') ?></button>
          <a href="/admin/downloads" class="btn btn--ghost">Back to Downloads</a>
          <?php if (!$is_new_download): ?>
            <button type="submit" name="action" value="delete" class="btn btn--danger" onclick="return confirm('Delete this download record?')">Delete</button>
          <?php endif; ?>
        </div>
      </form>

      <aside class="admin-download-sidebar">
        <section class="admin-panel">
          <h2 class="admin-panel__heading">Source Status</h2>
          <p class="admin-panel__desc">
            <span class="admin-chip admin-chip--<?= !empty($sourceInfo['exists']) ? 'green' : 'danger' ?>">
              <?= !empty($sourceInfo['exists']) ? 'Ready' : 'Broken' ?>
            </span>
          </p>
          <?php if (!empty($sourceInfo['resolved_path'])): ?>
            <p class="admin-inline-help"><strong>Resolved path:</strong><br><code><?= e((string) ($sourceInfo['resolved_path'] ?? '')) ?></code></p>
          <?php endif; ?>
          <?php if (!empty($sourceInfo['download_filename'])): ?>
            <p class="admin-inline-help"><strong>Delivered as:</strong> <?= e((string) ($sourceInfo['download_filename'] ?? '')) ?></p>
          <?php endif; ?>
          <?php if (!empty($sourceInfo['error'])): ?>
            <p class="admin-inline-help admin-inline-help--danger"><?= e((string) ($sourceInfo['error'] ?? '')) ?></p>
          <?php endif; ?>
        </section>

        <section class="admin-panel">
          <h2 class="admin-panel__heading">Access Stats</h2>
          <div class="admin-meta-list">
            <div class="admin-meta-list__row">
              <span>Claims</span>
              <strong><?= (int) ($stats['claims'] ?? 0) ?></strong>
            </div>
            <div class="admin-meta-list__row">
              <span>Downloads</span>
              <strong><?= (int) ($stats['downloads'] ?? 0) ?></strong>
            </div>
            <div class="admin-meta-list__row">
              <span>Last claim</span>
              <strong><?= e((string) ($stats['last_claimed_at'] ?? '')) ?></strong>
            </div>
            <div class="admin-meta-list__row">
              <span>Last download</span>
              <strong><?= e((string) ($stats['last_downloaded_at'] ?? '')) ?></strong>
            </div>
          </div>
        </section>

        <?php if (!$is_new_download): ?>
          <section class="admin-panel">
            <h2 class="admin-panel__heading">Recent Claims</h2>
            <?php if (empty($recentClaims)): ?>
              <p class="admin-empty">No claims yet.</p>
            <?php else: ?>
              <div class="admin-table-wrap">
                <table class="admin-table">
                  <thead>
                    <tr>
                      <th>Email</th>
                      <th>Downloads</th>
                      <th class="admin-hide-mobile">Last</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentClaims as $claim): ?>
                      <tr>
                        <td class="admin-table__mono"><?= e((string) ($claim['email'] ?? '')) ?></td>
                        <td><?= (int) ($claim['download_count'] ?? 0) ?></td>
                        <td class="admin-table__muted admin-hide-mobile"><?= e((string) (($claim['last_downloaded_at'] ?? '') !== '' ? ($claim['last_downloaded_at'] ?? '') : ($claim['last_issued_at'] ?? ''))) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </section>
        <?php endif; ?>
      </aside>
    </div>

  </div>
</main>

</body>
</html>
