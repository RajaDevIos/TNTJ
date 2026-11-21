<?php require_once APP_PATH . '/views/partials.php';
page_head($title, 'மார்க்கம் மற்றும் சமுதாயப் பணிகளுக்கான புள்ளிகளின் அடிப்படையில் கிளைகளின் மாதாந்திரத் தரவரிசை.', 'செய்திகள்'); ?>
<div class="wrap page">
  <?php if (!$reports) { empty_state('புள்ளிப்பட்டியல்கள் இன்னும் பதிவேற்றப்படவில்லை.'); return; }
  $idx = array_search($selected['id'], array_column($reports, 'id'), true);
  $newer = $reports[$idx - 1] ?? null; $older = $reports[$idx + 1] ?? null; ?>
  <article class="report">
    <div class="report-head">
      <h2><?= e(ta_month($selected['report_month'])) ?></h2>
      <nav class="report-step" aria-label="<?= e(t('மாதம்')) ?>">
        <?php if ($older): ?><a class="btn btn-line" href="?m=<?= e($older['report_month']) ?>"><?= e(ta_month($older['report_month'])) ?></a><?php endif; ?>
        <?php if ($newer): ?><a class="btn btn-line" href="?m=<?= e($newer['report_month']) ?>"><?= e(ta_month($newer['report_month'])) ?></a><?php endif; ?>
      </nav>
    </div>
    <?php if ($selected['notes']): ?><p class="dek"><?= nl2br(e($selected['notes'])) ?></p><?php endif; ?>
    <?php if ($selected['image']): ?>
      <a href="<?= e(media($selected['image'])) ?>" target="_blank" rel="noopener" class="report-img"><img src="<?= e(bimg($selected['image'], 1600)) ?>" alt="<?= e($selected['title']) ?>"></a>
      <p class="caption"><?= e(t('பெரிதாகப் பார்க்கப் படத்தைத் தொடவும்.')) ?></p>
    <?php endif; ?>
    <?php if ($selected['pdf']): ?><a class="btn" href="<?= e(media($selected['pdf'])) ?>" target="_blank" rel="noopener"><?= e(t('PDF பதிவிறக்கு')) ?></a><?php endif; ?>
  </article>

  <h2 class="rule-h" id="archive"><?= e(t('காப்பகம்')) ?></h2>
  <div class="archive">
    <?php $year = null; foreach ($reports as $r): $y = substr($r['report_month'], 0, 4); if ($y !== $year): if ($year !== null) echo '</ul></div>'; $year = $y; ?><div><h3><?= e($y) ?></h3><ul><?php endif; ?>
      <li><a href="?m=<?= e($r['report_month']) ?>"<?= $selected['id'] === $r['id'] ? ' aria-current="page"' : '' ?>><?= e(mb_substr(ta_month($r['report_month']), 0, -5)) ?></a></li>
    <?php endforeach; ?></ul></div>
  </div>
</div>
