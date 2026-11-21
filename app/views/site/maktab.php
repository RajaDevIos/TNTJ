<?php require_once APP_PATH . '/views/partials.php';
page_head($title, 'தஞ்சை வடக்கு மாவட்டக் கிளைகளுக்கான மக்தப் விதிமுறைகள், Class C பாடத்திட்டம் மற்றும் விண்ணப்பப் படிவங்கள்.', 'கல்வி');
$byCat = [];
foreach ($files as $f) $byCat[$f['category']][] = $f; ?>
<div class="wrap page">
  <?php if ($maktabs): ?>
    <h2 class="rule-h"><?= e(t('செயல்படும் மதரஸாக்கள்')) ?></h2>
    <table class="dir">
      <thead><tr><th scope="col"><?= e(t('மதரஸா')) ?></th><th scope="col"><?= e(t('நேரம்')) ?></th><th scope="col"><?= e(t('மாணவர்கள்')) ?></th><th scope="col"><?= e(t('ஆசிரியர்')) ?></th><th scope="col"><span class="sr"><?= e(t('அழைக்க')) ?></span></th></tr></thead>
      <tbody>
        <?php foreach ($maktabs as $m): ?>
          <tr><th scope="row"><?= e($m['name']) ?><?php if ($m['branch_name']): ?><small><?= e(branch_label($m['branch_name'])) ?></small><?php endif; ?></th><td><?= e($m['timing']) ?></td><td class="num"><?= $m['students'] ? (int)$m['students'] : '' ?></td><td><?= e($m['teacher']) ?></td><td class="dir-act"><?php contact_buttons($m['phone']); ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="doc-cols">
    <?php foreach ($byCat as $cat => $list): ?>
      <section>
        <h2 class="rule-h"><?= e(t(trim(str_replace('மக்தப் ', '', $cat)))) ?></h2>
        <ul class="docs">
          <?php foreach ($list as $f): ?>
            <li><a href="/downloads/<?= (int)$f['id'] ?>" target="_blank" rel="noopener"><span><?= e(t($f['title'])) ?></span><?= icon('download', 'i i-sm') ?></a><?php if ($f['description']): ?><small><?= e($f['description']) ?></small><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
  <?php if (!$maktabs && !$files) empty_state('மக்தப் விபரங்கள் இன்னும் சேர்க்கப்படவில்லை.'); ?>
</div>
