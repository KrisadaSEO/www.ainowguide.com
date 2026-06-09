<?php require PARTIALS_PATH . 'admin-nav.php'; ?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Redirects &amp; 404 Log</h1>
    </div>

    <?php if (!empty($redirects_success)): ?>
    <div class="admin-alert admin-alert--success"><?= e($redirects_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($redirects_error)): ?>
    <div class="admin-alert admin-alert--error"><?= e($redirects_error) ?></div>
    <?php endif; ?>

    <!-- Add redirect form -->
    <div class="admin-field-group">
      <div class="admin-field-group__heading">Add Redirect</div>
      <form method="POST" action="/admin/redirects">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="admin-form-inline">
          <input type="text" name="from" class="admin-input" placeholder="/old-path" required>
          <input type="text" name="to"   class="admin-input" placeholder="/new-path or URL" required>
          <select name="type" class="admin-input" style="width:auto;">
            <option value="301">301</option>
            <option value="302">302</option>
          </select>
          <button type="submit" class="btn btn--primary btn--sm">Add</button>
        </div>
      </form>
    </div>

    <!-- Redirects list -->
    <?php if (!empty($redirects_list)): ?>
    <div class="admin-section">
      <h2 class="admin-section-heading">Active Redirects (<?= count($redirects_list) ?>)</h2>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>From</th><th>To</th><th>Type</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($redirects_list as $rule): ?>
            <tr>
              <td><code><?= e($rule['from'] ?? '') ?></code></td>
              <td><code><?= e($rule['to'] ?? '') ?></code></td>
              <td><?= (int) ($rule['type'] ?? 301) ?></td>
              <td>
                <form method="POST" action="/admin/redirects" style="display:inline;">
                  <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="redirect_id" value="<?= e($rule['id'] ?? '') ?>">
                  <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--ang-red);">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- 404 log -->
    <?php if (!empty($log_404)): ?>
    <div class="admin-section">
      <h2 class="admin-section-heading">404 Log (<?= count($log_404) ?> unique URLs)</h2>
      <form method="POST" action="/admin/redirects" style="margin-bottom:var(--space-4);">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
        <input type="hidden" name="action" value="purge404">
        <div class="admin-form-inline">
          <label style="font-size:var(--text-xs);color:var(--ang-text-faint);">Purge entries with fewer than</label>
          <input type="number" name="min_hits" class="admin-input" value="2" min="1" style="width:70px;">
          <label style="font-size:var(--text-xs);color:var(--ang-text-faint);">hits</label>
          <button type="submit" class="btn btn--ghost btn--sm">Purge</button>
        </div>
      </form>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>URL</th><th>Hits</th><th>Last hit</th><th></th></tr></thead>
          <tbody>
            <?php foreach (array_slice($log_404, 0, 50) as $row): ?>
            <tr>
              <td><code><?= e($row['url'] ?? '') ?></code></td>
              <td><?= (int) ($row['hits'] ?? 0) ?></td>
              <td><?= e($row['last_hit'] ?? '') ?></td>
              <td>
                <form method="POST" action="/admin/redirects" style="display:inline;">
                  <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
                  <input type="hidden" name="action" value="remove404">
                  <input type="hidden" name="url" value="<?= e($row['url'] ?? '') ?>">
                  <button type="submit" class="btn btn--ghost btn--sm">Remove</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>
</div>
</body>
</html>
