<?php require PARTIALS_PATH . 'admin-nav.php'; ?>

<main class="admin-main">
  <div class="admin-container" style="max-width:640px;">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Site Settings</h1>
    </div>

    <?php if (!empty($settings_success)): ?>
    <div class="admin-alert admin-alert--success"><?= e($settings_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($settings_error)): ?>
    <div class="admin-alert admin-alert--error"><?= e($settings_error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/settings">
      <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">

      <div class="admin-field-group">
        <div class="admin-field-group__heading">Site Identity</div>
        <div class="admin-form-group">
          <label class="admin-label" for="site_name">Site Name</label>
          <input type="text" id="site_name" name="site_name" class="admin-input" value="<?= e($settings_data['site']['name'] ?? '') ?>">
        </div>
        <div class="admin-form-group">
          <label class="admin-label" for="site_tagline">Tagline</label>
          <input type="text" id="site_tagline" name="site_tagline" class="admin-input" value="<?= e($settings_data['site']['tagline'] ?? '') ?>">
        </div>
        <div class="admin-form-group">
          <label class="admin-label" for="site_email">Contact Email</label>
          <input type="email" id="site_email" name="site_email" class="admin-input" value="<?= e($settings_data['site']['email'] ?? '') ?>">
        </div>
      </div>

      <div class="admin-form-actions">
        <button type="submit" class="btn btn--primary">Save Settings</button>
      </div>
    </form>

  </div>
</main>
</div>
</body>
</html>
