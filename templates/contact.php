<?php
declare(strict_types=1);

$record      = $view['record'] ?? [];
$hero        = $view['hero'] ?? ($record['hero'] ?? []);
$fit         = $record['fit'] ?? [];
$not_fit     = $record['not_fit'] ?? [];
$form_copy   = $view['form_copy'] ?? ($record['form'] ?? []);
$form_config = $view['form_config'] ?? [];
$form_state  = $view['form_state'] ?? [];

$form_values  = $form_state['values'] ?? [];
$form_errors  = $form_state['errors'] ?? [];
$form_status  = $form_state['status'] ?? null;
$form_started = (string) ($form_state['form_started'] ?? time());
?>

<?php if (is_array($form_status) && !empty($form_status['message'])): ?>
  <div class="contact-alert contact-alert--<?= site_e((string) ($form_status['type'] ?? 'info')) ?>">
    <?= site_e((string) $form_status['message']) ?>
  </div>
<?php endif; ?>

<header class="contact-hero">
  <div class="contact-hero__eyebrow"><?= site_e($hero['eyebrow'] ?? 'Contact') ?></div>
  <h1 class="contact-hero__title"><?= site_e($hero['title'] ?? 'Contact') ?></h1>
  <p class="contact-hero__subheadline"><?= site_e($hero['subheadline'] ?? '') ?></p>
  <p class="contact-hero__intro"><?= site_e($hero['intro'] ?? '') ?></p>
</header>

<section class="contact-guide">
  <article class="contact-guide__card contact-guide__card--fit">
    <div class="contact-guide__label"><?= site_e($fit['title'] ?? 'Good fit') ?></div>
    <ul class="contact-guide__list">
      <?php foreach (($fit['items'] ?? []) as $item): ?>
        <li><?= site_e((string) $item) ?></li>
      <?php endforeach; ?>
    </ul>
  </article>

  <article class="contact-guide__card contact-guide__card--not-fit">
    <div class="contact-guide__label"><?= site_e($not_fit['title'] ?? 'Not for') ?></div>
    <ul class="contact-guide__list">
      <?php foreach (($not_fit['items'] ?? []) as $item): ?>
        <li><?= site_e((string) $item) ?></li>
      <?php endforeach; ?>
    </ul>
  </article>
</section>

<section class="contact-panel">
  <div class="contact-panel__intro">
    <div class="contact-panel__eyebrow">Start Here</div>
    <h2 class="contact-panel__title"><?= site_e($form_copy['section_title'] ?? 'Send the details') ?></h2>
    <p class="contact-panel__lead"><?= site_e($form_copy['transition'] ?? '') ?></p>
    <p class="contact-panel__text"><?= site_e($form_copy['intro'] ?? '') ?></p>
  </div>

  <?php require site_root_path('templates/partials/contact-form.php'); ?>

  <?php if (!empty($form_copy['fallback_note'])): ?>
    <p class="contact-panel__fallback"><?= site_e($form_copy['fallback_note']) ?></p>
  <?php endif; ?>
</section>
