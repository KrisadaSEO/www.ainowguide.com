<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title ?? 'Login') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body class="admin-body admin-body--login">
<div class="admin-login-wrap">
  <div class="admin-login-box">
    <div class="admin-login-brand">
      <span class="admin-login-brand__name">AI Now Guide</span>
      <span class="admin-login-brand__label">Admin</span>
    </div>

    <?php if (!empty($login_error)): ?>
    <div class="admin-alert admin-alert--error"><?= e($login_error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/login">
      <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">
      <div class="admin-form-group">
        <label class="admin-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="admin-input" autocomplete="username" autofocus>
      </div>
      <div class="admin-form-group">
        <label class="admin-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="admin-input" autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn--primary" style="width:100%;">Log In</button>
    </form>
  </div>
</div>
</body>
</html>
