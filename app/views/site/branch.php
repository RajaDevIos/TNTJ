<?php require_once APP_PATH . '/views/partials.php';
page_head(branch_label($branch['name']), trim(($branch['area'] ?? '') . ' ' . ($branch['address'] ?? '')), 'கிளைகள்'); ?>
<div class="wrap page with-rail">
  <div>
    <h2 class="rule-h"><?= e(t('கிளைச் செய்திகள்')) ?></h2>
    <?php if (!$news) empty_state('இந்தக் கிளையின் செய்திகள் இன்னும் பதிவேற்றப்படவில்லை.'); ?>
    <div class="river"><?php foreach ($news as $n) news_row($n); ?></div>
    <?php if (count($news) >= 12): ?><a class="more" href="/news?branch=<?= (int)$branch['id'] ?>"><?= e(t('இந்தக் கிளையின் எல்லாச் செய்திகளும்')) ?></a><?php endif; ?>
  </div>
  <aside class="rail">
    <h2 class="rule-h"><?= e(t('பொறுப்பாளர்கள்')) ?></h2>
    <?php foreach ($officers as $o): ?>
      <div class="lm"><p class="lm-role"><?= e(t($o['role'] ?? '')) ?></p><p class="lm-name"><?= e($o['name']) ?></p><p class="lm-num"><a href="tel:<?= e(tel($o['phone'])) ?>"><?= e(fmt_phone($o['phone'])) ?></a><?php contact_buttons($o['phone']); ?></p></div>
    <?php endforeach; ?>
    <?php if ($branch['map_url']): ?><a class="btn btn-line" href="<?= e($branch['map_url']) ?>" target="_blank" rel="noopener"><?= e(t('வரைபடத்தில் பார்')) ?></a><?php endif; ?>
  </aside>
</div>
