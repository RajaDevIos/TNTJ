<?php require_once APP_PATH . '/views/partials.php';
$helpline = setting('blood_helpline');
$opts = fn() => implode('', array_map(fn($g) => '<option>' . e($g) . '</option>', $groups)); ?>
<section class="blood-band blood-band-page pattern">
  <div class="wrap blood-band-in">
    <div>
      <nav class="crumbs" aria-label="<?= e(t('நீங்கள் இருக்கும் இடம்')) ?>"><a href="/"><?= e(t('முகப்பு')) ?></a><span><?= e(t('சேவைகள்')) ?></span></nav>
      <h1><?= e(t('இரத்ததானம் தேவை & கொடையாளர்')) ?></h1>
      <p><?= e(t('அவசரத் தேவைக்கு மருத்துவரணியை நேரடியாக அழையுங்கள். நோயாளியின் பெயர், மருத்துவமனை, இரத்த வகை, எத்தனை யூனிட் என்பதைச் சொல்லுங்கள்.')) ?></p>
    </div>
    <a class="blood-number" href="tel:<?= e(tel($helpline)) ?>"><span><?= e(t('மருத்துவரணி')) ?></span><?= e($helpline) ?></a>
    <div class="blood-band-act">
      <a class="btn btn-red" href="#need"><?= e(t('கோரிக்கையைப் பதிவு செய்க')) ?></a>
      <a class="btn btn-line-inv" href="#register"><?= e(t('கொடையாளராகச் சேர')) ?></a>
    </div>
  </div>
</section>

<div class="wrap page">
  <h2 class="rule-h"><?= e(t('தற்போதைய தேவைகள்')) ?></h2>
  <?php if (!$requests): ?>
    <p class="muted"><?= e(t('இப்போது திறந்த கோரிக்கைகள் இல்லை.')) ?></p>
  <?php else: ?>
    <table class="dir needs">
      <thead><tr><th scope="col"><?= e(t('வகை')) ?></th><th scope="col"><?= e(t('மருத்துவமனை')) ?></th><th scope="col"><?= e(t('எப்போது')) ?></th><th scope="col"><?= e(t('தொடர்பு')) ?></th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td class="bg-cell"><?= e($r['blood_group']) ?></td>
            <th scope="row"><?= e($r['hospital']) ?><small><?= e(t('%1$d யூனிட், நோயாளி %2$s', (int)$r['units'], $r['patient_name'])) ?><?= $r['notes'] ? '. ' . e($r['notes']) : '' ?></small></th>
            <td><?= $r['needed_on'] ? e(ta_date($r['needed_on'])) : e(t('உடனடியாக')) ?></td>
            <td class="dir-act"><span class="dir-sub"><?= e($r['contact_name']) ?></span><?php contact_buttons($r['contact_phone']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <h2 class="rule-h" id="donors"><?= e(t('கொடையாளர்கள்')) ?></h2>
  <nav class="tabs tabs-groups" aria-label="<?= e(t('இரத்த வகை')) ?>">
    <a href="/blood#donors"<?= !$group ? ' aria-current="true"' : '' ?>><?= e(t('எல்லாம்')) ?> <span><?= array_sum($counts) ?></span></a>
    <?php foreach (array_slice($groups, 0, 8) as $g): ?>
      <a href="/blood?group=<?= urlencode($g) ?>#donors"<?= $group === $g ? ' aria-current="true"' : '' ?>><?= e($g) ?> <span><?= (int)($counts[$g] ?? 0) ?></span></a>
    <?php endforeach; ?>
  </nav>
  <?php if (!$donors): ?>
    <?php empty_state($group ? t('%s வகைக் கொடையாளர்கள் இன்னும் பதிவு செய்யவில்லை. மருத்துவரணியை அழைத்தால் அவர்கள் ஏற்பாடு செய்வார்கள்.', $group) : 'கொடையாளர் பட்டியல் தயாராகிறது. முதல் கொடையாளராக நீங்கள் பதிவு செய்யுங்கள்.', '', ['#register', 'கொடையாளராகப் பதிவு செய்க']); ?>
  <?php else: ?>
    <div class="filters"><label class="search-line"><span class="sr"><?= e(t('ஊர் அல்லது பெயர்')) ?></span><input type="search" placeholder="<?= e(t('ஊர் அல்லது பெயர்')) ?>" data-filter=".dir-row"></label></div>
    <table class="dir">
      <thead><tr><th scope="col"><?= e(t('வகை')) ?></th><th scope="col"><?= e(t('பெயர்')) ?></th><th scope="col"><?= e(t('ஊர்')) ?></th><th scope="col"><?= e(t('கடைசி தானம்')) ?></th><th scope="col"><span class="sr"><?= e(t('அழைக்க')) ?></span></th></tr></thead>
      <tbody>
        <?php foreach ($donors as $d): ?>
          <tr class="dir-row<?= $d['available'] ? '' : ' dim' ?>" data-text="<?= e($d['name'] . ' ' . $d['area'] . ' ' . $d['branch_name']) ?>">
            <td class="bg-cell"><?= e($d['blood_group']) ?></td>
            <th scope="row"><?= e($d['name']) ?><?= $d['available'] ? '' : '<small>' . e(t('தற்போது தர இயலாது')) . '</small>' ?></th>
            <td class="dir-sub"><?= e(trim(($d['area'] ?? '') . ($d['branch_name'] ? ', ' . $d['branch_name'] : ''), ', ')) ?></td>
            <td class="dir-sub"><?= $d['last_donated'] ? e(ta_date($d['last_donated'])) : '' ?></td>
            <td class="dir-act"><?php contact_buttons($d['phone']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="no-results" hidden><?= e(t('இந்த ஊரில் கொடையாளர் இல்லை.')) ?></p>
  <?php endif; ?>

  <div class="forms">
    <form class="form" method="post" action="/blood/request" id="need">
      <h2><?= e(t('இரத்தக் கோரிக்கை')) ?></h2>
      <p class="muted"><?= e(t('மருத்துவரணி சரிபார்த்த பின் இது இந்தப் பக்கத்திலும் முகப்பிலும் வெளியிடப்படும்.')) ?></p>
      <?php form_guard(); ?>
      <div class="grid2">
        <label><?= e(t('நோயாளியின் பெயர்')) ?><input name="patient_name" required maxlength="160"></label>
        <label><?= e(t('இரத்த வகை')) ?><select name="blood_group" required><option value=""><?= e(t('தேர்வு செய்க')) ?></option><?= $opts() ?></select></label>
        <label><?= e(t('எத்தனை யூனிட்')) ?><input name="units" type="number" min="1" max="20" value="1" inputmode="numeric"></label>
        <label><?= e(t('எந்த நாள்')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="needed_on" type="date"></label>
      </div>
      <label><?= e(t('மருத்துவமனை')) ?><input name="hospital" required maxlength="255"></label>
      <div class="grid2">
        <label><?= e(t('உங்கள் பெயர்')) ?><input name="contact_name" required maxlength="160" autocomplete="name"></label>
        <label><?= e(t('கைபேசி எண்')) ?><input name="contact_phone" type="tel" inputmode="tel" required pattern="[0-9+ \-]{10,15}" autocomplete="tel"></label>
      </div>
      <label><?= e(t('குறிப்பு')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><textarea name="notes" rows="2" maxlength="1000"></textarea></label>
      <button class="btn btn-red"><?= e(t('கோரிக்கையை அனுப்பு')) ?></button>
    </form>

    <form class="form" method="post" action="/blood/donor" id="register">
      <h2><?= e(t('கொடையாளராகப் பதிவு')) ?></h2>
      <p class="muted"><?= e(t('18 முதல் 65 வயதுடைய, உடல்நலமுள்ள எவரும் பதிவு செய்யலாம். சரிபார்த்த பின் உங்கள் பெயர் பட்டியலில் சேரும்.')) ?></p>
      <?php form_guard(); ?>
      <div class="grid2">
        <label><?= e(t('பெயர்')) ?><input name="name" required maxlength="160" autocomplete="name"></label>
        <label><?= e(t('இரத்த வகை')) ?><select name="blood_group" required><option value=""><?= e(t('தேர்வு செய்க')) ?></option><?= $opts() ?></select></label>
        <label><?= e(t('கைபேசி எண்')) ?><input name="phone" type="tel" inputmode="tel" required pattern="[0-9+ \-]{10,15}" autocomplete="tel"></label>
        <label><?= e(t('வயது')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="age" type="number" min="18" max="65" inputmode="numeric"></label>
        <label><?= e(t('ஊர்')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="area" maxlength="160"></label>
        <label><?= e(t('கிளை')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><select name="branch_id"><option value=""><?= e(t('தேர்வு செய்க')) ?></option><?php foreach ($branchList as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e(t($b['name'])) ?></option><?php endforeach; ?></select></label>
      </div>
      <label><?= e(t('கடைசியாக இரத்தம் கொடுத்த நாள்')) ?> <span class="opt"><?= e(t('விருப்பம்')) ?></span><input name="last_donated" type="date"></label>
      <button class="btn"><?= e(t('பதிவு செய்')) ?></button>
    </form>
  </div>
</div>
