<?php require_once APP_PATH . '/views/partials.php';
page_head('திருமண தகவல்', 'வரதட்சணை, சீர்வரிசை இல்லாத நபிவழித் திருமணத்திற்கான தகவல் சேவை. தொடர்பு எண்கள் மாவட்ட நிர்வாகம் வழியாகவே பகிரப்படும்.', 'சேவைகள்'); ?>
<div class="wrap page with-rail">
  <div>
    <nav class="tabs" aria-label="<?= e(t('வடிகட்டு')) ?>">
      <a href="/matrimony"<?= !$for ? ' aria-current="true"' : '' ?>><?= e(t('அனைத்தும்')) ?></a>
      <a href="/matrimony?for=<?= urlencode('மணமகன் தேவை') ?>"<?= $for === 'மணமகன் தேவை' ? ' aria-current="true"' : '' ?>><?= e(t('மணமகன் தேவை')) ?></a>
      <a href="/matrimony?for=<?= urlencode('மணமகள் தேவை') ?>"<?= $for === 'மணமகள் தேவை' ? ' aria-current="true"' : '' ?>><?= e(t('மணமகள் தேவை')) ?></a>
    </nav>
    <?php if (!$profiles) empty_state('வெளியிடப்பட்ட பதிவுகள் இப்போது இல்லை. உங்கள் குடும்பத்தின் தேவையை இந்தப் பக்கத்தில் உள்ள படிவத்தில் பதிவு செய்யலாம்.', '', ['#register', 'பதிவு செய்க']); ?>
    <?php foreach ($profiles as $m): ?>
      <article class="notice">
        <p class="notice-top"><?= kicker($m['looking_for']) ?><span><?= e(t('பதிவு எண்')) ?> <?= e($m['profile_code']) ?></span></p>
        <h3><?= $m['age'] ? e(t('%d வயது', (int)$m['age'])) : '' ?><?= $m['area'] ? ', ' . e($m['area']) : '' ?></h3>
        <dl class="facts">
          <?php if ($m['marital_status']): ?><div><dt><?= e(t('நிலை')) ?></dt><dd><?= e(t($m['marital_status'] ?? '')) ?></dd></div><?php endif; ?>
          <?php if ($m['education']): ?><div><dt><?= e(t('கல்வி')) ?></dt><dd><?= e($m['education']) ?></dd></div><?php endif; ?>
          <?php if ($m['occupation']): ?><div><dt><?= e(t('பணி')) ?></dt><dd><?= e($m['occupation']) ?></dd></div><?php endif; ?>
        </dl>
        <?php if ($m['details']): ?><p><?= nl2br(e($m['details'])) ?></p><?php endif; ?>
        <?php if ($m['show_phone']): ?><p class="lm-num"><a href="tel:<?= e(tel($m['contact_phone'])) ?>"><?= e(fmt_phone($m['contact_phone'])) ?></a><?php contact_buttons($m['contact_phone']); ?></p>
        <?php else: ?><p class="muted"><?= e(t('தொடர்புக்கு மாவட்ட அலுவலகத்தை அழைத்து பதிவு எண்ணைக் குறிப்பிடுங்கள்:')) ?> <a href="tel:<?= e(tel(setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>

  <aside class="rail">
    <form class="form" method="post" action="/matrimony" id="register">
      <h2><?= e(t('தேவையைப் பதிவு செய்க')) ?></h2>
      <p class="muted"><?= e(t('நிர்வாகிகள் சரிபார்த்த பின் வெளியிடப்படும். உங்கள் கைபேசி எண் தளத்தில் காட்டப்படாது.')) ?></p>
      <?php form_guard(); ?>
      <label><?= e(t('தேவை')) ?><select name="looking_for" required><option value=""><?= e(t('தேர்வு செய்க')) ?></option><option><?= e(t('மணமகன் தேவை')) ?></option><option><?= e(t('மணமகள் தேவை')) ?></option></select></label>
      <div class="grid2">
        <label><?= e(t('வயது')) ?><input name="age" type="number" min="18" max="80" required inputmode="numeric"></label>
        <label><?= e(t('ஊர்')) ?><input name="area" required maxlength="160"></label>
      </div>
      <label><?= e(t('திருமண நிலை')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><select name="marital_status"><option value=""><?= e(t('தேர்வு செய்க')) ?></option><option><?= e(t('முதல் திருமணம்')) ?></option><option><?= e(t('மறுமணம்')) ?></option></select></label>
      <label><?= e(t('கல்வி')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="education" maxlength="255"></label>
      <label><?= e(t('பணி')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="occupation" maxlength="255"></label>
      <label><?= e(t('கூடுதல் விவரம்')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><textarea name="details" rows="3" maxlength="1500"></textarea></label>
      <label><?= e(t('பொறுப்பாளர் பெயர்')) ?><input name="contact_name" required maxlength="160"></label>
      <label><?= e(t('கைபேசி எண்')) ?><input name="contact_phone" type="tel" inputmode="tel" required pattern="[0-9+ \-]{10,15}"></label>
      <button class="btn"><?= e(t('பதிவை அனுப்பு')) ?></button>
    </form>
  </aside>
</div>
