<?php require_once APP_PATH . '/views/partials.php';
page_head($title, t('%d கிளைகளின் பொறுப்பாளர்களும் அவர்களின் தொலைபேசி எண்களும். ஊர் அல்லது பெயரைத் தேடலாம்.', count($branches)), 'ஜமாஅத்');
$sorted = $branches;
usort($sorted, fn($a, $b) => strcasecmp(t($a['name']), t($b['name'])));  // A–Z in the visitor's language ?>
<div class="wrap page branches">
  <nav class="b-index" aria-label="<?= e(t('கிளைகள்')) ?>">
    <h2><?= e(t('கிளைகள்')) ?></h2>
    <ul><?php foreach ($sorted as $b): ?><li><a href="#b<?= (int)$b['id'] ?>"><?= e(t($b['name'])) ?></a></li><?php endforeach; ?></ul>
  </nav>
  <div class="b-main">
    <div class="filters">
      <label class="search-line"><span class="sr"><?= e(t('கிளை, பெயர் அல்லது எண்')) ?></span><input type="search" placeholder="<?= e(t('கிளை, பெயர் அல்லது எண்')) ?>" data-filter=".b-sec"></label>
    </div>
    <?php foreach ($sorted as $b): $os = $officers[$b['id']] ?? []; ?>
      <details class="b-sec" id="b<?= (int)$b['id'] ?>" data-text="<?= e($b['name'] . ' ' . $b['area'] . ' ' . implode(' ', array_map(fn($o) => $o['name'] . ' ' . $o['phone'], $os))) ?>">
        <summary><?php if (!empty($photos[$b['id']])): ?><img class="b-thumb" src="<?= e(bimg($photos[$b['id']], 200)) ?>" alt="" loading="lazy"><?php else: ?><span class="b-thumb b-thumb-none" aria-hidden="true"></span><?php endif; ?><h2><?= e(t($b['name'])) ?></h2><span><?= $os ? e(t($os[0]['role']) . ' ' . $os[0]['name']) : e(t('விபரம் இல்லை')) ?></span><?= icon('chev', 'i b-chev') ?></summary>
        <?php if (!$os): ?><p class="muted"><?= e(t('இந்தக் கிளையின் பொறுப்பாளர்கள் இன்னும் சேர்க்கப்படவில்லை.')) ?></p><?php else: ?>
        <table class="dir dir-tight">
          <tbody>
            <?php foreach ($os as $o): ?>
              <tr><td class="dir-sub"><?= e(t($o['role'] ?? '')) ?></td><th scope="row"><?= e($o['name']) ?></th><td class="num"><a href="tel:<?= e(tel($o['phone'])) ?>"><?= e(fmt_phone($o['phone'])) ?></a></td><td class="dir-act"><?php contact_buttons($o['phone']); ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
        <a class="more" href="/branches/<?= (int)$b['id'] ?>"><?= e(t('%s செய்திகள்', branch_label($b['name']))) ?></a>
      </details>
    <?php endforeach; ?>
    <p class="no-results" hidden><?= e(t('இந்தப் பெயரில் கிளையோ பொறுப்பாளரோ இல்லை.')) ?></p>
  </div>
</div>
