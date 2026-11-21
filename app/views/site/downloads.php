<?php require_once APP_PATH . '/views/partials.php';
page_head($title, 'விண்ணப்பப் படிவங்கள், விதிமுறைகள் மற்றும் பாடத்திட்டங்கள். தலைப்பைத் தொட்டால் கோப்பு திறக்கும்.', 'சேவைகள்'); ?>
<div class="wrap page">
  <?php if (!$grouped) empty_state('பதிவிறக்கக் கோப்புகள் இன்னும் சேர்க்கப்படவில்லை.'); ?>
  <div class="doc-cols">
    <?php foreach ($grouped as $cat => $list): ?>
      <section>
        <h2 class="rule-h"><?= e(t($cat)) ?></h2>
        <ul class="docs">
          <?php foreach ($list as $f): ?>
            <li><a href="/downloads/<?= (int)$f['id'] ?>" target="_blank" rel="noopener"><span><?= e(t($f['title'])) ?></span><?= icon('download', 'i i-sm') ?></a><?php if ($f['description']): ?><small><?= e($f['description']) ?></small><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
</div>
