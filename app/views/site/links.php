<?php require_once APP_PATH . '/views/partials.php';
page_head($title, 'TNTJ மாநிலத் தலைமை மற்றும் அதிகாரப்பூர்வ ஊடகங்கள்.', 'தொடர்பு'); ?>
<div class="wrap page">
  <?php if (!$grouped) empty_state('இணைப்புகள் இன்னும் சேர்க்கப்படவில்லை.'); ?>
  <div class="doc-cols">
    <?php foreach ($grouped as $cat => $list): ?>
      <section>
        <h2 class="rule-h"><?= e(t($cat)) ?></h2>
        <ul class="docs">
          <?php foreach ($list as $l): ?>
            <li><a href="<?= e($l['url']) ?>" target="_blank" rel="noopener"><span><?= e(t($l['title'])) ?></span><?= icon('external', 'i i-sm') ?></a><small><?= e($l['description'] ?: '') ?> <span class="host"><?= e(preg_replace('/^www\./', '', (string)parse_url($l['url'], PHP_URL_HOST))) ?></span></small></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
</div>
