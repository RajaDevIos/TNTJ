<?php require_once APP_PATH . '/views/partials.php';
page_head($title, 'பொதுக்கூட்டங்கள், தர்பியா முகாம்கள், இரத்ததான முகாம்கள் மற்றும் கிளை நிகழ்ச்சிகள்.', 'செய்திகள்');
$row = function (array $ev, bool $past = false) { $t = strtotime($ev['starts_at']); ?>
  <li class="ev<?= $past ? ' ev-past' : '' ?>">
    <time class="ev-date" datetime="<?= e(date('Y-m-d', $t)) ?>"><b><?= date('j', $t) ?></b><span><?= e(ta_month($ev['starts_at'])) ?></span></time>
    <div>
      <?= kicker($ev['category']) ?>
      <h3><?= e($ev['title']) ?></h3>
      <p class="ev-meta"><span><?= date('g:i A', $t) ?></span><?php if ($ev['location']): ?><span><?= e($ev['location']) ?></span><?php endif; ?></p>
      <?php if ($ev['speaker']): ?><p><?= e(t('சிறப்புரை:')) ?> <?= e($ev['speaker']) ?></p><?php endif; ?>
      <?php if ($ev['description']): ?><p><?= nl2br(e($ev['description'])) ?></p><?php endif; ?>
    </div>
    <?php if ($ev['poster']): ?><a href="<?= e(media($ev['poster'])) ?>" target="_blank"><img src="<?= e(media($ev['poster'])) ?>" alt="<?= e($ev['title'] . ' ' . t('போஸ்டர்')) ?>" loading="lazy"></a><?php endif; ?>
  </li>
<?php }; ?>
<div class="wrap page with-rail">
  <div>
    <h2 class="rule-h"><?= e(t('வரவிருப்பவை')) ?></h2>
    <?php if (!$upcoming): ?><p class="muted"><?= e(t('தற்போது அறிவிக்கப்பட்ட நிகழ்வுகள் இல்லை. புதிய நிகழ்வுகள் இங்கும் முகப்புப் பக்கத்திலும் வெளியிடப்படும்.')) ?></p><?php endif; ?>
    <ol class="evs"><?php foreach ($upcoming as $ev) $row($ev); ?></ol>
    <?php if ($past): ?>
      <h2 class="rule-h"><?= e(t('நடைபெற்றவை')) ?></h2>
      <ol class="evs"><?php foreach ($past as $ev) $row($ev, true); ?></ol>
    <?php endif; ?>
  </div>
  <aside class="rail">
    <h2 class="rule-h"><?= e(t('கிளைகளில் நடந்தவை')) ?></h2>
    <?php foreach ($news as $n) news_row($n, false); ?>
    <a class="more" href="/news"><?= e(t('எல்லாச் செய்திகளும்')) ?></a>
  </aside>
</div>
