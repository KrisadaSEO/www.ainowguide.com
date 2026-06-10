<?php
declare(strict_types=1);

$delivery = site_flash_consume('subscribe.delivery');
$maskedEmail = is_array($delivery) ? (string) ($delivery['email_masked'] ?? '') : '';
$score = is_array($delivery) ? (int) ($delivery['score'] ?? 0) : 0;
$total = is_array($delivery) ? (int) ($delivery['total'] ?? 0) : 0;
$resultsSent = is_array($delivery) ? (bool) ($delivery['results_sent'] ?? false) : false;
?>

<div class="thankyou-wrap">
    <div class="thankyou-eyebrow">Done.</div>
    <h1 class="thankyou-title"><?= $resultsSent ? 'Your results are on the way.' : 'Your self-assessment is ready.' ?></h1>
    <p class="thankyou-body">
        <?php if ($resultsSent && $maskedEmail !== '' && $score > 0 && $total > 0): ?>
            I emailed your self-assessment summary to <strong><?= site_e($maskedEmail) ?></strong> with your score of <strong><?= $score ?>/<?= $total ?></strong>, the weak spots it surfaced, and the next reading path.
        <?php elseif ($resultsSent && $maskedEmail !== ''): ?>
            I emailed your self-assessment summary to <strong><?= site_e($maskedEmail) ?></strong>.
        <?php else: ?>
            Answer all 10 questions, open the explanations anywhere the wording feels fuzzy, and use the score to see what needs attention first.
        <?php endif; ?>
    </p>

    <a href="/checklist/" class="btn btn-primary thankyou-cta" target="_blank" rel="noopener">
        <?= $resultsSent ? 'Open the Self-Assessment Again &rarr;' : 'Open the Self-Assessment &rarr;' ?>
    </a>
    <p class="thankyou-note">
        <?php if ($resultsSent): ?>
            Opens in a new tab. If the email does not show up right away, check spam or promotions first.
        <?php else: ?>
            Opens in a new tab. You can save the finished assessment as a PDF after you work through it.
        <?php endif; ?>
    </p>

    <div class="thankyou-next">
        <p>When a question uncovers a weak spot, the library is where the fixes and deeper explanations live.</p>
        <a href="/library/" class="btn btn-outline-dark">Browse the Library &rarr;</a>
    </div>
</div>
