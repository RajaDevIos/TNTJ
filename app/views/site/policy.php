<?php require_once APP_PATH . '/views/partials.php';
page_head($page['title'] ?? 'கொள்கை', $page['subtitle'] ?? '', 'ஜமாஅத்'); ?>
<div class="wrap page">
  <div class="measure">
    <?php if (!empty($page['body'])): ?><div class="prose intro"><?= safe_html(t($page['body'])) ?></div><?php endif; ?>
    <ol class="tenets">
      <?php foreach ($points as $p): ?>
        <li>
          <h2><?= e(t(preg_replace('/^\d+\.\s*/', '', $p['title']))) ?></h2>
          <p><?= nl2br(e(t($p['body']))) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</div>
