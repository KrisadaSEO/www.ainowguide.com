<?php
// Sidebar block partial
// Expects: $block (array), $blockType (string), $styleClass (string)
$block      = $block ?? [];
$blockType  = $blockType ?? (string) ($block['type'] ?? 'block');
$styleClass = $styleClass ?? 'sidebar-block--' . $blockType;
$photoUrl   = (string) ($block['photo_url'] ?? '');

if ($blockType === 'constellation'):
?>
<div class="sidebar-block sidebar-block--constellation">
  <div class="constellation-card" style="background-image:url('/assets/img/federation-bg-mobile.webp')">

    <div class="constellation-card__text">
      <?php if (!empty($block['title'])): ?>
        <h4 class="constellation-card__heading"><?= site_e($block['title']) ?></h4>
      <?php endif; ?>
      <?php if (!empty($block['body'])): ?>
        <p class="constellation-card__body"><?= site_e($block['body']) ?></p>
      <?php endif; ?>
    </div>

    <div class="constellation-card__viz" aria-hidden="true">
      <svg class="constellation-svg" viewBox="0 0 420 380" fill="none" xmlns="http://www.w3.org/2000/svg">
        <line x1="210" y1="30"  x2="390" y2="150" stroke="rgba(255,255,255,0.30)" stroke-width="1.5" stroke-dasharray="4 4"/>
        <line x1="370" y1="200" x2="330" y2="300" stroke="rgba(255,255,255,0.70)" stroke-width="1.5"/>
        <line x1="210" y1="30"  x2="330" y2="7"   stroke="rgba(255,255,255,0.25)" stroke-width="1.5"/>
        <line x1="80"  y1="230" x2="180" y2="310" stroke="rgba(255,255,255,0.40)" stroke-width="1.5"/>
        <line x1="180" y1="310" x2="330" y2="300" stroke="rgba(255,255,255,0.70)" stroke-width="1.75"/>
        <line x1="210" y1="30"  x2="370" y2="200" stroke="rgba(255,255,255,0.40)" stroke-width="1.75"/>
        <line x1="80"  y1="230" x2="330" y2="300" stroke="rgba(255,255,255,0.60)" stroke-width="1.75"/>
        <line x1="80"  y1="230" x2="80"  y2="370" stroke="rgba(255,255,255,0.35)" stroke-width="1.5"/>
        <circle cx="210" cy="30"  r="14" fill="#c084fc"/>
        <circle cx="330" cy="7"   r="7"  fill="#a3e635" opacity="0.8"/>
        <circle cx="370" cy="200" r="12" fill="#facc15"/>
        <circle cx="330" cy="300" r="13" fill="#38bdf8" opacity="0.8"/>
        <circle cx="180" cy="310" r="12" fill="#a3e635" opacity="0.9"/>
        <circle cx="80"  cy="230" r="8"  fill="#facc15"/>
        <circle cx="25"  cy="65"  r="22" fill="#000000" opacity=".5"/>
        <circle cx="260" cy="70"  r="4"  fill="#c084fc" opacity="0.5"/>
        <circle cx="390" cy="150" r="4"  fill="#38bdf8" opacity="0.4"/>
        <circle cx="50"  cy="190" r="8"  fill="#38bdf8" opacity="0.7"/>
        <circle cx="135" cy="370" r="7"  fill="#a3e635" opacity="0.6"/>
        <line x1="240" y1="195" x2="120" y2="120" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
        <line x1="240" y1="195" x2="370" y2="200" stroke="rgba(255,255,255,0.50)" stroke-width="1.5"/>
        <line x1="240" y1="195" x2="180" y2="310" stroke="rgba(255,255,255,0.60)" stroke-width="1.5" stroke-dasharray="4 4"/>
        <circle cx="240" cy="195" r="8"  fill="#c084fc" opacity="0.8"/>
        <line x1="130" y1="358" x2="80"  y2="230" stroke="rgba(255,255,255,0.70)" stroke-width="1" stroke-dasharray="4 4"/>
        <line x1="130" y1="358" x2="180" y2="310" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="3 6"/>
        <circle cx="80"  cy="375" r="6"  fill="#fb923c" opacity="0.5"/>
        <text x="216" y="12"  font-size="12" fill="rgba(255,255,255,0.7)"  font-family="sans-serif" letter-spacing="0.08em">TOPICS</text>
        <text x="55"  y="69"  font-size="15" fill="rgba(255,255,255,0.75)" font-family="sans-serif" letter-spacing="0.08em">NO AI KARMA</text>
        <text x="345" y="8"   font-size="10" fill="rgba(255,255,255,0.65)" font-family="sans-serif" letter-spacing="0.08em">ENTITIES</text>
        <text x="245" y="220" font-size="11" fill="rgba(255,255,255,0.65)" font-family="sans-serif" letter-spacing="0.08em">RELATIONSHIPS</text>
        <text x="300" y="335" font-size="14" fill="rgba(255,255,255,0.65)" font-family="sans-serif" letter-spacing="0.08em">PAGES</text>
        <text x="160" y="340" font-size="14" fill="rgba(255,255,255,0.65)" font-family="sans-serif" letter-spacing="0.08em">AUTHORITY</text>
        <text x="20"  y="252" font-size="12" fill="rgba(255,255,255,0.7)"  font-family="sans-serif" letter-spacing="0.08em">SIGNALS</text>
      </svg>
    </div>

    <?php if (!empty($block['cta_label']) && !empty($block['cta_url'])): ?>
      <div class="constellation-card__cta-wrap">
        <a href="<?= site_e($block['cta_url']) ?>" class="cta-btn constellation-card__cta"><?= site_e($block['cta_label']) ?></a>
      </div>
    <?php endif; ?>

  </div>
</div>
<?php return; endif; ?>
<?php
$hasContent = !empty($block['title']) || !empty($block['body']) || !empty($block['links'])
    || (!empty($block['cta_label']) && !empty($block['cta_url']));
if (!$hasContent) return;
?>
<div class="sidebar-block <?= site_e($styleClass) ?>">

    <?php if ($blockType === 'mini-bio' && $photoUrl !== ''): ?>
        <div style="text-align:center; margin-bottom: 0.85rem;">
            <img src="<?= site_e($photoUrl) ?>"
                 alt="<?= site_e($block['title'] ?? 'Author') ?>"
                 style="width:72px; height:72px; border-radius:50%; object-fit:cover; border: 2px solid var(--color-border, #d7c6af);">
        </div>
    <?php endif; ?>

    <?php if (!empty($block['title'])): ?>
        <div class="sidebar-block__title"><?= site_e($block['title']) ?></div>
    <?php endif; ?>

    <?php if (!empty($block['body'])): ?>
        <div class="sidebar-block__body"><?= $block['body'] ?></div>
    <?php endif; ?>

    <?php if (!empty($block['links'])): ?>
        <ul class="sidebar-block__links">
        <?php foreach ($block['links'] as $link): ?>
            <li>
                <?php if ($blockType === 'related-articles' || $blockType === 'topic-articles'): ?>
                <h2 class="sidebar-block__link-heading"><a href="<?= site_e($link['url'] ?? '#') ?>"
                   <?= !empty($link['url']) && str_starts_with($link['url'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                ><?= site_e($link['label'] ?? '') ?></a></h2>
                <?php else: ?>
                <a href="<?= site_e($link['url'] ?? '#') ?>"
                   <?= !empty($link['url']) && str_starts_with($link['url'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                   <?= !empty($link['is_current']) ? 'class="is-current"' : '' ?>
                ><?= site_e($link['label'] ?? '') ?></a>
                <?php endif; ?>
                <?php if (!empty($link['description'])): ?>
                    <span class="text-muted" style="display:block;font-size:0.8rem;margin-top:0.15rem;"><?= site_e($link['description']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php if ($blockType === 'topic-articles' && !empty($block['category_url'])): ?>
            <a href="<?= site_e($block['category_url']) ?>" class="sidebar-block__browse-all">Browse all &rarr;</a>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($block['cta_label']) && !empty($block['cta_url'])): ?>
        <a href="<?= site_e($block['cta_url']) ?>" class="cta-btn"><?= site_e($block['cta_label']) ?></a>
    <?php endif; ?>

</div>
