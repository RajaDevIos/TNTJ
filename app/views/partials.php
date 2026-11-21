<?php
// Reusable view helpers.
require_once APP_PATH . '/views/icons.php';

/**
 * Page header: an ink band carrying the district's geometric star pattern, the page title,
 * and (when the page has them) three real photos from branch work related to the page.
 */
function page_head(string $title, string $lead = '', string $kicker = '', ?array $photos = null): void {
  $photos ??= $GLOBALS['head_photos'] ?? []; ?>
  <header class="phead<?= $photos ? ' has-photos' : '' ?>">
    <div class="wrap phead-in">
      <div class="phead-text">
        <nav class="crumbs" aria-label="<?= e(t('நீங்கள் இருக்கும் இடம்')) ?>"><a href="/"><?= e(t('முகப்பு')) ?></a><?php if ($kicker): ?><span><?= e(t($kicker)) ?></span><?php endif; ?></nav>
        <h1><?= e(t($title)) ?></h1>
        <?php if ($lead): ?><p class="dek"><?= e(t($lead)) ?></p><?php endif; ?>
      </div>
      <?php if ($photos): ?>
        <div class="phead-photos">
          <?php foreach ($photos as $i => $ph): ?>
            <a class="ph ph-<?= $i ?>" href="/news/<?= e(rawurlencode($ph['slug'])) ?>">
              <img src="<?= e(bimg($ph['image'], $i ? 640 : 1200)) ?>" alt="" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
              <span class="ph-cap"><?= e(photo_caption($ph)) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </header>
<?php }

/** "<branch> கிளை, <activity>": who and what is in the photo. */
function photo_caption(array $n): string
{
    $b = branch_label($n['branch_name'] ?? '');
    $c = t(trim((string)($n['category'] ?? '')));
    return $b && $c ? "$b, $c" : ($b ?: ($c ?: (string)$n['title']));
}

/** Call + WhatsApp. $label shows words next to the icons (used where there is room). */
function contact_buttons(?string $phone, bool $label = false): void { if (!$phone) return; ?>
  <span class="cbtns">
    <a class="cbtn cbtn-call" href="tel:<?= e(tel($phone)) ?>" aria-label="<?= e(t('%s அழைக்க', fmt_phone($phone))) ?>"><?= icon('phone') ?><?php if ($label): ?><span><?= e(t('அழைக்க')) ?></span><?php endif; ?></a>
    <a class="cbtn cbtn-wa" href="https://wa.me/<?= e(wa($phone)) ?>" target="_blank" rel="noopener" aria-label="<?= e(fmt_phone($phone)) ?> WhatsApp"><?= icon('whatsapp') ?><?php if ($label): ?><span>WhatsApp</span><?php endif; ?></a>
  </span>
<?php }

function empty_state(string $msg, string $icon = '', ?array $action = null): void { ?>
  <div class="empty"><p><?= e(t($msg)) ?></p><?php if ($action): ?><a class="btn btn-line" href="<?= e($action[0]) ?>"><?= e(t($action[1])) ?></a><?php endif; ?></div>
<?php }

function form_guard(): void { ?>
  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
  <div class="hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
<?php }

/** The red label above a headline: what kind of story it is. */
function kicker(?string $k): string { return $k ? '<span class="kicker">' . e(t($k)) . '</span>' : ''; }

/** Byline: branch and date as separate spans (spacing comes from CSS, not punctuation). */
function byline(array $n): string
{
    $out = '<span class="byline">';
    if (!empty($n['branch_name'])) $out .= '<span>' . e(branch_label($n['branch_name'])) . '</span>';
    $out .= '<time datetime="' . e(substr((string)$n['published_at'], 0, 10)) . '">' . e(ta_date($n['published_at'])) . '</time></span>';
    return $out;
}

/**
 * Display headline. Old posts are titled "<activity> - <branch> கிளை", which repeats
 * what the kicker and byline already say. Rewrite those as one Tamil sentence
 * ("<branch> கிளை சார்பில் <activity>") and drop the duplicate kicker and branch.
 * @return array{0:string,1:bool} [headline, rewritten]
 */
function headline(array $n): array
{
    $t = (string)$n['title'];
    $b = (string)($n['branch_name'] ?? '');
    $c = (string)($n['category'] ?? '');
    // In English/Arabic every branch post is described from its translated activity and branch.
    if ($b !== '' && $c !== '' && (lang() !== 'ta' || preg_match('/^(.+?)\s+-\s+.+$/u', $t))) {
        // ta "<branch> கிளை சார்பில் <activity>", en "<activity> by <branch> branch", ar "<activity> - فرع <branch>"
        return [t('%1$s சார்பில் %2$s', branch_label($b), t($c)), true];
    }
    return [$t, false];
}

/** River item: text first, thumbnail right. */
function news_row(array $n, bool $thumb = true): void { [$h, $re] = headline($n); ?>
  <article class="river-item">
    <div>
      <?= kicker($re ? null : ($n['category'] ?? null)) ?>
      <h3><a href="/news/<?= e(rawurlencode($n['slug'])) ?>"><?= e($h) ?></a></h3>
      <?= byline($re ? ['published_at' => $n['published_at']] : $n) ?>
    </div>
    <?php if ($thumb && !empty($n['image'])): ?><img src="<?= e(bimg($n['image'], 400)) ?>" alt="" loading="lazy"><?php endif; ?>
  </article>
<?php }

/** Grid story: image on top. */
function news_tile(array $n): void { [$h, $re] = headline($n); ?>
  <article class="tile">
    <a href="/news/<?= e(rawurlencode($n['slug'])) ?>" class="tile-img" tabindex="-1" aria-hidden="true"><?php if (!empty($n['image'])): ?><img src="<?= e(bimg($n['image'], 640)) ?>" alt="" loading="lazy"><?php endif; ?></a>
    <?= kicker($re ? null : ($n['category'] ?? null)) ?>
    <h3><a href="/news/<?= e(rawurlencode($n['slug'])) ?>"><?= e($h) ?></a></h3>
    <?= byline($re ? ['published_at' => $n['published_at']] : $n) ?>
  </article>
<?php }

function news_card(array $n): void { news_row($n); }

function initials(string $name): string
{
    $clean = trim(preg_replace('/^([A-Za-z]\.\s*)+/u', '', $name)) ?: $name;
    return mb_substr($clean, 0, 1);
}

function avatar(array $p, string $size = ''): string
{
    if (empty($p['photo'])) return '';
    return '<img class="portrait ' . $size . '" src="' . e(media($p['photo'])) . '" alt="">';
}
