<?php
declare(strict_types=1);

$view = $view ?? [];
$record = $view['record'] ?? [];
$downloadState = $view['download_state'] ?? [];
$claim = is_array($downloadState['claim'] ?? null) ? $downloadState['claim'] : null;
$error = is_string($downloadState['error'] ?? null) ? $downloadState['error'] : '';
?>

<article class="download-page">
    <header class="article-header">
        <div class="article-header__eyebrow">Protected Download</div>
        <h1><?= site_e((string) ($record['title'] ?? 'Download')) ?></h1>

        <?php if (!empty($record['summary'])): ?>
            <p class="article-header__excerpt"><?= site_e((string) ($record['summary'] ?? '')) ?></p>
        <?php endif; ?>
    </header>

    <?php if (!empty($record['body'])): ?>
        <div class="article-body">
            <?= (string) ($record['body'] ?? '') ?>
        </div>
    <?php endif; ?>

    <section class="download-gate" id="download-access">
        <div class="download-gate__header">
            <h2><?= site_e((string) ($record['gate_headline'] ?? 'Unlock the download')) ?></h2>
            <p><?= site_e((string) ($record['gate_description'] ?? 'Enter your email and I will unlock the protected download for you right away.')) ?></p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="download-gate__alert download-gate__alert--error" role="alert"><?= site_e($error) ?></div>
        <?php endif; ?>

        <?php if ($claim !== null): ?>
            <div class="download-gate__alert download-gate__alert--success" role="status">
                <p><strong><?= site_e((string) ($record['success_message'] ?? 'Your protected download is ready below.')) ?></strong></p>
                <p>Unlocked for <?= site_e((string) ($claim['email_masked'] ?? 'you')) ?><?= !empty($claim['email_sent']) ? '. A copy of the access link was emailed too.' : '. The access link was generated on-page even if email delivery is unavailable.' ?></p>
                <p><a href="<?= site_e((string) ($claim['access_url'] ?? '#')) ?>" class="btn btn-primary"><?= site_e((string) ($record['button_label'] ?? 'Get the download')) ?></a></p>
            </div>
        <?php endif; ?>

        <form class="download-gate__form" action="<?= site_e((string) ($record['canonical_url'] ?? '/')) ?>" method="post">
            <input type="hidden" name="form_started" value="<?= time() ?>">
            <div style="display:none" aria-hidden="true">
                <input type="text" name="_hp" value="" tabindex="-1" autocomplete="off">
            </div>

            <label class="download-gate__label" for="download-email">Email address</label>
            <div class="download-gate__row">
                <input
                    id="download-email"
                    type="email"
                    name="email"
                    required
                    class="download-gate__input"
                    placeholder="you@example.com"
                    autocomplete="email"
                >
                <button type="submit" class="btn btn-primary download-gate__button"><?= site_e((string) ($record['button_label'] ?? 'Get the download')) ?></button>
            </div>
            <p class="download-gate__note">The file itself stays protected. The access link is generated just for the email you enter here.</p>
        </form>
    </section>
</article>
