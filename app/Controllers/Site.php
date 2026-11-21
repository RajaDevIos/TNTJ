<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, HttpException, Router};
use App\Resources;

/** Server-rendered public website. Reads MySQL directly; public submissions are stored unapproved. */
final class Site
{
    /** The client's 14 menu items: [href, icon, label, one-line description]. */
    public const MENU = [
        ['/',                 'home',     'முகப்பு', ''],
        ['/policy',           'book',     'கொள்கை', 'தவ்ஹீத் ஜமாஅத்தின் அடிப்படைக் கொள்கைகள்'],
        ['/executives',       'users',    'மாவட்ட நிர்வாகிகள்', 'தலைவர், செயலாளர், பொருளாளர் மற்றும் அணிச் செயலாளர்கள்'],
        ['/daees',            'mic',      'மாவட்ட தாயிக்கள்', 'பயான் மற்றும் தர்பியாவுக்கு அழைக்க'],
        ['/branches',         'building', 'கிளை நிர்வாகிகள்', 'ஒவ்வொரு கிளையின் பொறுப்பாளர்கள் மற்றும் எண்கள்'],
        ['/colleges',         'school',   'பெண்கள் இஸ்லாமிய கல்லூரிகள்', 'படிப்புகள், தகுதி, சேர்க்கை'],
        ['/maktab',           'mosque',   'மக்தப் மதரஸா', 'பாடத்திட்டம், விதிமுறைகள், விண்ணப்பம்'],
        ['/blood',            'drop',     'இரத்ததானம் தேவை & கொடையாளர்', 'இரத்தம் கேட்க அல்லது கொடையாளராகப் பதிவு செய்ய'],
        ['/matrimony',        'rings',    'திருமண தகவல்', 'வரதட்சணை இல்லாத நபிவழித் திருமணம்'],
        ['/downloads',        'folder',   'படிவங்கள் & தரவிறக்கம்', 'விண்ணப்பப் படிவங்கள் மற்றும் கோப்புகள்'],
        ['/events',           'calendar', 'மாவட்ட நிகழ்வுகள்', 'பொதுக்கூட்டங்கள், முகாம்கள், தர்பியா'],
        ['/monthly-reports',  'chart',    'மாதாந்திர புள்ளி பட்டியல்', 'கிளைகளின் மாதாந்திரச் செயல்பாடு'],
        ['/contact',          'phone',    'தொடர்புக்கு', 'மாவட்ட அலுவலகம் மற்றும் செய்தி அனுப்ப'],
        ['/links',            'link',     'அதிகாரப்பூர்வ இணைப்புகள்', 'மாநிலத் தலைமை மற்றும் ஊடகங்கள்'],
    ];

    /** How the 14 items are grouped in the header (indexes into MENU). */
    public const NAV = [
        ['ஜமாஅத்', [1, 2, 3, 4]],
        ['கல்வி', [5, 6]],
        ['சேவைகள்', [7, 8, 9]],
        ['செய்திகள்', [10, 11]],
        ['தொடர்பு', [12, 13]],
    ];

    public static function routes(Router $r): void
    {
        $r->get('/', [self::class, 'home']);
        $r->get('/policy', [self::class, 'policy']);
        $r->get('/executives', [self::class, 'executives']);
        $r->get('/daees', [self::class, 'daees']);
        $r->get('/branches', [self::class, 'branches']);
        $r->get('/branches/{id}', [self::class, 'branch']);
        $r->get('/colleges', [self::class, 'colleges']);
        $r->get('/maktab', [self::class, 'maktab']);
        $r->get('/blood', [self::class, 'blood']);
        $r->post('/blood/donor', [self::class, 'bloodDonorSubmit']);
        $r->post('/blood/request', [self::class, 'bloodRequestSubmit']);
        $r->get('/matrimony', [self::class, 'matrimony']);
        $r->post('/matrimony', [self::class, 'matrimonySubmit']);
        $r->get('/downloads', [self::class, 'downloads']);
        $r->get('/downloads/{id}', [self::class, 'downloadGo']);
        $r->get('/events', [self::class, 'events']);
        $r->get('/monthly-reports', [self::class, 'reports']);
        $r->get('/contact', [self::class, 'contact']);
        $r->post('/contact', [self::class, 'contactSubmit']);
        $r->get('/links', [self::class, 'links']);
        $r->get('/news', [self::class, 'newsIndex']);
        $r->get('/news/{slug}', [self::class, 'newsShow']);
    }

    public static function render(string $view, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        $GLOBALS['head_photos'] = $data['headPhotos'] ?? ($status === 200 ? self::headPhotos() : []);
        extract($data, EXTR_SKIP);
        ob_start();
        require APP_PATH . "/views/site/$view.php";
        $content = ob_get_clean();
        require APP_PATH . '/views/layout.php';
        exit;
    }

    /**
     * Each inner page opens with real field photos from the district's own posts,
     * chosen by what the page is about (chalkboard dawah for the creed, lectures for daees…).
     * Values are LIKE patterns on the post's activity; '' = latest photos from any branch.
     */
    private const HEAD_PHOTOS = [
        '/policy'          => ['%கரும்பலகை%', '%வெண்பலகை%', '%சுவரொட்டி%'],
        '/executives'      => ['%ஆலோசனை%', '%கலந்துரையாடல்%', '%செயற்குழு%', '%செயல்வீரர்%'],
        '/daees'           => ['%பயான்%', '%தர்பியா%', '%பேச்சாளர்%'],
        '/branches'        => [''],
        '/colleges'        => ['%பெண்கள்%'],
        '/maktab'          => ['%மதரஸா%', '%மக்தப்%', '%போட்டி%', '%நல்லொழுக்க%', '%குர்ஆன்%'],
        '/matrimony'       => ['%திருமண%', '%பெண்கள் பயான்%'],
        '/downloads'       => ['%நோட்டீஸ்%', '%புத்தக%', '%அன்பளிப்பு%'],
        '/events'          => ['%பொதுக்கூட்ட%', '%தெருமுனை%', '%பிரச்சாரம்%', '%மாநாடு%'],
        '/monthly-reports' => ['%கரும்பலகை%', '%பயான்%'],
        '/contact'         => ['%உதவி%', '%சேவை%', '%ஆம்புலன்ஸ்%', '%இரத்த%'],
        '/links'           => ['%காணொளி%', '%மெகா%', '%பிரச்சாரம்%'],
    ];

    public static function headPhotos(?string $path = null, int $n = 3): array
    {
        $path ??= rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
        $pats = self::HEAD_PHOTOS[$path] ?? null;
        if ($pats === null) return [];
        $base = "SELECT n.title, n.slug, n.image, n.category, n.published_at, b.name AS branch_name
                 FROM news n LEFT JOIN branches b ON b.id = n.branch_id
                 WHERE n.published = 1 AND n.image <> '' AND (n.category IS NULL OR n.category <> 'மாதாந்திர புள்ளிப்பட்டியல்')";
        $rows = [];
        if ($pats !== ['']) {
            $where = implode(' OR ', array_fill(0, count($pats), 'n.category LIKE ?'));
            $rows = DB::all("$base AND ($where) ORDER BY n.published_at DESC LIMIT 40", $pats);
        }
        if (count($rows) < $n) $rows = array_merge($rows, DB::all("$base ORDER BY n.published_at DESC LIMIT 40"));
        // one photo per branch so the strip shows the district, not one busy branch
        $seen = []; $out = [];
        foreach ($rows as $r) {
            $k = $r['branch_name'] ?? $r['slug'];
            if (isset($seen[$k])) continue;
            $seen[$k] = true; $out[] = $r;
            if (count($out) === $n) break;
        }
        return $out;
    }

    private static function back(string $to, string $msg, string $type = 'ok'): never
    {
        flash(t($msg), $type);
        header('Location: ' . $to, true, 303);
        exit;
    }

    /** CSRF + honeypot + simple per-IP throttle for public forms. */
    private static function guardForm(string $to): void
    {
        if (!csrf_check()) self::back($to, 'படிவம் காலாவதியானது. மீண்டும் முயற்சிக்கவும்.', 'err');
        if (!empty($_POST['website'])) self::back($to, 'நன்றி!');   // bot filled the hidden field
        $_SESSION['submits'] = array_filter($_SESSION['submits'] ?? [], fn($t) => $t > time() - 600);
        if (count($_SESSION['submits']) >= 5) self::back($to, 'அதிகமான சமர்ப்பிப்புகள். சிறிது நேரம் கழித்து முயற்சிக்கவும்.', 'err');
        $_SESSION['submits'][] = time();
    }

    private static function req(array $keys, string $to): array
    {
        $out = [];
        foreach ($keys as $k => $required) {
            $v = trim((string)($_POST[$k] ?? ''));
            if ($required && $v === '') self::back($to, '"விருப்பம்" எனக் குறிக்கப்படாத எல்லாப் புலங்களையும் நிரப்பவும்.', 'err');
            $out[$k] = $v === '' ? null : mb_substr($v, 0, 2000);
        }
        return $out;
    }

    private static function validPhone(?string $p, string $to): string
    {
        $d = preg_replace('/\D/', '', (string)$p);
        if (strlen($d) < 10 || strlen($d) > 13) self::back($to, 'சரியான 10 இலக்க கைபேசி எண்ணை உள்ளிடவும்.', 'err');
        return $d;
    }

    // ------------------------------------------------------------ pages
    public static function home(): never
    {
        // Phone finder data: every public contact on the site, searchable client-side.
        $directory = array_merge(
            DB::all("SELECT name AS n, role AS r, 'மாவட்டம்' AS b, phone AS p FROM executives WHERE active = 1 AND phone IS NOT NULL ORDER BY sort_order"),
            DB::all("SELECT o.name AS n, o.role AS r, b.name AS b, o.phone AS p FROM branch_officers o JOIN branches b ON b.id = o.branch_id
                     WHERE b.active = 1 AND o.phone IS NOT NULL ORDER BY b.sort_order, o.sort_order"),
            DB::all("SELECT name AS n, 'தாயி' AS r, COALESCE(NULLIF(area,''),'மாவட்டம்') AS b, phone AS p FROM daees WHERE active = 1 AND phone IS NOT NULL ORDER BY sort_order"),
        );
        self::render('home', [
            'title' => null,
            // Branch activity posts (monthly reports are shown separately as the lead).
            'news' => DB::all("SELECT n.id, n.title, n.slug, n.category, n.image, n.published_at, b.name AS branch_name
                               FROM news n LEFT JOIN branches b ON b.id = n.branch_id
                               WHERE n.published = 1 AND (n.category IS NULL OR n.category <> 'மாதாந்திர புள்ளிப்பட்டியல்')
                               ORDER BY n.published_at DESC, n.id LIMIT 40"),
            'activities' => DB::all("SELECT category AS name, COUNT(*) AS n FROM news
                                     WHERE published = 1 AND category IS NOT NULL AND category <> 'மாதாந்திர புள்ளிப்பட்டியல்'
                                     GROUP BY category HAVING n >= 3 ORDER BY n DESC LIMIT 8"),
            'events' => DB::all('SELECT * FROM events WHERE active = 1 AND starts_at >= CURDATE() ORDER BY starts_at LIMIT 3'),
            'requests' => DB::all("SELECT * FROM blood_requests WHERE approved = 1 AND status = 'தேவை' ORDER BY created_at DESC LIMIT 3"),
            'report' => DB::one('SELECT * FROM monthly_reports ORDER BY report_month DESC LIMIT 1'),
            'leaders' => DB::all('SELECT name, role, phone, photo FROM executives WHERE active = 1 ORDER BY sort_order, id LIMIT 3'),
            'policy' => DB::one("SELECT subtitle FROM pages WHERE slug = 'policy'"),
            'directory' => $directory,
            'branchCount' => (int)DB::value('SELECT COUNT(*) FROM branches WHERE active = 1'),
            'officerCount' => (int)DB::value('SELECT COUNT(*) FROM branch_officers'),
        ]);
    }

    public static function policy(): never
    {
        self::render('policy', [
            'title' => 'கொள்கை',
            'page' => DB::one("SELECT * FROM pages WHERE slug = 'policy'"),
            'points' => DB::all('SELECT * FROM policy_points WHERE active = 1 ORDER BY sort_order, id'),
        ]);
    }

    public static function executives(): never
    {
        self::render('people', [
            'title' => 'மாவட்ட நிர்வாகிகள்', 'lead' => 'தமிழ்நாடு தவ்ஹீத் ஜமாஅத் — தஞ்சை வடக்கு மாவட்ட நிர்வாகக் குழு',
            'people' => DB::all('SELECT name, role AS subtitle, phone, photo FROM executives WHERE active = 1 ORDER BY sort_order, id'),
            'featured' => 3,
        ]);
    }

    public static function daees(): never
    {
        $g = $_GET['g'] ?? '';
        $where = in_array($g, ['ஆண்', 'பெண்'], true) ? 'AND gender = ?' : '';
        self::render('people', [
            'title' => 'மாவட்ட தாயிக்கள்', 'lead' => 'மாவட்ட பேச்சாளர்கள் — பயான், தர்பியா மற்றும் பொதுக்கூட்டங்களுக்கு தொடர்பு கொள்ளவும்',
            'people' => DB::all("SELECT name, CONCAT_WS(' · ', NULLIF(qualification,''), NULLIF(area,''), NULLIF(specialization,'')) AS subtitle, phone, photo, gender
                                 FROM daees WHERE active = 1 $where ORDER BY sort_order, id", $where ? [$g] : []),
            'genderFilter' => $g, 'searchable' => true,
        ]);
    }

    public static function branches(): never
    {
        $branches = DB::all('SELECT * FROM branches WHERE active = 1 ORDER BY sort_order, name');
        $officers = [];
        foreach (DB::all('SELECT * FROM branch_officers ORDER BY sort_order, id') as $o) $officers[$o['branch_id']][] = $o;
        $photos = DB::pairs("SELECT n.branch_id, n.image FROM news n
                             JOIN (SELECT branch_id, MAX(published_at) AS m FROM news WHERE published = 1 AND image <> '' AND branch_id IS NOT NULL GROUP BY branch_id) x
                               ON x.branch_id = n.branch_id AND x.m = n.published_at
                             WHERE n.image <> ''");
        self::render('branches', ['title' => 'கிளை நிர்வாகிகள்', 'branches' => $branches, 'officers' => $officers, 'photos' => $photos]);
    }

    public static function branch(string $id): never
    {
        $b = DB::one('SELECT * FROM branches WHERE id = ? AND active = 1', [(int)$id]);
        if (!$b) throw new HttpException(404, 'கிளை இல்லை');
        $own = DB::all("SELECT n.title, n.slug, n.image, n.category, n.published_at, ? AS branch_name FROM news n
                        WHERE n.published = 1 AND n.branch_id = ? AND n.image <> '' ORDER BY n.published_at DESC LIMIT 3", [$b['name'], $b['id']]);
        self::render('branch', [
            'title' => branch_label($b['name']), 'branch' => $b, 'headPhotos' => $own,
            'officers' => DB::all('SELECT * FROM branch_officers WHERE branch_id = ? ORDER BY sort_order, id', [$b['id']]),
            'news' => DB::all('SELECT n.id, n.title, n.slug, n.image, n.category, n.published_at, ? AS branch_name FROM news n WHERE n.published = 1 AND n.branch_id = ? ORDER BY n.published_at DESC LIMIT 12', [$b['name'], $b['id']]),
        ]);
    }

    public static function colleges(): never
    {
        self::render('colleges', ['title' => 'பெண்கள் இஸ்லாமிய கல்லூரிகள்', 'colleges' => DB::all('SELECT * FROM colleges WHERE active = 1 ORDER BY sort_order, id')]);
    }

    public static function maktab(): never
    {
        self::render('maktab', [
            'title' => 'மக்தப் மதரஸா',
            'maktabs' => DB::all('SELECT m.*, b.name AS branch_name FROM maktabs m LEFT JOIN branches b ON b.id = m.branch_id WHERE m.active = 1 ORDER BY m.sort_order, m.id'),
            'files' => DB::all("SELECT * FROM downloads WHERE active = 1 AND category LIKE 'மக்தப்%' ORDER BY category, sort_order, id"),
        ]);
    }

    public static function blood(): never
    {
        $g = $_GET['group'] ?? '';
        $valid = in_array($g, Resources::BLOOD_GROUPS, true);
        self::render('blood', [
            'title' => 'இரத்ததானம்',
            'groups' => Resources::BLOOD_GROUPS, 'group' => $valid ? $g : '',
            'requests' => DB::all("SELECT * FROM blood_requests WHERE approved = 1 AND status = 'தேவை' ORDER BY needed_on IS NULL, needed_on, created_at DESC LIMIT 30"),
            'donors' => DB::all('SELECT d.*, b.name AS branch_name FROM blood_donors d LEFT JOIN branches b ON b.id = d.branch_id WHERE d.approved = 1 ' . ($valid ? 'AND d.blood_group = ? ' : '') . 'ORDER BY d.available DESC, d.blood_group, d.name LIMIT 300', $valid ? [$g] : []),
            'counts' => DB::pairs('SELECT blood_group, COUNT(*) FROM blood_donors WHERE approved = 1 GROUP BY blood_group'),
            'branchList' => DB::all('SELECT id, name FROM branches WHERE active = 1 ORDER BY name'),
        ]);
    }

    public static function bloodDonorSubmit(): never
    {
        $to = '/blood#register';
        self::guardForm($to);
        $d = self::req(['name' => true, 'blood_group' => true, 'phone' => true, 'area' => false, 'age' => false, 'last_donated' => false, 'branch_id' => false], $to);
        if (!in_array($d['blood_group'], Resources::BLOOD_GROUPS, true)) self::back($to, 'இரத்த வகையைத் தேர்வு செய்யவும்.', 'err');
        $d['phone'] = self::validPhone($d['phone'], $to);
        $d['age'] = $d['age'] !== null ? max(0, min(99, (int)$d['age'])) : null;
        if ($d['last_donated'] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['last_donated'])) $d['last_donated'] = null;
        $d['branch_id'] = $d['branch_id'] && DB::value('SELECT 1 FROM branches WHERE id = ?', [(int)$d['branch_id']]) ? (int)$d['branch_id'] : null;
        if (DB::value('SELECT 1 FROM blood_donors WHERE phone = ?', [$d['phone']])) self::back($to, 'இந்த எண் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது. ஜஸாகல்லாஹ் ஹைரன்!');
        DB::insert('blood_donors', $d + ['approved' => 0, 'available' => 1]);
        self::back($to, 'ஜஸாகல்லாஹ் ஹைரன்! உங்கள் பதிவு சரிபார்ப்புக்குப் பின் இணைக்கப்படும்.');
    }

    public static function bloodRequestSubmit(): never
    {
        $to = '/blood#need';
        self::guardForm($to);
        $d = self::req(['patient_name' => true, 'blood_group' => true, 'units' => false, 'hospital' => true, 'needed_on' => false, 'contact_name' => true, 'contact_phone' => true, 'notes' => false], $to);
        if (!in_array($d['blood_group'], Resources::BLOOD_GROUPS, true)) self::back($to, 'இரத்த வகையைத் தேர்வு செய்யவும்.', 'err');
        $d['contact_phone'] = self::validPhone($d['contact_phone'], $to);
        $d['units'] = max(1, min(20, (int)($d['units'] ?? 1)));
        if ($d['needed_on'] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['needed_on'])) $d['needed_on'] = null;
        DB::insert('blood_requests', $d + ['approved' => 0]);
        self::back($to, t('உங்கள் கோரிக்கை பெறப்பட்டது. மருத்துவரணி உடனே தொடர்பு கொள்வார்கள். அவசரத்திற்கு: %s', setting('blood_helpline')));
    }

    public static function matrimony(): never
    {
        $f = $_GET['for'] ?? '';
        $valid = in_array($f, ['மணமகன் தேவை', 'மணமகள் தேவை'], true);
        self::render('matrimony', [
            'title' => 'திருமண தகவல்', 'for' => $valid ? $f : '',
            'profiles' => DB::all("SELECT * FROM matrimony WHERE approved = 1 AND status = 'செயலில்' " . ($valid ? 'AND looking_for = ? ' : '') . 'ORDER BY created_at DESC LIMIT 200', $valid ? [$f] : []),
        ]);
    }

    public static function matrimonySubmit(): never
    {
        $to = '/matrimony#register';
        self::guardForm($to);
        $d = self::req(['looking_for' => true, 'age' => true, 'marital_status' => false, 'education' => false, 'occupation' => false, 'area' => true, 'details' => false, 'contact_name' => true, 'contact_phone' => true], $to);
        if (!in_array($d['looking_for'], ['மணமகன் தேவை', 'மணமகள் தேவை'], true)) self::back($to, 'தேவையைத் தேர்வு செய்யவும்.', 'err');
        if (!in_array($d['marital_status'], ['முதல் திருமணம்', 'மறுமணம்', null], true)) $d['marital_status'] = null;
        $d['age'] = max(18, min(80, (int)$d['age']));
        $d['contact_phone'] = self::validPhone($d['contact_phone'], $to);
        $id = DB::insert('matrimony', $d + ['approved' => 0, 'show_phone' => 0]);
        DB::run('UPDATE matrimony SET profile_code = ? WHERE id = ?', [sprintf('TVT-%04d', $id), $id]);
        self::back($to, t('பதிவு பெறப்பட்டது (பதிவு எண்: TVT-%04d). நிர்வாகிகள் சரிபார்த்த பின் வெளியிடப்படும்.', $id));
    }

    public static function downloads(): never
    {
        $rows = DB::all('SELECT * FROM downloads WHERE active = 1 ORDER BY category, sort_order, id');
        $grouped = [];
        foreach ($rows as $r) $grouped[$r['category']][] = $r;
        self::render('downloads', ['title' => 'படிவங்கள் & தரவிறக்கம்', 'grouped' => $grouped]);
    }

    public static function downloadGo(string $id): never
    {
        $d = DB::one('SELECT * FROM downloads WHERE id = ? AND active = 1', [(int)$id]);
        if (!$d) throw new HttpException(404, 'கோப்பு இல்லை');
        DB::run('UPDATE downloads SET download_count = download_count + 1 WHERE id = ?', [$d['id']]);
        header('Location: ' . media($d['file_url']), true, 302);
        exit;
    }

    public static function events(): never
    {
        self::render('events', [
            'title' => 'மாவட்ட நிகழ்வுகள்',
            'upcoming' => DB::all('SELECT * FROM events WHERE active = 1 AND starts_at >= CURDATE() ORDER BY starts_at'),
            'past' => DB::all('SELECT * FROM events WHERE active = 1 AND starts_at < CURDATE() ORDER BY starts_at DESC LIMIT 24'),
            'news' => DB::all("SELECT n.id, n.title, n.slug, n.image, n.category, n.published_at, b.name AS branch_name FROM news n LEFT JOIN branches b ON b.id = n.branch_id
                               WHERE n.published = 1 AND (n.category IS NULL OR n.category <> 'மாதாந்திர புள்ளிப்பட்டியல்') ORDER BY n.published_at DESC LIMIT 8"),
        ]);
    }

    public static function reports(): never
    {
        $rows = DB::all('SELECT * FROM monthly_reports ORDER BY report_month DESC');
        $sel = null;
        if (!empty($_GET['m'])) foreach ($rows as $r) if ($r['report_month'] === $_GET['m']) $sel = $r;
        self::render('reports', ['title' => 'மாதாந்திர புள்ளி பட்டியல்', 'reports' => $rows, 'selected' => $sel ?? ($rows[0] ?? null)]);
    }

    public static function contact(): never
    {
        self::render('contact', [
            'title' => 'தொடர்புக்கு',
            'leaders' => DB::all('SELECT name, role, phone FROM executives WHERE active = 1 ORDER BY sort_order, id LIMIT 3'),
        ]);
    }

    public static function contactSubmit(): never
    {
        $to = '/contact#form';
        self::guardForm($to);
        $d = self::req(['name' => true, 'phone' => false, 'subject' => false, 'message' => true], $to);
        if ($d['phone']) $d['phone'] = self::validPhone($d['phone'], $to);
        DB::insert('contact_messages', $d + ['ip' => client_ip()]);
        self::back($to, 'உங்கள் செய்தி அனுப்பப்பட்டது. விரைவில் தொடர்பு கொள்கிறோம்.');
    }

    public static function links(): never
    {
        $rows = DB::all('SELECT * FROM links WHERE active = 1 ORDER BY sort_order, id');
        $grouped = [];
        foreach ($rows as $r) $grouped[$r['category'] ?: 'இணைப்புகள்'][] = $r;
        self::render('links', ['title' => 'அதிகாரப்பூர்வ இணைப்புகள்', 'grouped' => $grouped]);
    }

    public static function newsIndex(): never
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $where = 'n.published = 1'; $params = [];
        $q = trim((string)($_GET['q'] ?? ''));
        $cat = trim((string)($_GET['c'] ?? ''));
        $branch = (int)($_GET['branch'] ?? 0);
        if ($q !== '') { $where .= ' AND n.title LIKE ?'; $params[] = "%$q%"; }
        if ($cat !== '') { $where .= ' AND n.category = ?'; $params[] = $cat; }
        if ($branch) { $where .= ' AND n.branch_id = ?'; $params[] = $branch; }
        $total = (int)DB::value("SELECT COUNT(*) FROM news n WHERE $where", $params);
        $per = 20;
        self::render('news', [
            'title' => 'செய்திகள்', 'q' => $q, 'cat' => $cat, 'branch' => $branch,
            'page' => $page, 'pages' => max(1, (int)ceil($total / $per)), 'total' => $total,
            'activities' => DB::all("SELECT category AS name, COUNT(*) AS n FROM news WHERE published = 1 AND category IS NOT NULL GROUP BY category HAVING n >= 3 ORDER BY n DESC LIMIT 10"),
            'branchList' => DB::all('SELECT b.id, b.name, COUNT(n.id) AS n FROM branches b JOIN news n ON n.branch_id = b.id AND n.published = 1 GROUP BY b.id ORDER BY b.name'),
            'news' => DB::all("SELECT n.id, n.title, n.slug, n.category, n.image, n.published_at, b.name AS branch_name
                               FROM news n LEFT JOIN branches b ON b.id = n.branch_id WHERE $where
                               ORDER BY n.published_at DESC, n.id LIMIT $per OFFSET " . (($page - 1) * $per), $params),
        ]);
    }

    public static function newsShow(string $slug): never
    {
        $n = DB::one('SELECT n.*, b.name AS branch_name FROM news n LEFT JOIN branches b ON b.id = n.branch_id WHERE n.slug = ? AND n.published = 1', [$slug]);
        if (!$n) throw new HttpException(404, 'பதிவு இல்லை');
        self::render('news_show', [
            'title' => $n['title'], 'post' => $n, 'ogImage' => $n['image'],
            'related' => DB::all('SELECT n.title, n.slug, n.image, n.category, n.published_at, b.name AS branch_name FROM news n LEFT JOIN branches b ON b.id = n.branch_id
                                  WHERE n.published = 1 AND n.id <> ? AND (n.branch_id <=> ? OR n.category <=> ?) ORDER BY n.published_at DESC LIMIT 4', [$n['id'], $n['branch_id'], $n['category']]),
        ]);
    }
}
