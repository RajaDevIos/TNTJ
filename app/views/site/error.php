<?php require_once APP_PATH . '/views/partials.php'; ?>
<div class="wrap page err-page">
  <p class="err-code"><?= (int)$status ?></p>
  <h1><?= e(t($title)) ?></h1>
  <p class="dek"><?= e(t($message)) ?>. <?= e(t('முகவரியைச் சரிபார்க்கவும், அல்லது கீழே உள்ள பக்கங்களில் தேடுங்கள்.')) ?></p>
  <p class="err-links"><a class="btn" href="/"><?= e(t('முகப்பு')) ?></a><a class="btn btn-line" href="/branches"><?= e(t('கிளைகள்')) ?></a><a class="btn btn-line" href="/news"><?= e(t('செய்திகள்')) ?></a></p>
</div>
