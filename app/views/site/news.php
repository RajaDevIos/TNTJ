<?php require_once APP_PATH . '/views/partials.php';
$heading = $cat !== '' ? $cat : 'செய்திகள்';
if ($branch) foreach ($branchList as $b) if ((int)$b['id'] === $branch) $heading = t('%s செய்திகள்', branch_label($b['name']));
page_head($heading, $q !== '' ? t('“%s” என்று தேடியதில் %d பதிவுகள்.', $q, $total) : t('%d பதிவுகள், புதியவை முதலில்.', $total), 'செய்திகள்');
$qs = fn(array $over) => '?' . http_build_query(array_filter(array_merge(['q' => $q, 'c' => $cat, 'branch' => $branch ?: null], $over))); ?>
<div class="wrap page with-rail">
  <div>
    <form class="filters" method="get" role="search">
      <label class="search-line"><span class="sr"><?= e(t('தலைப்பில் தேடுக')) ?></span><input type="search" name="q" placeholder="<?= e(t('தலைப்பில் உள்ள சொல்')) ?>" value="<?= e($q) ?>"></label>
      <?php if ($cat !== ''): ?><input type="hidden" name="c" value="<?= e($cat) ?>"><?php endif; ?>
      <label class="select-line"><span class="sr"><?= e(t('கிளை')) ?></span>
        <select name="branch" onchange="this.form.submit()">
          <option value=""><?= e(t('எல்லாக் கிளைகளும்')) ?></option>
          <?php foreach ($branchList as $b): ?><option value="<?= (int)$b['id'] ?>"<?= $branch === (int)$b['id'] ? ' selected' : '' ?>><?= e(t($b['name'])) ?> (<?= (int)$b['n'] ?>)</option><?php endforeach; ?>
        </select>
      </label>
      <button class="btn"><?= e(t('தேடு')) ?></button>
    </form>
    <?php if (!$news) empty_state($q !== '' ? 'இந்தச் சொல் உள்ள பதிவுகள் இல்லை. கிளையின் பெயரைக் கொண்டு தேடிப் பாருங்கள்.' : 'இந்தப் பிரிவில் பதிவுகள் இல்லை.', '', ['/news', 'எல்லாச் செய்திகளும்']); ?>
    <div class="river"><?php foreach ($news as $n) news_row($n); ?></div>
    <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="<?= e(t('பக்கங்கள்')) ?>">
        <?php if ($page > 1): ?><a class="btn btn-line" href="<?= e($qs(['page' => $page - 1])) ?>"><?= e(t('புதியவை')) ?></a><?php else: ?><span></span><?php endif; ?>
        <span><?= e(t('பக்கம்')) ?> <?= $page ?> / <?= $pages ?></span>
        <?php if ($page < $pages): ?><a class="btn btn-line" href="<?= e($qs(['page' => $page + 1])) ?>"><?= e(t('பழையவை')) ?></a><?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
  <aside class="rail">
    <h2 class="rule-h"><?= e(t('பணி வகை')) ?></h2>
    <ul class="facet">
      <li><a href="<?= e($qs(['c' => null, 'page' => null])) ?>"<?= $cat === '' ? ' aria-current="true"' : '' ?>><?= e(t('எல்லாம்')) ?></a></li>
      <?php foreach ($activities as $a): ?>
        <li><a href="<?= e($qs(['c' => $a['name'], 'page' => null])) ?>"<?= $cat === $a['name'] ? ' aria-current="true"' : '' ?>><?= e(t($a['name'])) ?><span><?= (int)$a['n'] ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </aside>
</div>
