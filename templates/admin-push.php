<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Push to GitHub ... Krisada.com Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title">Push to GitHub</h1>
      <p class="admin-page-desc">Commit all unsaved changes and push to the main branch.</p>
    </div>

    <section class="admin-panel">
      <h2 class="admin-panel__heading">Save All Changes</h2>
      <p class="admin-panel__desc">Stages every modified file, creates a commit, and pushes to GitHub. GitHub Actions will deploy automatically.</p>

      <?php if (!empty($push_success)): ?>
        <div class="admin-alert admin-alert--success" role="alert"><?= e($push_success) ?></div>
      <?php endif; ?>

      <?php if (!empty($push_error)): ?>
        <div class="admin-alert admin-alert--error" role="alert"><?= e($push_error) ?></div>
      <?php endif; ?>

      <form method="post" action="/admin/push" class="admin-form">
        <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">

        <div class="admin-form-field">
          <label for="commit_message" class="admin-form-label">Commit Message</label>
          <input type="text" id="commit_message" name="commit_message"
                 class="admin-form-input" placeholder="admin: live edit" autofocus>
        </div>

        <div class="admin-form-actions">
          <button type="submit" class="btn btn--gold">↑ Push to GitHub</button>
        </div>
      </form>
    </section>

  </div>
</main>

</body>
</html>
