<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Content ... Krisada.com Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php require site_root_path('templates/partials/admin-nav.php'); ?>

<?php
$has_visual_fields = false;
foreach (($content_entry['field_groups'] ?? []) as $group_fields) {
    foreach ($group_fields as $field) {
        if (($field['input'] ?? '') === 'richtext') {
            $has_visual_fields = true;
            break 2;
        }
    }
}
?>

<main class="admin-main">
  <div class="admin-container">

    <div class="admin-page-header">
      <h1 class="admin-page-title"><?= e($content_entry['title'] ?? 'Edit Content') ?></h1>
      <p class="admin-page-desc">
        <?= e($content_entry['type_label'] ?? 'Content') ?> &mdash; <span class="admin-table__mono"><?= e($content_entry['slug'] ?? '') ?></span>.
        <?php if (!empty($content_entry['public_url'])): ?>
          <a href="<?= e($content_entry['public_url']) ?>" target="_blank" rel="noopener">View live page</a>.
        <?php endif; ?>
      </p>
    </div>

    <?php if (!empty($content_success)): ?>
      <div class="admin-alert admin-alert--success" role="alert"><?= e($content_success) ?></div>
    <?php endif; ?>

    <?php if (!empty($content_error)): ?>
      <div class="admin-alert admin-alert--error" role="alert"><?= e($content_error) ?></div>
    <?php endif; ?>

    <?php if ($has_visual_fields): ?>
      <div class="admin-alert admin-alert--info" role="status">
        Long body sections use a visual paragraph editor. <code>Enter</code> creates a new paragraph,
        <code>Shift+Enter</code> inserts a line break. Text saves back to JSON as plain text.
      </div>
    <?php endif; ?>

    <form method="post"
          action="/admin/content/<?= e($content_entry['type'] ?? '') ?>/<?= e($content_entry['slug'] ?? '') ?>"
          class="admin-editor-form">
      <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">

      <?php foreach (($content_entry['field_groups'] ?? []) as $group_label => $group_fields): ?>
        <section class="admin-panel">
          <h2 class="admin-panel__heading"><?= e($group_label) ?></h2>
          <div class="admin-editor-grid">
            <?php foreach ($group_fields as $field): ?>
              <?php
                $field_key   = $field['key'];
                $field_input = $field['input'];
                $field_kind  = $field['kind'];
                $field_value = $field['value'];
                $display_value = $field_kind === 'list'
                  ? implode("\n", is_array($field_value) ? $field_value : [])
                  : ($field_kind === 'bool' ? '1' : (string) ($field_value ?? ''));
              ?>
              <div class="admin-form-field<?= in_array($field_input, ['textarea', 'richtext'], true) ? ' admin-form-field--full' : '' ?>">
                <label for="<?= e($field_key) ?>" class="admin-form-label"><?= e($field['label']) ?></label>
                <input type="hidden" name="field_paths[<?= e($field_key) ?>]" value="<?= e($field['path']) ?>">
                <input type="hidden" name="field_kinds[<?= e($field_key) ?>]" value="<?= e($field_kind) ?>">

                <?php if ($field_input === 'richtext'): ?>
                  <div class="admin-richtext" data-richtext-editor>
                    <div class="admin-richtext__modes" data-richtext-modes hidden>
                      <button type="button" class="admin-richtext__mode-btn is-active" data-richtext-mode="visual">Visual</button>
                      <button type="button" class="admin-richtext__mode-btn" data-richtext-mode="source">Source</button>
                    </div>
                    <div class="admin-richtext__visual" data-richtext-visual hidden>
                      <p class="admin-richtext__hint">Paragraph spacing is preserved when saved back to JSON.</p>
                      <div
                        id="<?= e($field_key) ?>_visual"
                        class="admin-richtext__editor"
                        contenteditable="true"
                        spellcheck="true"
                        data-richtext-input
                      ></div>
                    </div>
                    <textarea
                      id="<?= e($field_key) ?>"
                      name="fields[<?= e($field_key) ?>]"
                      class="admin-form-textarea admin-richtext__source"
                      rows="14"
                      data-richtext-source
                    ><?= e($display_value) ?></textarea>
                  </div>

                <?php elseif ($field_input === 'textarea'): ?>
                  <textarea
                    id="<?= e($field_key) ?>"
                    name="fields[<?= e($field_key) ?>]"
                    class="admin-form-textarea"
                    rows="<?= $field_kind === 'list' ? '5' : '6' ?>"
                  ><?= e($display_value) ?></textarea>

                <?php elseif ($field_input === 'checkbox'): ?>
                  <label class="admin-checkbox">
                    <input
                      id="<?= e($field_key) ?>"
                      type="checkbox"
                      name="fields[<?= e($field_key) ?>]"
                      value="1"
                      <?= !empty($field_value) ? 'checked' : '' ?>
                    >
                    <span>Enabled</span>
                  </label>

                <?php elseif ($field_input === 'select-status'): ?>
                  <select
                    id="<?= e($field_key) ?>"
                    name="fields[<?= e($field_key) ?>]"
                    class="admin-form-input admin-form-select"
                  >
                    <option value="published"<?= $display_value === 'published' ? ' selected' : '' ?>>Published</option>
                    <option value="draft"<?= $display_value === 'draft' ? ' selected' : '' ?>>Draft</option>
                  </select>

                <?php elseif ($field_input === 'select-author-multi'): ?>
                  <?php
                    $author_options  = ['krisada', 'kodi', 'atlas'];
                    $current_authors = is_array($field_value)
                        ? array_map('strtolower', $field_value)
                        : ($display_value !== '' ? [strtolower(trim($display_value))] : []);
                  ?>
                  <input
                    type="hidden"
                    id="<?= e($field_key) ?>"
                    name="fields[<?= e($field_key) ?>]"
                    value="<?= e(implode("\n", $current_authors)) ?>"
                  >
                  <div class="admin-author-checkboxes" data-author-multi="<?= e($field_key) ?>">
                    <?php foreach ($author_options as $a_slug):
                      $a_data     = site_get_author($a_slug);
                      if ($a_data === null) continue;
                      $a_checked  = in_array($a_slug, $current_authors, true);
                    ?>
                    <label class="admin-author-option<?= $a_checked ? ' is-selected' : '' ?>">
                      <input type="checkbox" value="<?= e($a_slug) ?>"<?= $a_checked ? ' checked' : '' ?>>
                      <?php if (!empty($a_data['avatar'])): ?>
                      <img src="<?= e($a_data['avatar']) ?>" alt="" class="admin-author-option__avatar" loading="lazy">
                      <?php endif; ?>
                      <span class="admin-author-option__info">
                        <strong><?= e($a_data['name'] ?? $a_slug) ?></strong>
                        <span><?= e($a_data['role'] ?? '') ?></span>
                      </span>
                    </label>
                    <?php endforeach; ?>
                  </div>

                <?php else: ?>
                  <input
                    id="<?= e($field_key) ?>"
                    type="<?= e($field_input) ?>"
                    name="fields[<?= e($field_key) ?>]"
                    value="<?= e($display_value) ?>"
                    class="admin-form-input"
                  >
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>

      <div class="admin-form-actions">
        <button type="submit" class="btn btn--gold">Save Changes</button>
        <a href="/admin/content" class="btn btn--ghost">Back to Content</a>
      </div>
    </form>

  </div>
</main>

<?php if ($has_visual_fields): ?>
<script>
(() => {
  const wrappers = Array.from(document.querySelectorAll('[data-richtext-editor]'));
  if (!wrappers.length) return;

  const esc = (v) => v.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');

  const textToHtml = (value) => {
    const s = String(value||'').replace(/\r\n?/g,'\n').trim();
    if (!s) return '<p><br></p>';
    return s.split(/\n{2,}/).map(p=>`<p>${esc(p).replace(/\n/g,'<br>')}</p>`).join('');
  };

  const readNode = (node) => {
    if (node.nodeType===Node.TEXT_NODE) return node.nodeValue||'';
    if (node.nodeType!==Node.ELEMENT_NODE) return '';
    const tag = node.nodeName.toLowerCase();
    if (tag==='br') return '\n';
    let text='';
    node.childNodes.forEach(c=>{ text+=readNode(c); });
    if (tag==='li') text=`- ${text.trim()}`;
    if (['p','div','section','article','blockquote','ul','ol','li','h1','h2','h3','h4','h5','h6'].includes(tag))
      return text.trim()===''?'':`${text.trim()}\n\n`;
    return text;
  };

  const htmlToText = (html) => {
    const d=document.createElement('div');
    d.innerHTML=html;
    let out='';
    d.childNodes.forEach(c=>{ out+=readNode(c); });
    return out.replace(/ /g,' ').replace(/[ \t]+\n/g,'\n').replace(/\n{3,}/g,'\n\n').trim();
  };

  const insertPlain = (text) => {
    const sel=window.getSelection();
    if (!sel||sel.rangeCount===0) return;
    const r=sel.getRangeAt(0);
    r.deleteContents();
    const tn=document.createTextNode(text);
    r.insertNode(tn);
    r.setStartAfter(tn); r.setEndAfter(tn);
    sel.removeAllRanges(); sel.addRange(r);
  };

  const syncWrapper = (wrapper) => {
    const src=wrapper.querySelector('[data-richtext-source]');
    const ed=wrapper.querySelector('[data-richtext-input]');
    if (!src||!ed||wrapper.dataset.richtextMode==='source') return;
    src.value=htmlToText(ed.innerHTML);
  };

  const setMode = (wrapper, mode) => {
    const src=wrapper.querySelector('[data-richtext-source]');
    const vis=wrapper.querySelector('[data-richtext-visual]');
    const ed=wrapper.querySelector('[data-richtext-input]');
    const btns=wrapper.querySelectorAll('[data-richtext-mode]');
    if (!src||!vis||!ed) return;
    if (mode!=='source') { ed.innerHTML=textToHtml(src.value); } else { syncWrapper(wrapper); }
    wrapper.dataset.richtextMode=mode;
    vis.hidden=mode==='source'; src.hidden=mode!=='source';
    btns.forEach(b=>b.classList.toggle('is-active',b.getAttribute('data-richtext-mode')===mode));
  };

  wrappers.forEach((wrapper) => {
    const modes=wrapper.querySelector('[data-richtext-modes]');
    const src=wrapper.querySelector('[data-richtext-source]');
    const ed=wrapper.querySelector('[data-richtext-input]');
    if (!modes||!src||!ed) return;
    modes.hidden=false;
    setMode(wrapper,'visual');
    wrapper.querySelectorAll('[data-richtext-mode]').forEach(b=>{
      b.addEventListener('click',()=>setMode(wrapper,b.getAttribute('data-richtext-mode')||'visual'));
    });
    ed.addEventListener('input',()=>syncWrapper(wrapper));
    ed.addEventListener('paste',(ev)=>{
      ev.preventDefault();
      insertPlain((ev.clipboardData||window.clipboardData).getData('text/plain'));
      syncWrapper(wrapper);
    });
  });

  document.querySelectorAll('.admin-editor-form').forEach(form=>{
    form.addEventListener('submit',()=>wrappers.forEach(syncWrapper));
  });
})();
</script>
<?php endif; ?>

<script>
(() => {
  document.querySelectorAll('[data-author-multi]').forEach(wrapper => {
    const hidden = document.getElementById(wrapper.dataset.authorMulti);
    const boxes  = Array.from(wrapper.querySelectorAll('input[type=checkbox]'));
    function sync() {
      hidden.value = boxes.filter(b => b.checked).map(b => b.value).join('\n');
      wrapper.querySelectorAll('label').forEach(lbl => {
        lbl.classList.toggle('is-selected', lbl.querySelector('input').checked);
      });
    }
    boxes.forEach(b => b.addEventListener('change', sync));
  });
})();
</script>

</body>
</html>
