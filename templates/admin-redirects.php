<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Redirect Manager ... AI Now Guide Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<main class="admin-main">
<div class="admin-container">

  <div class="admin-page-header">
    <h1 class="admin-page-title">Redirect Manager</h1>
    <p class="admin-page-desc">Review 404 errors and create redirects.</p>
  </div>

  <?php if (!empty($redirect_success)): ?>
    <div class="admin-alert admin-alert--success" role="alert"><?= e($redirect_success) ?></div>
  <?php endif; ?>

  <?php if (!empty($redirect_error)): ?>
    <div class="admin-alert admin-alert--error" role="alert"><?= e($redirect_error) ?></div>
  <?php endif; ?>


  <!-- ══ ADD REDIRECT FORM ══════════════════════════════════════════════════ -->
  <section class="admin-panel" id="add-redirect">
    <h2 class="admin-panel__heading">Add Redirect</h2>
    <form method="post" action="/admin/redirects" class="admin-form">
      <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
      <input type="hidden" name="action" value="add-redirect">
      <div class="admin-form__row">
        <div class="admin-form__field admin-form__field--wide">
          <span>From URL</span>
          <input type="text" id="redir-from" name="from" placeholder="/old-page" required autocomplete="off">
        </div>
        <div class="admin-form__field admin-form__field--wide">
          <span>To URL</span>
          <input type="text" id="redir-to" name="to" placeholder="/new-page" required autocomplete="off">
        </div>
        <div class="admin-form__field">
          <span>Type</span>
          <select name="type">
            <option value="301" selected>301 Permanent</option>
            <option value="302">302 Temporary</option>
          </select>
        </div>
        <div class="admin-form__field admin-form__field--action">
          <button type="submit" class="btn btn--secondary">Add</button>
        </div>
      </div>
    </form>
  </section>


  <!-- ══ 404 LOG ════════════════════════════════════════════════════════════ -->
  <section class="admin-panel">
    <h2 class="admin-panel__heading">
      404 Errors
      <span class="admin-badge admin-badge--red"><?= count($log_entries) ?></span>
    </h2>
    <p class="admin-panel__desc">URLs that visitors tried but didn't find. Hit "Redirect" to pre-fill the form above.</p>

    <?php if (!empty($log_entries)): ?>
    <div class="admin-toolbar">
      <form method="post" action="/admin/redirects" class="admin-inline-form"
            onsubmit="return confirm('Delete all 404 errors from the log?')">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
        <input type="hidden" name="action" value="purge-all-errors">
        <button type="submit" class="btn btn--danger btn--sm">Purge All</button>
      </form>
      <form method="post" action="/admin/redirects" class="admin-inline-form admin-toolbar__inline"
            onsubmit="return confirm('Delete errors with fewer than '+this.min_hits.value+' hit(s)?')">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
        <input type="hidden" name="action" value="purge-errors-below">
        <label for="purge-min-hits" class="admin-toolbar__label">Purge below</label>
        <input type="number" id="purge-min-hits" name="min_hits" value="2" min="1" class="admin-toolbar__num-input">
        <span class="admin-toolbar__label">hits</span>
        <button type="submit" class="btn btn--secondary btn--sm">Purge</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if (empty($log_entries)): ?>
      <p class="admin-empty">No 404 errors logged yet.</p>
    <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>URL</th>
              <th>Hits</th>
              <th class="admin-hide-mobile">Last Hit</th>
              <th class="admin-hide-tablet">Referrer</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($log_entries as $entry): ?>
              <tr>
                <td class="admin-table__mono admin-table__url"><?= e($entry['url'] ?? '') ?></td>
                <td>
                  <span class="admin-chip admin-chip--<?= ($entry['hits'] ?? 0) >= 10 ? 'danger' : (($entry['hits'] ?? 0) >= 3 ? 'amber' : 'muted') ?>">
                    <?= (int) ($entry['hits'] ?? 0) ?>
                  </span>
                </td>
                <td class="admin-table__muted admin-hide-mobile"><?= e($entry['last_hit'] ?? '') ?></td>
                <td class="admin-table__muted admin-table__referrer admin-hide-tablet"><?= e($entry['referrer'] ?? '...') ?></td>
                <td class="admin-table__actions">
                  <button type="button" class="btn btn--ghost btn--xs"
                          onclick="prefillRedirect('<?= e(addslashes($entry['url'] ?? '')) ?>')">
                    Redirect
                  </button>
                  <form method="post" action="/admin/redirects" class="admin-inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
                    <input type="hidden" name="action" value="dismiss-404">
                    <input type="hidden" name="url" value="<?= e($entry['url'] ?? '') ?>">
                    <button type="submit" class="admin-icon-button" title="Dismiss">&times;</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>


  <!-- ══ ACTIVE REDIRECTS ═══════════════════════════════════════════════════ -->
  <section class="admin-panel">
    <h2 class="admin-panel__heading">
      Active Redirects
      <span class="admin-badge"><?= count($redirects) ?></span>
    </h2>

    <?php if (empty($redirects)): ?>
      <p class="admin-empty">No redirects configured yet.</p>
    <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>From</th>
              <th>To</th>
              <th>Type</th>
              <th class="admin-hide-mobile">Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($redirects as $i => $rule): ?>
              <tr>
                <td class="admin-table__mono"><?= e($rule['from'] ?? '') ?></td>
                <td class="admin-table__mono"><?= e($rule['to'] ?? '') ?></td>
                <td>
                  <span class="admin-chip admin-chip--<?= ($rule['type'] ?? 301) === 302 ? 'amber' : 'green' ?>">
                    <?= (int) ($rule['type'] ?? 301) ?>
                  </span>
                </td>
                <td class="admin-table__muted admin-hide-mobile"><?= e($rule['created_at'] ?? '') ?></td>
                <td>
                  <form method="post" action="/admin/redirects" class="admin-inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete-redirect">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <button type="submit"
                            class="admin-icon-button"
                            title="Remove redirect"
                            onclick="return confirm('Delete this redirect?')">&times;</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

</div>
</main>

<script>
function prefillRedirect(url) {
  var fromField = document.getElementById('redir-from');
  var toField   = document.getElementById('redir-to');
  if (fromField) {
    fromField.value = url;
    toField.value   = '';
    toField.focus();
    document.getElementById('add-redirect').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
</script>

</body>
</html>
