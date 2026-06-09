<?php require PARTIALS_PATH . 'admin-nav.php'; ?>

<main class="admin-main">
  <div class="admin-container" style="max-width:780px;">

    <div class="admin-page-header">
      <h1 class="admin-page-title"><?= e($content_entry['title'] ?? 'Edit Content') ?></h1>
      <p class="admin-page-desc">
        <span class="admin-chip admin-chip--violet"><?= e($content_entry['type_label'] ?? '') ?></span>
        &nbsp;<?= e($content_entry['slug'] ?? '') ?>
        <?php if (!empty($content_entry['public_url'])): ?>
        &mdash; <a href="<?= e($content_entry['public_url']) ?>" target="_blank" rel="noopener" style="font-size:var(--text-xs);">View live &rarr;</a>
        <?php endif; ?>
      </p>
    </div>

    <?php if (!empty($content_success)): ?>
    <div class="admin-alert admin-alert--success"><?= e($content_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($content_error)): ?>
    <div class="admin-alert admin-alert--error"><?= e($content_error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/content/<?= e($content_entry['type']) ?>/<?= e($content_entry['slug']) ?>">
      <input type="hidden" name="_csrf" value="<?= e(admin_csrf_token()) ?>">

      <?php
      $field_groups = $content_entry['field_groups'] ?? [];
      $field_index  = 0;
      foreach ($field_groups as $group_name => $fields):
      ?>
      <div class="admin-field-group">
        <div class="admin-field-group__heading"><?= e($group_name) ?></div>

        <?php foreach ($fields as $field): ?>
        <?php
        $input_name_path  = 'field_paths[' . $field_index . ']';
        $input_name_kind  = 'field_kinds[' . $field_index . ']';
        $input_name_value = 'fields[' . $field_index . ']';
        $input_id         = 'field-' . e($field['key']);
        ?>
        <div class="admin-form-group">
          <label class="admin-label" for="<?= $input_id ?>"><?= e($field['label']) ?></label>
          <input type="hidden" name="<?= $input_name_path ?>" value="<?= e($field['path']) ?>">
          <input type="hidden" name="<?= $input_name_kind ?>" value="<?= e($field['kind']) ?>">

          <?php if ($field['input'] === 'textarea'): ?>
          <textarea id="<?= $input_id ?>" name="<?= $input_name_value ?>" class="admin-textarea"><?= e((string) ($field['value'] ?? '')) ?></textarea>

          <?php elseif ($field['input'] === 'checkbox'): ?>
          <div class="admin-checkbox-row">
            <input type="checkbox" id="<?= $input_id ?>" name="<?= $input_name_value ?>" value="1"<?= !empty($field['value']) ? ' checked' : '' ?>>
            <label for="<?= $input_id ?>" style="font-family:var(--font-serif);font-size:var(--text-sm);color:var(--ang-text-muted);"><?= e($field['label']) ?></label>
          </div>

          <?php elseif ($field['input'] === 'number'): ?>
          <input type="number" id="<?= $input_id ?>" name="<?= $input_name_value ?>" class="admin-input" value="<?= e((string) ($field['value'] ?? '')) ?>">

          <?php elseif ($field['input'] === 'date'): ?>
          <input type="date" id="<?= $input_id ?>" name="<?= $input_name_value ?>" class="admin-input" value="<?= e((string) ($field['value'] ?? '')) ?>">

          <?php else: ?>
          <input type="text" id="<?= $input_id ?>" name="<?= $input_name_value ?>" class="admin-input" value="<?= e((string) ($field['value'] ?? '')) ?>">
          <?php endif; ?>
        </div>

        <?php $field_index++; ?>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>

      <div class="admin-form-group">
        <label class="admin-label" for="commit-message">Commit Message (optional)</label>
        <input type="text" id="commit-message" name="commit_message" class="admin-input" placeholder="e.g. update session summary">
      </div>

      <div class="admin-form-actions">
        <button type="submit" class="btn btn--primary">Save Changes</button>
        <a href="/admin/content?type=<?= e($content_entry['type']) ?>" class="btn btn--ghost">Cancel</a>
      </div>
    </form>

  </div>
</main>
</div>
</body>
</html>
