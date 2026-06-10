<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile ... AI Now Guide Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Profile</h1>
      <p class="admin-page-desc">Manage your account credentials.</p>
    </div>

    <!-- ── Account info ─────────────────────────────────────────────────── -->
    <section class="admin-panel">
      <h2 class="admin-panel__heading">Account</h2>
      <div class="admin-profile-info">
        <div class="admin-profile-row">
          <span class="admin-profile-row__label">Username</span>
          <span class="admin-profile-row__value admin-table__mono"><?= e($current_user['username'] ?? '') ?></span>
        </div>
        <div class="admin-profile-row">
          <span class="admin-profile-row__label">Display Name</span>
          <span class="admin-profile-row__value"><?= e($current_user['displayName'] ?? '') ?></span>
        </div>
        <div class="admin-profile-row">
          <span class="admin-profile-row__label">Logged In</span>
          <span class="admin-profile-row__value admin-table__muted"><?= e($current_user['logged_in_at'] ?? '') ?></span>
        </div>
      </div>
    </section>

    <!-- ── Change password ──────────────────────────────────────────────── -->
    <section class="admin-panel">
      <h2 class="admin-panel__heading">Change Password</h2>
      <p class="admin-panel__desc">Minimum 8 characters. You will remain logged in after changing your password.</p>

      <?php if (!empty($profile_success)): ?>
        <div class="admin-alert admin-alert--success" role="alert"><?= e($profile_success) ?></div>
      <?php endif; ?>

      <?php if (!empty($profile_error)): ?>
        <div class="admin-alert admin-alert--error" role="alert"><?= e($profile_error) ?></div>
      <?php endif; ?>

      <form method="post" action="/admin/profile" class="admin-form" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">

        <div class="admin-form-field">
          <label for="current_password" class="admin-form-label">Current Password</label>
          <input type="password" id="current_password" name="current_password"
                 class="admin-form-input" required autocomplete="current-password">
        </div>

        <div class="admin-form-field">
          <label for="new_password" class="admin-form-label">New Password</label>
          <input type="password" id="new_password" name="new_password"
                 class="admin-form-input" required minlength="8" autocomplete="new-password">
        </div>

        <div class="admin-form-field">
          <label for="confirm_password" class="admin-form-label">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 class="admin-form-input" required minlength="8" autocomplete="new-password"
                 oninput="this.setCustomValidity(this.value!==document.getElementById('new_password').value?'Passwords do not match.':'')">
        </div>

        <div class="admin-form-actions">
          <button type="submit" class="btn btn--gold">Update Password</button>
        </div>
      </form>
    </section>

    <!-- ── Session ──────────────────────────────────────────────────────── -->
    <section class="admin-panel admin-panel--danger">
      <h2 class="admin-panel__heading">Session</h2>
      <p class="admin-panel__desc">Log out of the admin panel and clear your session.</p>
      <a href="/admin/logout" class="btn btn--ghost">Log Out →</a>
    </section>

  </div>
</main>

</body>
</html>
