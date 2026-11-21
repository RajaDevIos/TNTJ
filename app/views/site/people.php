<?php require_once APP_PATH . '/views/partials.php';
page_head($title, $lead ?? '', 'ஜமாஅத்');
$featured = $featured ?? 0; ?>
<div class="wrap page">
  <?php if (!empty($searchable)): ?>
    <div class="filters">
      <label class="search-line"><span class="sr"><?= e(t('தேடுக')) ?></span><input type="search" placeholder="<?= e(t('பெயர், ஊர் அல்லது எண்')) ?>" data-filter=".dir-row"></label>
      <nav class="tabs" aria-label="<?= e(t('பிரிவு')) ?>">
        <a href="?"<?= empty($genderFilter) ? ' aria-current="true"' : '' ?>><?= e(t('அனைவரும்')) ?></a>
        <a href="?g=<?= urlencode('ஆண்') ?>"<?= ($genderFilter ?? '') === 'ஆண்' ? ' aria-current="true"' : '' ?>><?= e(t('ஆண் தாயிக்கள்')) ?></a>
        <a href="?g=<?= urlencode('பெண்') ?>"<?= ($genderFilter ?? '') === 'பெண்' ? ' aria-current="true"' : '' ?>><?= e(t('பெண் தாயிக்கள்')) ?></a>
      </nav>
    </div>
  <?php endif; ?>

  <?php if (!$people) empty_state('இந்தப் பட்டியல் இன்னும் சேர்க்கப்படவில்லை. மாவட்ட அலுவலகத்தை அழைக்கவும்.', '', ['/contact', 'தொடர்புக்கு']); ?>

  <?php if ($featured && count($people) > $featured): ?>
    <div class="principals">
      <?php foreach (array_splice($people, 0, $featured) as $p): ?>
        <article>
          <?= avatar($p) ?>
          <p class="p-role"><?= e(t($p['subtitle'] ?? '')) ?></p>
          <h2><?= e($p['name']) ?></h2>
          <?php if ($p['phone']): ?><p class="p-num"><a href="tel:<?= e(tel($p['phone'])) ?>"><?= e(fmt_phone($p['phone'])) ?></a></p><?php contact_buttons($p['phone'], true); ?><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <table class="dir">
    <thead><tr><th scope="col"><?= e(t('பெயர்')) ?></th><th scope="col"><?= e(t(!empty($searchable) ? 'விபரம்' : 'பொறுப்பு')) ?></th><th scope="col"><?= e(t('தொலைபேசி')) ?></th><th scope="col"><span class="sr"><?= e(t('அழைக்க')) ?></span></th></tr></thead>
    <tbody>
      <?php foreach ($people as $p): ?>
        <tr class="dir-row" data-text="<?= e($p['name'] . ' ' . $p['subtitle'] . ' ' . $p['phone']) ?>">
          <th scope="row"><?= e($p['name']) ?></th>
          <td class="dir-sub"><?= e(t($p['subtitle'] ?? '')) ?></td>
          <td class="num"><?php if ($p['phone']): ?><a href="tel:<?= e(tel($p['phone'])) ?>"><?= e(fmt_phone($p['phone'])) ?></a><?php endif; ?></td>
          <td class="dir-act"><?php contact_buttons($p['phone']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="no-results" hidden><?= e(t('இந்தத் தேடலுக்குப் பொருத்தம் இல்லை. பெயரின் முதல் சில எழுத்துகளை மட்டும் முயற்சிக்கவும்.')) ?></p>
</div>
