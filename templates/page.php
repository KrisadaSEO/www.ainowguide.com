<?php
declare(strict_types=1);
?>
<div class="page-content">
    <h1 class="page-content__title"><?= site_e((string) ($record['title'] ?? '')) ?></h1>
    <?php if (!empty($record['description'])): ?>
    <p class="page-content__lead"><?= site_e((string) $record['description']) ?></p>
    <?php endif; ?>
    <?php if (!empty($record['body'])): ?>
    <div class="page-content__body">
        <?= nl2br(site_e((string) $record['body'])) ?>
    </div>
    <?php endif; ?>
</div>
