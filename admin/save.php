<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: save.php');
    exit;
}

define('SAVE_PASSWORD', 'Sumaleerk5124!');
define('REPO', '/home/webserver005/public_html/krisada.com');

$loggedIn = isset($_SESSION['git_auth']);
$output   = '';
$status   = '';

if (!$loggedIn && isset($_POST['password'])) {
    if ($_POST['password'] === SAVE_PASSWORD) {
        $_SESSION['git_auth'] = true;
        $loggedIn = true;
    } else {
        $status = 'wrong';
    }
}

function git_run($command) {
    $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
    $env = ['HOME' => '/home/webserver005', 'PATH' => '/usr/local/bin:/usr/bin:/bin'];
    $proc = proc_open($command, $descriptors, $pipes, null, $env);
    if (!is_resource($proc)) return ['could not start process', 1];
    fclose($pipes[0]);
    $out  = stream_get_contents($pipes[1]);
    $out .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [trim($out), proc_close($proc)];
}

if ($loggedIn && isset($_POST['message'])) {
    $repo = REPO;
    $msg  = escapeshellarg(trim($_POST['message']) ?: 'live edit');
    $lines = [];

    [$out, ]            = git_run("git -C $repo add -A");        $lines[] = $out;
    [$out, $commitCode] = git_run("git -C $repo commit -m $msg"); $lines[] = $out;

    $flat = implode(' ', $lines);
    $nothingToCommit = strpos($flat, 'nothing to commit') !== false
                    || strpos($flat, 'nothing added to commit') !== false;

    if ($nothingToCommit) {
        $status = 'nothing';
    } elseif ($commitCode === 0) {
        [$out, $pushCode] = git_run("git -C $repo push git@github.com:KrisadaSEO/www.krisada.com main");
        $lines[]  = $out;
        $status = ($pushCode === 0) ? 'success' : 'error';
    } else {
        $status = 'error';
    }

    $output = implode("\n", array_filter($lines)) ?: '(no output from git)';

    $_SESSION['flash_status'] = $status;
    $_SESSION['flash_output'] = $output;
    header('Location: save.php');
    exit;
}

$status = $_SESSION['flash_status'] ?? '';
$output = $_SESSION['flash_output'] ?? '';
unset($_SESSION['flash_status'], $_SESSION['flash_output']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Save to GitHub</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0f0f0f; color: #e0e0e0; font-family: system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
  .card { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 12px; padding: 40px; width: 100%; max-width: 520px; }
  h1 { font-size: 1.2rem; font-weight: 600; color: #fff; margin-bottom: 6px; }
  .sub { font-size: 0.82rem; color: #555; margin-bottom: 28px; }
  label { display: block; font-size: 0.75rem; color: #777; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
  input { width: 100%; background: #111; border: 1px solid #2e2e2e; border-radius: 8px; padding: 12px 16px; color: #fff; font-size: 1rem; outline: none; }
  input:focus { border-color: #444; }
  button { width: 100%; margin-top: 14px; padding: 14px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
  button:hover { background: #1d4ed8; }
  .output { margin-top: 24px; background: #111; border: 1px solid #1e1e1e; border-radius: 8px; padding: 16px; font-family: monospace; font-size: 0.78rem; white-space: pre-wrap; line-height: 1.7; color: #888; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; margin-bottom: 12px; }
  .success { background: #14532d; color: #4ade80; }
  .error   { background: #450a0a; color: #f87171; }
  .nothing { background: #1c1917; color: #78716c; }
  .wrong   { background: #450a0a; color: #f87171; margin-bottom: 16px; }
  .logout  { margin-top: 20px; text-align: right; }
  .logout a { font-size: 0.78rem; color: #444; text-decoration: none; }
  .logout a:hover { color: #777; }
</style>
</head>
<body>
<div class="card">
  <h1>Save to GitHub</h1>
  <p class="sub">Krisada.com &mdash; push live edits to main</p>

  <?php if (!$loggedIn): ?>

    <?php if ($status === 'wrong'): ?>
      <div class="output"><span class="badge wrong">Wrong password</span></div>
    <?php endif; ?>

    <form method="post">
      <label>Password</label>
      <input type="password" name="password" autofocus autocomplete="current-password">
      <button type="submit">Unlock</button>
    </form>

  <?php else: ?>

    <form method="post">
      <label>What did you change?</label>
      <input type="text" name="message" placeholder="e.g. update .htaccess redirect" autofocus>
      <button type="submit">Save to GitHub</button>
    </form>

    <?php if ($status): ?>
      <div class="output">
        <?php if ($status === 'success'): ?>
          <span class="badge success">Pushed to main</span>
        <?php elseif ($status === 'nothing'): ?>
          <span class="badge nothing">Nothing to commit</span>
        <?php elseif ($status === 'error'): ?>
          <span class="badge error">Error ... see below</span>
        <?php endif; ?>
        <?= htmlspecialchars($output) ?>
      </div>
    <?php endif; ?>

    <div class="logout"><a href="?logout=1">Log out</a></div>

  <?php endif; ?>
</div>
</body>
</html>
