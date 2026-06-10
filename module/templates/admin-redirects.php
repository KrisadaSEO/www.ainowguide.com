<?php
// ════════════════════════════════════════════════════════════════════════════
// ADMIN ... REDIRECT MANAGER TEMPLATE
// Data contract (from router.php):
//   $redirects   ... array of redirect rules from redirects.json
//   $log_entries  ... array of 404 log entries sorted by hits desc
//   $page_title   ... string
// ════════════════════════════════════════════════════════════════════════════
$token = e($_GET['token'] ?? '');
$breadcrumbs = [];
$sidebar_cards = [];
$data = [];
?>
<?php require PARTIALS_PATH . 'head.php'; ?>
<?php require PARTIALS_PATH . 'header.php'; ?>

<main class="site-main">
<div class="page-layout page-layout--full">
<div class="page-content">

  <div class="admin-header">
    <div class="admin-header__eyebrow">Admin</div>
    <h1 class="admin-header__title">Redirect Manager</h1>
    <p class="admin-header__desc">Review 404 errors and create redirects. Like Joomla's redirect component ... but simpler.</p>
  </div>


  <!-- ══ ADD REDIRECT FORM ════════════════════════════════════════════════ -->
  <section class="admin-panel">
    <h2 class="admin-panel__heading">Add Redirect</h2>
    <form method="post" action="/admin/redirects?token=<?= $token ?>" class="admin-form">
      <input type="hidden" name="action" value="add-redirect">
      <input type="hidden" name="token" value="<?= $token ?>">
      <div class="admin-form__row">
        <div class="admin-form__field admin-form__field--wide">
          <label for="redir-from">From URL</label>
          <input type="text" id="redir-from" name="from" placeholder="/old-page" required>
        </div>
        <div class="admin-form__field admin-form__field--wide">
          <label for="redir-to">To URL</label>
          <input type="text" id="redir-to" name="to" placeholder="/new-page" required>
        </div>
        <div class="admin-form__field">
          <label for="redir-type">Type</label>
          <select id="redir-type" name="type">
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


  <!-- ══ ACTIVE REDIRECTS ═════════════════════════════════════════════════ -->
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
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($redirects as $i => $rule): ?>
              <tr>
                <td class="admin-table__mono"><?= e($rule['from'] ?? '') ?></td>
                <td class="admin-table__mono"><?= e($rule['to'] ?? '') ?></td>
                <td><span class="admin-chip admin-chip--<?= ($rule['type'] ?? 301) === 302 ? 'amber' : 'green' ?>"><?= (int) ($rule['type'] ?? 301) ?></span></td>
                <td class="admin-table__muted"><?= e($rule['created_at'] ?? '') ?></td>
                <td>
                  <form method="post" action="/admin/redirects?token=<?= $token ?>" class="admin-inline-form">
                    <input type="hidden" name="action" value="delete-redirect">
                    <input type="hidden" name="token" value="<?= $token ?>">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <button type="submit" class="admin-btn-delete" title="Remove redirect">&times;</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>


  <!-- ══ 404 LOG ══════════════════════════════════════════════════════════ -->
  <section class="admin-panel">
    <h2 class="admin-panel__heading">
      404 Errors
      <span class="admin-badge admin-badge--red"><?= count($log_entries) ?></span>
    </h2>
    <p class="admin-panel__desc">URLs that visitors tried but didn't find. Create a redirect or dismiss.</p>

    <?php if (!empty($log_entries)): ?>
    <div class="admin-toolbar">
      <!-- Purge all -->
      <form method="post" action="/admin/redirects?token=<?= $token ?>" class="admin-inline-form"
            onsubmit="return confirm('Delete all 404 errors from the log?');">
        <input type="hidden" name="action" value="purge-all-errors">
        <input type="hidden" name="token" value="<?= $token ?>">
        <button type="submit" class="btn btn--danger btn--sm">Purge All Errors</button>
      </form>
      <!-- Purge below N hits -->
      <form method="post" action="/admin/redirects?token=<?= $token ?>" class="admin-inline-form admin-toolbar__inline"
            onsubmit="return confirm('Delete all errors with fewer than ' + this.min_hits.value + ' hit(s)?');">
        <input type="hidden" name="action" value="purge-errors-below">
        <input type="hidden" name="token" value="<?= $token ?>">
        <label for="purge-min-hits" class="admin-toolbar__label">Purge errors with fewer than</label>
        <input type="number" id="purge-min-hits" name="min_hits" value="2" min="1" class="admin-toolbar__num-input">
        <span class="admin-toolbar__label">hit(s)</span>
        <button type="submit" class="btn btn--secondary btn--sm">Purge</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if (empty($log_entries)): ?>
      <p class="admin-empty">No 404 errors logged yet. They'll appear here as they happen.</p>
    <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>URL</th>
              <th>Hits</th>
              <th>Last Hit</th>
              <th>Referrer</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($log_entries as $entry): ?>
              <tr>
                <td class="admin-table__mono admin-table__url"><?= e($entry['url'] ?? '') ?></td>
                <td>
                  <span class="admin-chip admin-chip--<?= ($entry['hits'] ?? 0) >= 10 ? 'red' : (($entry['hits'] ?? 0) >= 3 ? 'amber' : 'muted') ?>">
                    <?= (int) ($entry['hits'] ?? 0) ?>
                  </span>
                </td>
                <td class="admin-table__muted"><?= e($entry['last_hit'] ?? '') ?></td>
                <td class="admin-table__muted admin-table__referrer"><?= e($entry['referrer'] ?? '...') ?></td>
                <td class="admin-table__actions">
                  <!-- Quick-redirect button pre-fills the add form -->
                  <button type="button" class="btn btn--ghost btn--xs" onclick="prefillRedirect('<?= e(addslashes($entry['url'] ?? '')) ?>')">Redirect</button>
                  <!-- Dismiss -->
                  <form method="post" action="/admin/redirects?token=<?= $token ?>" class="admin-inline-form">
                    <input type="hidden" name="action" value="dismiss-404">
                    <input type="hidden" name="token" value="<?= $token ?>">
                    <input type="hidden" name="url" value="<?= e($entry['url'] ?? '') ?>">
                    <button type="submit" class="admin-btn-dismiss" title="Dismiss this entry">&times;</button>
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
</div>
</main>

<script>
function prefillRedirect(url) {
  var fromField = document.getElementById('redir-from');
  var toField   = document.getElementById('redir-to');
  if (fromField) {
    fromField.value = url;
    toField.value = '';
    toField.focus();
    fromField.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}
</script>

<?php require PARTIALS_PATH . 'footer.php'; ?>
