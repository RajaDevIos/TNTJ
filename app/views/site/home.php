<?php require_once APP_PATH . '/views/partials.php';
$helpline = setting('blood_helpline');
$withImg = array_values(array_filter($news, fn($n) => !empty($n['image'])));
$slides = array_slice($withImg, 0, 5);
$slugs = array_column($slides, 'slug');
$rest = array_values(array_filter($news, fn($n) => !in_array($n['slug'], $slugs, true)));
$latest = array_slice($rest, 0, 5);
$restImg = array_values(array_filter(array_slice($rest, 5), fn($n) => !empty($n['image'])));
$mosaic = array_slice($restImg, 0, 9);
// the reel shows the spread of the district: one photo per branch
$strip = []; $seenB = [];
foreach (array_slice($restImg, 9) as $n) { $k = $n['branch_name'] ?? $n['slug']; if (isset($seenB[$k])) continue; $seenB[$k] = 1; $strip[] = $n; if (count($strip) === 12) break; }
?>
<nav class="wrap quick-m" aria-label="<?= e(t('விரைவுத் தொடர்பு')) ?>">
  <a href="tel:<?= e(tel($helpline)) ?>" class="quick-blood"><small><?= e(t('இரத்த உதவி')) ?></small><?= e($helpline) ?></a>
  <a href="#finder"><small><?= e(t('பொறுப்பாளர் எண்')) ?></small><?= e(t('தேடுக')) ?></a>
</nav>

<?php if ($slides): ?>
<!-- Hero: the week's work in the branches, in their own photographs -->
<section class="hero" aria-roledescription="carousel" aria-label="<?= e(t('கிளைகளின் அண்மைய களப்பணி')) ?>">
  <h1 class="sr"><?= e(t(setting('site_name', 'தமிழ்நாடு தவ்ஹீத் ஜமாஅத்'))) ?>, <?= e(t(setting('site_tagline', 'தஞ்சை வடக்கு மாவட்டம்'))) ?></h1>
  <div class="hero-slides">
    <?php foreach ($slides as $i => $s): ?>
      <img class="hero-img<?= $i ? '' : ' on' ?>" data-slide="<?= $i ?>" src="<?= e(bimg($s['image'], 1600)) ?>" alt="" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?> decoding="async">
    <?php endforeach; ?>
  </div>
  <div class="wrap hero-cap">
    <?php foreach ($slides as $i => $s): [$h] = headline($s); ?>
      <div class="hero-text" data-slide="<?= $i ?>" role="group" aria-roledescription="slide" aria-label="<?= $i + 1 ?> / <?= count($slides) ?>"<?= $i ? ' hidden' : '' ?>>
        <p class="hero-kick"><?= e(t($s['category'] ?: 'கிளைச் செய்தி')) ?></p>
        <h2><a href="/news/<?= e(rawurlencode($s['slug'])) ?>"><?= e($h) ?></a></h2>
        <p class="hero-meta"><time datetime="<?= e(substr($s['published_at'], 0, 10)) ?>"><?= e(ta_date($s['published_at'])) ?></time></p>
      </div>
    <?php endforeach; ?>
    <div class="hero-thumbs" role="tablist" aria-label="<?= e(t('படங்கள்')) ?>">
      <?php foreach ($slides as $i => $s): ?>
        <button type="button" role="tab" data-go="<?= $i ?>" aria-selected="<?= $i ? 'false' : 'true' ?>" aria-label="<?= e(photo_caption($s)) ?>">
          <img src="<?= e(bimg($s['image'], 200)) ?>" alt="" loading="lazy"><span class="hero-prog"></span>
        </button>
      <?php endforeach; ?>
      <button type="button" class="hero-pause" aria-label="<?= e(t('படங்கள் தானாக மாறுவதை நிறுத்து')) ?>" aria-pressed="false"><?= icon('pause') ?></button>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="wrap front">
  <!-- The month's branch ranking: the district's main notice -->
  <section class="lead-row" aria-labelledby="lead-h">
    <?php if ($report): ?>
      <article class="lead">
        <?= kicker('மாதாந்திர புள்ளிப்பட்டியல்') ?>
        <h2 id="lead-h" class="lead-h"><a href="/monthly-reports"><?= e(t('%s: கிளைகளின் செயல்பாட்டுப் புள்ளிப்பட்டியல் வெளியானது', ta_month($report['report_month']))) ?></a></h2>
        <p class="dek"><?= e(t('மார்க்கம் மற்றும் சமுதாயப் பணிகளுக்கான புள்ளிகளின் அடிப்படையில் %d கிளைகளும் மூன்று பிரிவுகளில் தரவரிசைப்படுத்தப்பட்டுள்ளன. உங்கள் கிளை எந்த இடத்தில் உள்ளது என்று பாருங்கள்.', $branchCount)) ?></p>
        <?php if ($report['image']): ?>
          <a href="/monthly-reports" class="lead-img"><img src="<?= e(bimg($report['image'], 1600)) ?>" alt="<?= e($report['title']) ?>" loading="lazy"></a>
        <?php endif; ?>
        <p class="lead-links"><a href="/monthly-reports"><?= e(t('முழுப் பட்டியலைப் பார்க்க')) ?></a><a href="/monthly-reports#archive"><?= e(t('முந்தைய மாதங்கள்')) ?></a></p>
      </article>
    <?php endif; ?>

    <aside class="latest" aria-labelledby="latest-h">
      <h2 id="latest-h" class="rule-h"><?= e(t('கிளைகளில் அண்மையில்')) ?></h2>
      <?php foreach ($latest as $n) news_row($n, true); ?>
      <a href="/news" class="more"><?= e(t('எல்லாச் செய்திகளும்')) ?></a>
    </aside>
  </section>
</div>

<!-- Blood helpline: display type on ink, with the star lattice -->
<section class="blood-band pattern" aria-labelledby="blood-h">
  <div class="wrap blood-band-in">
    <div>
      <h2 id="blood-h"><?= e(t('அவசரமாக இரத்தம் தேவையா?')) ?></h2>
      <p><?= e(t('மாவட்ட மருத்துவரணி இரவு பகல் பாராமல் கொடையாளர்களை ஏற்பாடு செய்கிறது. நோயாளியின் பெயர், மருத்துவமனை, இரத்த வகை ஆகியவற்றுடன் அழையுங்கள்.')) ?></p>
    </div>
    <a class="blood-number" href="tel:<?= e(tel($helpline)) ?>"><span><?= e(t('மருத்துவரணி')) ?></span><?= e($helpline) ?></a>
    <div class="blood-band-act">
      <a class="btn btn-red" href="/blood#need"><?= e(t('கோரிக்கையைப் பதிவு செய்க')) ?></a>
      <a class="btn btn-line-inv" href="/blood#register"><?= e(t('கொடையாளராகச் சேர')) ?></a>
    </div>
    <?php if ($requests): ?>
      <ul class="band-needs">
        <?php foreach ($requests as $r): ?>
          <li><b><?= e($r['blood_group']) ?></b><span><?= e($r['hospital']) ?>, <?= (int)$r['units'] ?> <?= e(t('யூனிட்')) ?></span><a href="tel:<?= e(tel($r['contact_phone'])) ?>"><?= e(fmt_phone($r['contact_phone'])) ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<div class="wrap front">
  <section aria-labelledby="branches-h">
    <div class="sect-head">
      <h2 id="branches-h" class="rule-h"><?= e(t('கிளைகளின் களப்பணி')) ?></h2>
      <?php if ($activities): ?>
        <nav class="topics" aria-label="<?= e(t('பணி வகை')) ?>">
          <?php foreach ($activities as $a): ?><a href="/news?c=<?= urlencode(t($a['name'])) ?>"><?= e(t($a['name'])) ?><span><?= (int)$a['n'] ?></span></a><?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>
    <div class="tiles mosaic"><?php foreach ($mosaic as $n) news_tile($n); ?></div>
  </section>
</div>

<?php if ($strip): ?>
<!-- Contact sheet: a scrollable row of field photographs from across the district -->
<section class="reel" aria-labelledby="reel-h">
  <div class="wrap reel-head">
    <h2 id="reel-h" class="rule-h"><?= e(t('களத்திலிருந்து படங்கள்')) ?></h2>
    <div class="reel-nav">
      <button type="button" class="icon-btn" data-reel="-1" aria-label="<?= e(t('முந்தைய படங்கள்')) ?>"><?= icon('chevr', 'i reel-prev') ?></button>
      <button type="button" class="icon-btn" data-reel="1" aria-label="<?= e(t('அடுத்த படங்கள்')) ?>"><?= icon('chevr', 'i reel-next') ?></button>
    </div>
  </div>
  <ul class="reel-track" tabindex="0" aria-label="<?= e(t('படங்கள், இடம் வலமாக நகர்த்தலாம்')) ?>">
    <?php foreach ($strip as $n): ?>
      <li><a href="/news/<?= e(rawurlencode($n['slug'])) ?>">
        <img src="<?= e(bimg($n['image'], 640)) ?>" alt="" loading="lazy">
        <span class="reel-cap"><b><?= e($n['branch_name'] ? branch_label($n['branch_name']) : t('மாவட்டம்')) ?></b><?= e($n['category'] ? t($n['category']) : $n['title']) ?></span>
      </a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<div class="wrap front">
  <section class="split" aria-label="<?= e(t('தொடர்பு')) ?>">
    <div class="finder" id="finder">
      <h2 class="rule-h"><?= e(t('யாரை அழைக்க வேண்டும்?')) ?></h2>
      <p class="muted"><?= e(t('%1$d கிளைகளின் %2$d பொறுப்பாளர்கள், மாவட்ட நிர்வாகிகள், தாயிக்கள். ஊர் அல்லது பெயரைத் தட்டச்சு செய்யுங்கள்.', $branchCount, $officerCount)) ?></p>
      <label class="finder-box"><span class="sr"><?= e(t('ஊர் அல்லது பெயர்')) ?></span>
        <input type="search" id="finderQ" placeholder="<?= e(t('எ.கா. வழுத்தூர்')) ?>" autocomplete="off" aria-controls="finderList">
      </label>
      <ul class="finder-list" id="finderList" aria-live="polite"></ul>
      <p class="finder-hint" id="finderHint"><a href="/branches"><?= e(t('கிளைகளின் முழுப் பட்டியலைப் பாருங்கள்')) ?></a></p>
      <script type="application/json" id="finderData"><?= json_encode(array_map(fn($r) => [
        'n' => $r['n'], 'p' => $r['p'], 'r' => t($r['r']), 's' => $r['b'] . ' ' . $r['r'],
        'b' => $r['b'] === 'மாவட்டம்' ? t('மாவட்டம்') : branch_label($r['b']),
      ], $directory), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </div>

    <div class="leaders-mini">
      <h2 class="rule-h"><?= e(t('மாவட்ட நிர்வாகம்')) ?></h2>
      <?php foreach ($leaders as $l): ?>
        <div class="lm">
          <p class="lm-role"><?= e(t($l['role'] ?? '')) ?></p>
          <p class="lm-name"><?= e($l['name']) ?></p>
          <p class="lm-num"><a href="tel:<?= e(tel($l['phone'])) ?>"><?= e(fmt_phone($l['phone'])) ?></a><?php contact_buttons($l['phone']); ?></p>
        </div>
      <?php endforeach; ?>
      <a class="more" href="/executives"><?= e(t('முழு நிர்வாகக் குழு')) ?></a>
    </div>
  </section>
</div>

<?php if (!empty($policy['subtitle'])): ?>
  <section class="creed pattern-soft" aria-label="<?= e(t('கொள்கை')) ?>">
    <div class="wrap">
      <blockquote><p><?= e(t($policy['subtitle'] ?? '')) ?></p></blockquote>
      <a class="more" href="/policy"><?= e(t('தவ்ஹீத் ஜமாஅத்தின் கொள்கைகள்')) ?></a>
    </div>
  </section>
<?php endif; ?>
