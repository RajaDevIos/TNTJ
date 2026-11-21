<?php require_once APP_PATH . '/views/partials.php';
$url = config('app_url') . '/news/' . rawurlencode($post['slug']);
$bodyHasImage = $post['image'] && str_contains((string)$post['body'], (string)$post['image']); ?>
<article class="article">
  <header class="wrap article-head">
    <nav class="crumbs" aria-label="<?= e(t('நீங்கள் இருக்கும் இடம்')) ?>"><a href="/"><?= e(t('முகப்பு')) ?></a><a href="/news"><?= e(t('செய்திகள்')) ?></a></nav>
    <?php if ($post['category']): ?><a class="kicker" href="/news?c=<?= urlencode(t($post['category'] ?? '')) ?>"><?= e(t($post['category'] ?? '')) ?></a><?php endif; ?>
    <h1><?= e($post['title']) ?></h1>
    <p class="byline">
      <?php if ($post['branch_name']): ?><a href="/branches/<?= (int)$post['branch_id'] ?>"><?= e(branch_label($post['branch_name'])) ?></a><?php endif; ?>
      <time datetime="<?= e(substr($post['published_at'], 0, 10)) ?>"><?= e(ta_date($post['published_at'])) ?></time>
    </p>
  </header>
  <?php if ($post['image'] && !$bodyHasImage): ?><figure class="wrap article-fig"><img class="vt-hero" src="<?= e(bimg($post['image'], 1600)) ?>" alt=""></figure><?php endif; ?>
  <div class="wrap"><div class="prose article-body"><?= $bodyHasImage ? preg_replace('~<img\b~i', '<img class="vt-hero"', blogger_html(safe_html($post['body'])), 1) : blogger_html(safe_html($post['body'])) ?></div></div>
  <div class="wrap"><div class="share article-body">
    <a class="btn btn-wa" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . $url) ?>"><?= icon('whatsapp') ?><?= e(t('WhatsApp-ல் பகிர்')) ?></a>
    <a class="btn btn-line" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($url) ?>">Facebook</a>
  </div></div>
</article>
<?php if ($related): ?>
<section class="wrap page">
  <h2 class="rule-h"><?= e(t('இதே போன்றவை')) ?></h2>
  <div class="tiles"><?php foreach ($related as $n) news_tile($n); ?></div>
</section>
<?php endif; ?>
