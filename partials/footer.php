<?php
$settings = get_site_settings();
$footer   = $settings['footer'] ?? [];
$columns  = $footer['columns'] ?? [];
?>
<footer class="site-footer">
  <div class="site-footer__inner">

    <div class="site-footer__grid">

      <div class="site-footer__col site-footer__col--brand">
        <div class="site-footer__brand-name">AI<span class="site-footer__brand-dot">Now</span>Guide</div>
        <div class="site-footer__brand-tag">Build in Public</div>
        <p class="site-footer__desc"><?= e($footer['tagline'] ?? 'The public operating window into a real AI-first digital asset portfolio.') ?></p>
      </div>

      <?php foreach ($columns as $col): ?>
      <div class="site-footer__col">
        <div class="site-footer__col-heading"><?= e($col['heading'] ?? '') ?></div>
        <ul class="site-footer__links">
          <?php foreach ($col['links'] ?? [] as $link): ?>
          <li><a href="<?= e($link['url'] ?? '#') ?>"><?= e($link['label'] ?? '') ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>

    </div>

    <div class="site-footer__bottom">
      <div class="site-footer__copyright">
        &copy; <?= date('Y') ?> AINowGuide.com
      </div>
      <div class="site-footer__legal">
        <a href="/about">About</a>
        <a href="/sessions">Sessions</a>
      </div>
    </div>

  </div>
</footer>

<script src="/assets/js/main.js"></script>
</div><!-- /.site-wrapper -->

<!-- Default Statcounter code for ! - *AINowGuide.com
https://www.ainowguide.com/ -->
<script type="text/javascript">
var sc_project=12961705;
var sc_invisible=1;
var sc_security="3648782d";
var sc_remove_link=1;
</script>
<script type="text/javascript"
src="https://www.statcounter.com/counter/counter.js"
async></script>
<noscript><div class="statcounter"><img class="statcounter"
src="https://c.statcounter.com/12961705/0/3648782d/1/"
alt="Web Analytics Made Easy - Statcounter"
referrerPolicy="no-referrer-when-downgrade"></div></noscript>
<!-- End of Statcounter Code -->

</body>
</html>
