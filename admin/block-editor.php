<?php
// Minimal Block JSON Editor for Krisada.com
// Place in /admin/ and protect with .htaccess or similar

$CONTENT_DIR = realpath(__DIR__ . '/../content/pages');
$REPO_ROOT    = realpath(__DIR__ . '/..');
$errors = [];
$success = '';

function list_json_files($dir) {
    $files = [];
    foreach (glob($dir . '/*.json') as $file) {
        $files[] = basename($file);
    }
    return $files;
}

function load_json_file($path) {
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function save_json_file($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents($path, $json . "\n");
}

function block_editor_git_push($abs_path, $repo_root, $commit_msg) {
    $install_path = $repo_root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'install.json';
    if (!is_file($install_path)) {
        return;
    }
    $install = json_decode(file_get_contents($install_path), true);
    $token   = (string) ($install['github_token'] ?? '');
    if ($token === '') {
        return;
    }
    $rel    = str_replace('\\', '/', substr($abs_path, strlen($repo_root) + 1));
    $remote = 'https://' . rawurlencode($token) . '@github.com/KrisadaSEO/www.krisada.com.git';
    $cmd = '('
        . 'cd ' . escapeshellarg($repo_root)
        . ' && git add ' . escapeshellarg($rel)
        . ' && git -c user.email=krisadaseo@gmail.com -c user.name=KrisadaSEO commit -m ' . escapeshellarg('[admin] ' . $commit_msg)
        . ' && git pull --rebase ' . escapeshellarg($remote) . ' main'
        . ' && git push ' . escapeshellarg($remote) . ' main'
        . ') 2>&1';
    exec($cmd, $output, $code);
    if ($code !== 0) {
        error_log('[block-editor git] ' . $commit_msg . ': ' . implode(' | ', $output));
    }
}

$file = $_GET['file'] ?? '';
$filepath = $file ? $CONTENT_DIR . DIRECTORY_SEPARATOR . basename($file) : '';
$data = $filepath && file_exists($filepath) ? load_json_file($filepath) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && $filepath) {
    $blocks = json_decode($_POST['blocks'] ?? '[]', true);
    if (!is_array($blocks)) {
        $errors[] = 'Blocks JSON is invalid.';
    } else {
        $data['blocks'] = $blocks;
        if (save_json_file($filepath, $data) !== false) {
            block_editor_git_push($filepath, $REPO_ROOT, 'save page/' . basename($file, '.json'));
            $success = 'Saved!';
            $data = load_json_file($filepath);
        } else {
            $errors[] = 'Failed to save file.';
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Block JSON Editor</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        textarea { width: 100%; min-height: 300px; font-family: monospace; }
        .block-list { margin: 1em 0; }
        .block { border: 1px solid #ccc; padding: 1em; margin-bottom: 1em; background: #faf9f6; }
        .actions { margin-top: 1em; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
<h1>Block JSON Editor</h1>
<form method="get" action="">
    <label for="file">Select file:</label>
    <select name="file" id="file" onchange="this.form.submit()">
        <option value="">-- Choose --</option>
        <?php foreach (list_json_files($CONTENT_DIR) as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>"<?= $f === $file ? ' selected' : '' ?>><?= htmlspecialchars($f) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($file && $data): ?>
    <h2>Editing: <?= htmlspecialchars($file) ?></h2>
    <?php if ($success): ?><div class="success">✔ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="error">✖ <?= htmlspecialchars($err) ?></div><?php endforeach; ?>
    <form method="post">
        <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
        <label for="blocks">Blocks JSON (edit directly or use the quick add below):</label><br>
        <textarea name="blocks" id="blocks"><?= htmlspecialchars(json_encode($data['blocks'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></textarea>
        <div class="actions">
            <button type="submit" name="save">Save</button>
        </div>
    </form>
    <details style="margin-top:2em;">
        <summary>Quick Add Block (append to JSON above)</summary>
        <form onsubmit="event.preventDefault(); var t=document.getElementById('blocks'); var b=JSON.parse(t.value); b.push(JSON.parse(document.getElementById('quickblock').value)); t.value=JSON.stringify(b,null,2);">
            <select id="blocktype" onchange="document.getElementById('quickblock').value=this.value">
                <option value='{"type":"heading","level":2,"text":"New Heading"}'>Heading</option>
                <option value='{"type":"paragraph","text":"New paragraph."}'>Paragraph</option>
                <option value='{"type":"image","src":"/img/example.jpg","alt":"Alt text"}'>Image</option>
                <option value='{"type":"list","items":["Item 1","Item 2"],"ordered":false}'>List</option>
                <option value='{"type":"quote","text":"Quote text."}'>Quote</option>
                <option value='{"type":"code","code":"echo Hello;","language":"php"}'>Code</option>
                <option value='{"type":"callout","title":"Note","text":"Callout text."}'>Callout</option>
                <option value='{"type":"embed","url":"https://example.com/embed"}'>Embed</option>
            </select>
            <input type="text" id="quickblock" value='{"type":"heading","level":2,"text":"New Heading"}' size="60">
            <button>Add Block</button>
        </form>
    </details>
<?php elseif ($file): ?>
    <div class="error">File not found or invalid JSON.</div>
<?php endif; ?>
</body>
</html>
