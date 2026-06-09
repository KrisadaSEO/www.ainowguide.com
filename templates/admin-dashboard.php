<?php require PARTIALS_PATH . 'admin-nav.php'; ?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Dashboard</h1>
      <p class="admin-page-desc">AI Now Guide &mdash; Content &amp; Site Management</p>
    </div>

    <?php
    $channels_count = count(get_all_channels(false));
    $sessions_count = count(get_all_sessions(false));
    $pub_sessions   = count(get_all_sessions(true));
    ?>

    <div class="admin-stats-grid">
      <div class="admin-stat-card">
        <div class="admin-stat-card__num"><?= $channels_count ?></div>
        <div class="admin-stat-card__label">Channels</div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-card__num"><?= $pub_sessions ?></div>
        <div class="admin-stat-card__label">Published Sessions</div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-card__num"><?= $sessions_count - $pub_sessions ?></div>
        <div class="admin-stat-card__label">Draft Sessions</div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-card__num"><?= cms_db_backend_label() ?></div>
        <div class="admin-stat-card__label">Storage Backend</div>
      </div>
    </div>

    <div class="admin-quick-links">
      <h2 class="admin-section-heading">Quick Actions</h2>
      <div class="admin-quick-grid">
        <a href="/admin/content?type=session" class="admin-quick-card">
          <div class="admin-quick-card__title">Manage Sessions</div>
          <div class="admin-quick-card__desc">Edit session titles, summaries, dates, and publish status.</div>
        </a>
        <a href="/admin/content?type=channel" class="admin-quick-card">
          <div class="admin-quick-card__title">Manage Channels</div>
          <div class="admin-quick-card__desc">Update channel descriptions, icons, and sort order.</div>
        </a>
        <a href="/admin/content?type=page" class="admin-quick-card">
          <div class="admin-quick-card__title">Edit Pages</div>
          <div class="admin-quick-card__desc">Update homepage hero, about page copy, and meta tags.</div>
        </a>
        <a href="/admin/settings" class="admin-quick-card">
          <div class="admin-quick-card__title">Site Settings</div>
          <div class="admin-quick-card__desc">Site name, tagline, navigation, and contact info.</div>
        </a>
        <a href="/admin/redirects" class="admin-quick-card">
          <div class="admin-quick-card__title">Redirects &amp; 404s</div>
          <div class="admin-quick-card__desc">Manage URL redirects and review 404 log.</div>
        </a>
      </div>
    </div>

    <?php if (!empty($log_404)): ?>
    <div class="admin-section">
      <h2 class="admin-section-heading">Recent 404s</h2>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>URL</th><th>Hits</th><th>Last hit</th></tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($log_404, 0, 10) as $row): ?>
            <tr>
              <td><code><?= e($row['url'] ?? '') ?></code></td>
              <td><?= (int) ($row['hits'] ?? 0) ?></td>
              <td><?= e($row['last_hit'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <a href="/admin/redirects" class="btn btn--ghost btn--sm">Manage Redirects &rarr;</a>
    </div>
    <?php endif; ?>

    <!-- Git push from VPS -->
    <div class="admin-section">
      <h2 class="admin-section-heading">Push to GitHub</h2>
      <form method="POST" action="/admin/dashboard" id="git-push-form">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
        <input type="hidden" name="action" value="git_push">
        <div class="admin-form-inline">
          <input type="text" name="commit_message" class="admin-input" placeholder="Commit message (optional)">
          <button type="submit" class="btn btn--secondary btn--sm">Push</button>
        </div>
      </form>
    </div>

  </div>
</main>
</div>
</body>
</html>
