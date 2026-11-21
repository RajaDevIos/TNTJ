<?php require_once APP_PATH . '/views/partials.php';
page_head($title, 'மாவட்ட அலுவலகம், பொறுப்பாளர்கள், அல்லது எங்களுக்கு எழுதுங்கள்.', 'தொடர்பு'); ?>
<div class="wrap page with-rail">
  <div>
    <dl class="contact-facts">
      <div><dt><?= e(t('மாவட்ட அலுவலகம்')) ?></dt><dd><a class="big-num" href="tel:<?= e(tel(setting('phone'))) ?>"><?= e(setting('phone')) ?></a><?php contact_buttons(setting('phone')); ?></dd></div>
      <div><dt><?= e(t('இரத்த உதவி (மருத்துவரணி)')) ?></dt><dd><a class="big-num big-num-red" href="tel:<?= e(tel(setting('blood_helpline'))) ?>"><?= e(setting('blood_helpline')) ?></a><?php contact_buttons(setting('blood_helpline')); ?></dd></div>
      <div><dt><?= e(t('முகவரி')) ?></dt><dd><?= e(t(setting('address'))) ?></dd></div>
      <?php if (setting('email')): ?><div><dt><?= e(t('மின்னஞ்சல்')) ?></dt><dd><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></dd></div><?php endif; ?>
      <?php if (setting('facebook')): ?><div><dt>Facebook</dt><dd><a href="<?= e(setting('facebook')) ?>" target="_blank" rel="noopener"><?= e(preg_replace('~^https?://(www\.)?~', '', rtrim(setting('facebook'), '/'))) ?></a></dd></div><?php endif; ?>
    </dl>
    <h2 class="rule-h"><?= e(t('மாவட்டப் பொறுப்பாளர்கள்')) ?></h2>
    <div class="principals principals-sm">
      <?php foreach ($leaders as $l): ?>
        <article><p class="p-role"><?= e(t($l['role'] ?? '')) ?></p><h3><?= e($l['name']) ?></h3><p class="p-num"><a href="tel:<?= e(tel($l['phone'])) ?>"><?= e(fmt_phone($l['phone'])) ?></a></p><?php contact_buttons($l['phone']); ?></article>
      <?php endforeach; ?>
    </div>
    <?php if (setting('map_embed_url')): ?><iframe class="map" src="<?= e(setting('map_embed_url')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?= e(t('மாவட்ட அலுவலக வரைபடம்')) ?>"></iframe><?php endif; ?>
  </div>
  <aside class="rail">
    <form class="form" method="post" action="/contact" id="form">
      <h2><?= e(t('எங்களுக்கு எழுதுங்கள்')) ?></h2>
      <p class="muted"><?= e(t('கேள்வி, ஆலோசனை அல்லது புகார். பொறுப்பாளர்கள் உங்களைத் தொடர்பு கொள்வார்கள்.')) ?></p>
      <?php form_guard(); ?>
      <label><?= e(t('பெயர்')) ?><input name="name" required maxlength="160" autocomplete="name"></label>
      <label><?= e(t('கைபேசி எண்')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="phone" type="tel" inputmode="tel" pattern="[0-9+ \-]{10,15}" autocomplete="tel"></label>
      <label><?= e(t('பொருள்')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="subject" maxlength="255"></label>
      <label><?= e(t('செய்தி')) ?><textarea name="message" rows="5" required maxlength="2000"></textarea></label>
      <button class="btn"><?= e(t('செய்தியை அனுப்பு')) ?></button>
    </form>
  </aside>
</div>
