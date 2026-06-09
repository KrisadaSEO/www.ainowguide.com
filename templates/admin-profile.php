<?php require PARTIALS_PATH . 'admin-nav.php'; ?>

<main class="admin-main">
  <div class="admin-container" style="max-width:480px;">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Profile</h1>
      <p class="admin-page-desc">Logged in as <strong><?= e(admin_current_user()['displayName'] ?? '') ?></strong></p>
    </div>

    <?php if (!empty($profile_success)): ?>
    <div class="admin-alert admin-alert--success"><?= e($profile_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($profile_error)): ?>
    <div class="admin-alert admin-alert--error"><?= e($profile_error) ?></div>
    <?php endif; ?>

    <div class="admin-field-group">
      <div class="admin-field-group__heading">Change Password</div>
      <form method="POST" action="/admin/profile">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
        <div class="admin-form-group">
          <label class="admin-label" for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="admin-input" autocomplete="current-password">
        </div>
        <div class="admin-form-group">
          <label class="admin-label" for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" class="admin-input" autocomplete="new-password">
        </div>
        <div class="admin-form-actions">
          <button type="submit" class="btn btn--primary">Update Password</button>
        </div>
      </form>
    </div>

  </div>
</main>
</div>
</body>
</html>
