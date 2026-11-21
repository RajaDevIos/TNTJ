<?php require_once APP_PATH . '/views/partials.php';
page_head($title, 'மாவட்டத்தில் செயல்படும் பெண்கள் இஸ்லாமியக் கல்லூரிகள், படிப்புகள் மற்றும் சேர்க்கை விபரம்.', 'கல்வி'); ?>
<div class="wrap page">
  <?php if (!$colleges) empty_state('கல்லூரி விபரங்கள் இன்னும் சேர்க்கப்படவில்லை. சேர்க்கை பற்றி அறிய மாவட்ட அலுவலகத்தை அழைக்கவும்.', '', ['/contact', 'தொடர்புக்கு']); ?>
  <?php foreach ($colleges as $c): ?>
    <article class="feature">
      <?php if ($c['photo']): ?><img src="<?= e(media($c['photo'])) ?>" alt="" loading="lazy"><?php endif; ?>
      <div>
        <?= $c['admission_open'] ? kicker('சேர்க்கை நடைபெறுகிறது') : '' ?>
        <h2><?= e($c['name']) ?></h2>
        <?php if ($c['location']): ?><p class="muted"><?= e($c['location']) ?></p><?php endif; ?>
        <?php if ($c['description']): ?><p><?= nl2br(e($c['description'])) ?></p><?php endif; ?>
        <dl class="facts">
          <?php foreach (['courses' => 'படிப்புகள்', 'eligibility' => 'தகுதி', 'seats' => 'இடங்கள்', 'facilities' => 'வசதிகள்'] as $k => $lbl): if (!$c[$k]) continue; ?>
            <div><dt><?= e(t($lbl)) ?></dt><dd><?= nl2br(e($c[$k])) ?></dd></div>
          <?php endforeach; ?>
        </dl>
        <?php if ($c['phone']): ?><p class="lm-num"><a href="tel:<?= e(tel($c['phone'])) ?>"><?= e($c['phone']) ?></a><?php contact_buttons($c['phone']); ?></p><?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>
