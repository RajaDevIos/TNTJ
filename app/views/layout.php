<?php
use App\Controllers\Site;
require_once APP_PATH . '/views/icons.php';
$current = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$siteName = t(setting('site_name', 'தமிழ்நாடு தவ்ஹீத் ஜமாஅத்'));
$tagline = t(setting('site_tagline', 'தஞ்சை வடக்கு மாவட்டம்'));
$pageTitle = isset($title) && $title ? t($title) . " | $tagline - TNTJ" : "$siteName - $tagline";
$L = \App\I18n::LANGS; $lang = lang();
$flash = flash();
$on = fn(string $href) => $href === '/' ? $current === '/' : ($current === $href || str_starts_with($current, $href . '/'));
$groupOn = fn(array $idx) => (bool)array_filter($idx, fn($i) => $on(Site::MENU[$i][0]));
$helpline = setting('blood_helpline');
$isArticle = str_starts_with($current, '/news/');
$days = day_names();
?><!doctype html>
<html lang="<?= e($lang) ?>" dir="<?= e(\App\I18n::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription ?? t('தமிழ்நாடு தவ்ஹீத் ஜமாஅத் தஞ்சை வடக்கு மாவட்டம்: கிளைச் செய்திகள், நிர்வாகிகள், இரத்ததானம், திருமண தகவல், மக்தப் மதரஸா, மாதாந்திர புள்ளிப்பட்டியல்.')) ?>">
<?php foreach ($L as $code => $_): ?><link rel="alternate" hreflang="<?= $code ?>" href="<?= e(config('app_url') . \App\I18n::switchUrl($code)) ?>">
<?php endforeach; ?>
<meta property="og:title" content="<?= e($pageTitle) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e(media($ogImage)) ?>"><?php endif; ?>
<meta name="theme-color" content="#123138">
<link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anek+Tamil:wdth,wght@75..100,400..700&family=Noto+Serif+Tamil:wdth,wght@62.5..100,500..700<?php if ($lang === 'en'): ?>&family=Noto+Serif:wdth,wght@62.5..100,500..700<?php elseif ($lang === 'ar'): ?>&family=Noto+Naskh+Arabic:wght@500..700&family=Noto+Sans+Arabic:wdth,wght@75..100,400..700<?php endif; ?>&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<script>
/* Motion is opt-in: only when the visitor allows it. If site.js never starts, show everything after 2.5s. */
if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
  document.documentElement.classList.add('motion');
  setTimeout(function () { if (!window.__motion) document.documentElement.classList.remove('motion'); }, 2500);
}
</script>
</head>
<body<?= $isArticle ? ' class="is-article"' : '' ?>>
<?= icon_sprite() ?>
<a class="skip" href="#main"><?= e(t('உள்ளடக்கத்திற்குச் செல்')) ?></a>

<header class="mast">
  <div class="wrap util">
    <span class="util-date"><?= e($days[(int)date('w')] . ', ' . ta_date(date('Y-m-d'))) ?></span>
    <a class="util-blood" href="tel:<?= e(tel($helpline)) ?>"><?= e(t('இரத்த உதவி')) ?> <b><?= e($helpline) ?></b></a>
    <a href="tel:<?= e(tel(setting('phone'))) ?>"><?= e(t('மாவட்ட அலுவலகம்')) ?> <b><?= e(setting('phone')) ?></b></a>
  </div>

  <div class="wrap mast-row">
    <a href="/" class="wordmark" aria-label="<?= e($siteName . ', ' . $tagline . ': ' . t('முகப்பு')) ?>">
      <img src="<?= asset('img/logo.svg') ?>" alt="" width="56" height="56">
      <span><b><?= e($siteName) ?></b><span><?= e($tagline) ?></span></span>
    </a>
    <div class="mast-act">
      <nav class="langs" aria-label="Language / மொழி / اللغة">
        <?php foreach ($L as $code => $info): ?>
          <a href="<?= e(\App\I18n::switchUrl($code)) ?>" lang="<?= $code ?>" hreflang="<?= $code ?>"<?= $code === $lang ? ' aria-current="true"' : '' ?> aria-label="<?= e($info['label']) ?>"><span class="lf"><?= e($info['label']) ?></span><span class="ls" aria-hidden="true"><?= e($info['short']) ?></span></a>
        <?php endforeach; ?>
      </nav>
      <button type="button" class="icon-btn" data-search-toggle aria-expanded="false" aria-controls="searchbar" aria-label="<?= e(t('செய்திகளைத் தேடுக')) ?>"><?= icon('search') ?></button>
      <a class="btn btn-red mast-blood" href="/blood"><?= e(t('இரத்தம் தேவை')) ?></a>
    </div>
  </div>

  <nav class="wrap nav" aria-label="<?= e(t('முதன்மை மெனு')) ?>">
    <ul>
      <li><a href="/" class="nav-a<?= $on('/') ? ' on' : '' ?>"<?= $on('/') ? ' aria-current="page"' : '' ?>><?= e(t('முகப்பு')) ?></a></li>
      <?php foreach (Site::NAV as $gi => [$label, $idx]): ?>
        <li class="dd">
          <button type="button" class="nav-a<?= $groupOn($idx) ? ' on' : '' ?>" aria-expanded="false" aria-controls="dd<?= $gi ?>"><?= e(t($label)) ?><?= icon('chev', 'i i-xs') ?></button>
          <ul class="dd-panel" id="dd<?= $gi ?>">
            <?php foreach ($idx as $i): [$h, , $l, $d] = Site::MENU[$i]; ?>
              <li><a href="<?= e($h) ?>"<?= $on($h) ? ' aria-current="page"' : '' ?>><?= e(t($l)) ?><small><?= e(t($d)) ?></small></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <form id="searchbar" class="searchbar" action="/news" method="get" role="search" hidden>
    <div class="wrap">
      <label for="sq" class="sr"><?= e(t('செய்திகளைத் தேடுக')) ?></label>
      <input id="sq" type="search" name="q" placeholder="<?= e(t('கிளை, நிகழ்ச்சி அல்லது தலைப்பு')) ?>" value="<?= e($_GET['q'] ?? '') ?>">
      <button class="btn"><?= e(t('தேடு')) ?></button>
    </div>
  </form>
  <?php if ($isArticle): ?><div class="readbar" aria-hidden="true"></div><?php endif; ?>
</header>

<?php if ($flash): ?>
  <div class="wrap"><p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></p></div>
<?php endif; ?>

<main id="main"><?= $content ?></main>

<footer class="ftr">
  <div class="wrap">
    <p class="ftr-mark"><?= e($siteName) ?><span><?= e($tagline) ?></span></p>
    <div class="ftr-grid">
      <div class="ftr-contact">
        <p><?= e(t(setting('address'))) ?></p>
        <p><?= e(t('அலுவலகம்')) ?> <a href="tel:<?= e(tel(setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p>
        <p><?= e(t('இரத்த உதவி')) ?> <a href="tel:<?= e(tel($helpline)) ?>"><?= e($helpline) ?></a></p>
        <?php if (setting('email')): ?><p><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p><?php endif; ?>
      </div>
      <?php foreach (Site::NAV as [$label, $idx]): ?>
        <div><h2><?= e(t($label)) ?></h2><ul><?php foreach ($idx as $i): ?><li><a href="<?= e(Site::MENU[$i][0]) ?>"><?= e(t(Site::MENU[$i][2])) ?></a></li><?php endforeach; ?></ul></div>
      <?php endforeach; ?>
    </div>
    <div class="ftr-base">
      <span>© <?= date('Y') ?> <?= e($siteName) ?>, <?= e($tagline) ?></span>
      <a href="/admin"><?= e(t('நிர்வாகி உள்நுழைவு')) ?></a>
    </div>
  </div>
</footer>

<nav class="tabbar" aria-label="<?= e(t('விரைவு மெனு')) ?>">
  <a href="/"<?= $on('/') ? ' aria-current="page"' : '' ?>><?= icon('home') ?><span><?= e(t('முகப்பு')) ?></span></a>
  <a href="/branches"<?= $on('/branches') ? ' aria-current="page"' : '' ?>><?= icon('building') ?><span><?= e(t('கிளைகள்')) ?></span></a>
  <a href="/blood" class="tab-blood"<?= $on('/blood') ? ' aria-current="page"' : '' ?>><?= icon('drop') ?><span><?= e(t('இரத்தம்')) ?></span></a>
  <a href="/news"<?= $on('/news') ? ' aria-current="page"' : '' ?>><?= icon('news') ?><span><?= e(t('செய்திகள்')) ?></span></a>
  <button type="button" data-sheet-open aria-controls="sheet" aria-expanded="false"><?= icon('menu') ?><span><?= e(t('மெனு')) ?></span></button>
</nav>
<div class="sheet" id="sheet" role="dialog" aria-modal="true" aria-label="<?= e(t('எல்லாப் பக்கங்களும்')) ?>" hidden>
  <div class="sheet-head">
    <span class="sheet-title"><?= e($siteName) ?></span>
    <button type="button" class="icon-btn" data-sheet-close aria-label="<?= e(t('மூடு')) ?>"><?= icon('close') ?></button>
  </div>
  <nav class="langs langs-sheet" aria-label="Language / மொழி / اللغة">
    <?php foreach ($L as $code => $info): ?>
      <a href="<?= e(\App\I18n::switchUrl($code)) ?>" lang="<?= $code ?>" hreflang="<?= $code ?>"<?= $code === $lang ? ' aria-current="true"' : '' ?>><?= e($info['label']) ?></a>
    <?php endforeach; ?>
  </nav>
  <form class="sheet-search" action="/news" method="get" role="search"><input type="search" name="q" placeholder="<?= e(t('செய்திகளைத் தேடுக')) ?>" aria-label="<?= e(t('செய்திகளைத் தேடுக')) ?>"><button class="btn"><?= e(t('தேடு')) ?></button></form>
  <ul class="sheet-list">
    <li><a href="/"<?= $on('/') ? ' aria-current="page"' : '' ?>><?= e(t('முகப்பு')) ?></a></li>
  </ul>
  <?php foreach (Site::NAV as [$label, $idx]): ?>
    <h2><?= e(t($label)) ?></h2>
    <ul class="sheet-list">
      <?php foreach ($idx as $i): [$h, , $l] = Site::MENU[$i]; ?>
        <li><a href="<?= e($h) ?>"<?= $on($h) ? ' aria-current="page"' : '' ?>><?= e(t($l)) ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>
</div>

<script>window.T = <?= json_encode([
  'call' => t('%s அழைக்க'), 'district' => t('மாவட்டம்'), 'none' => t('“%s” என்ற பெயரில் யாரும் இல்லை. ஊரின் முதல் சில எழுத்துகளை மட்டும் தட்டச்சு செய்து பாருங்கள்.'),
  'pause' => t('படங்கள் தானாக மாறுவதை நிறுத்து'), 'play' => t('படங்களைத் தானாக மாற்று'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="<?= asset('js/site.js') ?>" defer></script>
<script src="<?= asset('js/motion.js') ?>" defer></script>
</body>
</html>
