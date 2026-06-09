<?php
$page_type = 'home';
require PARTIALS_PATH . 'head.php';
require PARTIALS_PATH . 'header.php';

$hero       = $data['hero']          ?? [];
$workstation = $data['workstation']  ?? [];
$why        = $data['why']           ?? [];
$fed_cta    = $data['federation_cta'] ?? [];
$channels   = get_all_channels();
$sessions   = get_latest_sessions(6, 'public');
$membership = get_membership();
$tiers      = $membership['tiers'] ?? [];
?>
<main class="site-main">

  <!-- ── Hero ─────────────────────────────────────────────────────────────── -->
  <section class="hero">
    <div class="hero__bg">
      <div class="hero__bg-grid"></div>
    </div>
    <div class="hero__inner">
      <div class="hero__content">
        <div class="hero__eyebrow">AI Now Guide &mdash; Build in Public</div>
        <h1 class="hero__title">
          <?= e($hero['headline'] ?? 'Watch a Digital Asset Federation Get Built in Public.') ?><br>
          <span class="hero__title-accent"><?= e($hero['headline_accent'] ?? 'Raw. Unfiltered. Daily.') ?></span>
        </h1>
        <p class="hero__subtitle"><?= e($hero['subheadline'] ?? '') ?></p>
        <div class="hero__actions">
          <a href="<?= e($hero['cta_primary_url'] ?? '/sessions') ?>" class="btn btn--primary">
            <?= e($hero['cta_primary_label'] ?? 'Watch Latest Session') ?>
          </a>
          <a href="<?= e($hero['cta_secondary_url'] ?? '/channels') ?>" class="btn btn--secondary">
            <?= e($hero['cta_secondary_label'] ?? 'Explore Channels') ?>
          </a>
        </div>
      </div>
      <div class="hero__visual">
        <div class="hero__visual-screen">
          <div class="hero__visual-bar">
            <span></span><span></span><span></span>
          </div>
          <div class="hero__visual-content">
            <div class="hero__stat-grid">
              <div class="hero__stat"><span class="hero__stat-num">80+</span><span class="hero__stat-label">Digital Properties</span></div>
              <div class="hero__stat"><span class="hero__stat-num">Daily</span><span class="hero__stat-label">New Sessions</span></div>
              <div class="hero__stat"><span class="hero__stat-num">Raw</span><span class="hero__stat-label">No Editing</span></div>
              <div class="hero__stat"><span class="hero__stat-num">Real</span><span class="hero__stat-label">Results</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Workstation section ────────────────────────────────────────────── -->
  <section class="home-section">
    <div class="home-section__header">
      <div class="section-heading">
        <div class="section-heading__label">The Setup</div>
        <h2 class="section-heading__title"><?= e($workstation['heading'] ?? 'Not a Course Studio. The Actual Build Desk.') ?></h2>
        <p class="section-heading__body"><?= e($workstation['body'] ?? '') ?></p>
      </div>
    </div>
    <div class="workstation-bullets">
      <?php foreach ($workstation['bullets'] ?? [] as $bullet): ?>
      <div class="workstation-bullet">
        <span class="workstation-bullet__dot"></span>
        <span class="workstation-bullet__text"><?= e($bullet) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ── Channels section ───────────────────────────────────────────────── -->
  <?php if (!empty($channels)): ?>
  <section class="home-section home-section--full">
    <div class="home-section__inner">
      <div class="home-section__header">
        <div class="section-heading">
          <div class="section-heading__label">What You Can Watch</div>
          <h2 class="section-heading__title">Choose a Channel to Follow</h2>
          <p class="section-heading__body">Each channel covers a distinct domain of the portfolio build. Subscribe to what matters to you.</p>
        </div>
      </div>
      <div class="cards-grid cards-grid--4col">
        <?php foreach ($channels as $channel): ?>
        <a href="/channels/<?= e($channel['core']['slug'] ?? '') ?>" class="channel-card">
          <div class="channel-card__icon"><?= $channel['core']['icon'] ?? '' ?></div>
          <div class="channel-card__title"><?= e($channel['core']['title'] ?? '') ?></div>
          <p class="channel-card__desc"><?= e(truncate($channel['core']['description'] ?? '', 100)) ?></p>
          <div class="channel-card__link">Watch channel &rarr;</div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Latest sessions ────────────────────────────────────────────────── -->
  <?php if (!empty($sessions)): ?>
  <section class="home-section">
    <div class="home-section__header">
      <div class="section-heading">
        <div class="section-heading__label">Recent Work</div>
        <h2 class="section-heading__title">Latest Sessions</h2>
        <p class="section-heading__body">The most recent raw working sessions from the portfolio build.</p>
      </div>
    </div>
    <div class="cards-grid cards-grid--3col">
      <?php foreach ($sessions as $session): ?>
      <?php $channel_data = !empty($session['core']['channel']) ? get_channel($session['core']['channel']) : null; ?>
      <a href="/sessions/<?= e($session['core']['slug'] ?? '') ?>" class="session-card">
        <?php if (!empty($session['core']['thumbnail'])): ?>
        <div class="session-card__thumb">
          <img src="<?= e($session['core']['thumbnail']) ?>" alt="<?= e($session['core']['title'] ?? '') ?>" loading="lazy">
        </div>
        <?php else: ?>
        <div class="session-card__thumb session-card__thumb--placeholder">
          <span class="session-card__thumb-icon"><?= $channel_data['core']['icon'] ?? '▶' ?></span>
        </div>
        <?php endif; ?>
        <div class="session-card__body">
          <?php if ($channel_data): ?>
          <div class="session-card__channel"><?= e($channel_data['core']['title'] ?? '') ?></div>
          <?php endif; ?>
          <h3 class="session-card__title"><?= e($session['core']['title'] ?? '') ?></h3>
          <p class="session-card__summary"><?= e(truncate($session['content']['summary'] ?? '', 120)) ?></p>
          <div class="session-card__meta">
            <span class="session-card__date"><?= e($session['core']['date'] ?? '') ?></span>
            <?php if (!empty($session['core']['duration'])): ?>
            <span class="session-card__duration"><?= e($session['core']['duration']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="home-section__more">
      <a href="/sessions" class="btn btn--secondary">View All Sessions &rarr;</a>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Membership section ─────────────────────────────────────────────── -->
  <?php if (!empty($tiers)): ?>
  <section class="home-section home-section--full" id="membership">
    <div class="home-section__inner">
      <div class="home-section__header">
        <div class="section-heading">
          <div class="section-heading__label">Access</div>
          <h2 class="section-heading__title">Membership Levels</h2>
          <p class="section-heading__body">Watch at your own depth. Most sessions are free. Full access is for builders who want everything.</p>
        </div>
      </div>
      <div class="membership-grid">
        <?php foreach ($tiers as $tier): ?>
        <div class="membership-card membership-card--<?= e($tier['slug'] ?? 'free') ?><?= !empty($tier['highlighted']) ? ' membership-card--highlighted' : '' ?>">
          <?php if (!empty($tier['highlighted'])): ?>
          <div class="membership-card__badge">Most Popular</div>
          <?php endif; ?>
          <div class="membership-card__title"><?= e($tier['title'] ?? '') ?></div>
          <div class="membership-card__price"><?= e($tier['price_label'] ?? '') ?></div>
          <p class="membership-card__desc"><?= e($tier['description'] ?? '') ?></p>
          <ul class="membership-card__features">
            <?php foreach ($tier['features'] ?? [] as $feature): ?>
            <li><?= e($feature) ?></li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= e($tier['cta_url'] ?? '#') ?>" class="btn<?= !empty($tier['highlighted']) ? ' btn--primary' : ' btn--secondary' ?>">
            <?= e($tier['cta_label'] ?? 'Join') ?>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Why section ────────────────────────────────────────────────────── -->
  <section class="home-section">
    <div class="why-block">
      <div class="section-heading">
        <div class="section-heading__label">Why This Exists</div>
        <h2 class="section-heading__title"><?= e($why['heading'] ?? 'Why This Exists') ?></h2>
        <p class="section-heading__body"><?= e($why['body'] ?? '') ?></p>
      </div>
    </div>
  </section>

  <!-- ── Federation CTA ─────────────────────────────────────────────────── -->
  <section class="home-section">
    <div class="cta-block">
      <div class="cta-block__eyebrow"><?= e($fed_cta['eyebrow'] ?? 'Portfolio Network') ?></div>
      <h2 class="cta-block__heading"><?= e($fed_cta['heading'] ?? 'Want to watch this build happen?') ?></h2>
      <p class="cta-block__body"><?= e($fed_cta['body'] ?? '') ?></p>
      <div class="cta-block__actions">
        <a href="<?= e($fed_cta['cta_url'] ?? '/sessions') ?>" class="btn btn--primary"><?= e($fed_cta['cta_label'] ?? 'Watch Latest Session') ?></a>
        <a href="/channels" class="btn btn--secondary">Explore Channels</a>
      </div>
    </div>
  </section>

</main>
<?php require PARTIALS_PATH . 'footer.php'; ?>
