<?php
$fields         = $form_config['fields'] ?? [];
$inquiry_types  = $form_config['inquiry_types'] ?? [];
$stages         = $form_config['stages'] ?? [];
$honeypot_field = $form_config['spam_protection']['honeypot_field'] ?? 'company';
$option_sets    = [
    'inquiry_type' => $inquiry_types,
    'stage'        => $stages,
];
$form_action = (string) ($form_state['form_action'] ?? '/contact/');
$intent = (string) ($form_state['intent'] ?? '');
?>
<form class="contact-form" method="post" action="<?= site_e($form_action) ?>" novalidate>
  <input type="hidden" name="form_started" value="<?= site_e($form_started) ?>">
  <?php if ($intent !== ''): ?>
    <input type="hidden" name="intent" value="<?= site_e($intent) ?>">
  <?php endif; ?>

  <div class="contact-form__honeypot" aria-hidden="true">
    <label for="cf-<?= site_e($honeypot_field) ?>">Company</label>
    <input
      type="text"
      id="cf-<?= site_e($honeypot_field) ?>"
      name="<?= site_e($honeypot_field) ?>"
      value=""
      tabindex="-1"
      autocomplete="off"
    >
  </div>

  <div class="contact-form__grid">
    <?php foreach ($fields as $field): ?>
      <?php
      $name         = (string) ($field['name'] ?? '');
      $type         = (string) ($field['type'] ?? 'text');
      $label        = (string) ($field['label'] ?? $name);
      $placeholder  = (string) ($field['placeholder'] ?? '');
      $help         = (string) ($field['help'] ?? '');
      $required     = !empty($field['required']);
      $max_length   = (int) ($field['max_length'] ?? 0);
      $rows         = max(3, (int) ($field['rows'] ?? 4));
      $autocomplete = (string) ($field['autocomplete'] ?? '');
      $value        = (string) ($form_values[$name] ?? '');
      $error        = (string) ($form_errors[$name] ?? '');
      $field_id     = 'cf-' . $name;
      $field_class  = 'contact-form__field';
      if ($type === 'textarea') {
          $field_class .= ' contact-form__field--full';
      }
      ?>
      <div class="<?= $field_class ?>">
        <label class="contact-form__label" for="<?= site_e($field_id) ?>">
          <?= site_e($label) ?><?php if ($required): ?> <span aria-hidden="true">*</span><?php endif; ?>
        </label>

        <?php if ($type === 'textarea'): ?>
          <textarea
            class="contact-form__control contact-form__control--textarea<?= $error ? ' contact-form__control--error' : '' ?>"
            id="<?= site_e($field_id) ?>"
            name="<?= site_e($name) ?>"
            rows="<?= $rows ?>"
            placeholder="<?= site_e($placeholder) ?>"
            <?= $required ? 'required' : '' ?>
            <?= $max_length > 0 ? 'maxlength="' . $max_length . '"' : '' ?>
            aria-invalid="<?= $error ? 'true' : 'false' ?>"
            aria-describedby="<?= site_e($field_id) ?>-meta"
          ><?= site_e($value) ?></textarea>
        <?php elseif ($type === 'select'): ?>
          <select
            class="contact-form__control<?= $error ? ' contact-form__control--error' : '' ?>"
            id="<?= site_e($field_id) ?>"
            name="<?= site_e($name) ?>"
            <?= $required ? 'required' : '' ?>
            aria-invalid="<?= $error ? 'true' : 'false' ?>"
            aria-describedby="<?= site_e($field_id) ?>-meta"
          >
            <option value=""><?= site_e($placeholder) ?></option>
            <?php foreach ($option_sets[$name] ?? [] as $option): ?>
              <?php $option_value = (string) ($option['value'] ?? ''); ?>
              <option value="<?= site_e($option_value) ?>" <?= $value === $option_value ? 'selected' : '' ?>>
                <?= site_e((string) ($option['label'] ?? $option_value)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input
            class="contact-form__control<?= $error ? ' contact-form__control--error' : '' ?>"
            type="<?= site_e($type) ?>"
            id="<?= site_e($field_id) ?>"
            name="<?= site_e($name) ?>"
            value="<?= site_e($value) ?>"
            placeholder="<?= site_e($placeholder) ?>"
            <?= $required ? 'required' : '' ?>
            <?= $autocomplete !== '' ? 'autocomplete="' . site_e($autocomplete) . '"' : '' ?>
            <?= $max_length > 0 ? 'maxlength="' . $max_length . '"' : '' ?>
            aria-invalid="<?= $error ? 'true' : 'false' ?>"
            aria-describedby="<?= site_e($field_id) ?>-meta"
          >
        <?php endif; ?>

        <div class="contact-form__meta" id="<?= site_e($field_id) ?>-meta">
          <?php if ($error): ?>
            <div class="contact-form__error"><?= site_e($error) ?></div>
          <?php elseif ($help): ?>
            <div class="contact-form__help"><?= site_e($help) ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="contact-form__footer">
    <p class="contact-form__submit-note"><?= site_e($form_copy['submit_note'] ?? '') ?></p>
    <button type="submit" class="btn btn-primary"><?= site_e($form_copy['submit_label'] ?? 'Send') ?></button>
  </div>
</form>
