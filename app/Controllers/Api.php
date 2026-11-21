<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, DB, HttpException, Router};
use App\Resources;

/**
 * REST API — every endpoint except POST /auth/login requires `Authorization: Bearer <token>`.
 *   'read'  ability : GET on public resources (only published / approved rows, private fields hidden)
 *   'write' ability : full CRUD (issued to admin users at login)
 */
final class Api
{
    public static function routes(Router $r): void
    {
        $p = '/api/v1';
        $r->post("$p/auth/login", [self::class, 'login']);
        $r->post("$p/auth/logout", [self::class, 'logout']);
        $r->get("$p/auth/me", [self::class, 'me']);
        $r->get("$p/meta", [self::class, 'meta']);
        $r->get("$p/dashboard", [self::class, 'dashboard']);
        $r->post("$p/uploads", [self::class, 'upload']);

        $r->get("$p/settings", [self::class, 'settingsIndex']);
        $r->add('PUT', "$p/settings", [self::class, 'settingsUpdate']);

        $r->get("$p/users", [self::class, 'usersIndex']);
        $r->post("$p/users", [self::class, 'usersStore']);
        $r->add('PUT', "$p/users/{id}", [self::class, 'usersUpdate']);
        $r->add('DELETE', "$p/users/{id}", [self::class, 'usersDestroy']);

        $r->get("$p/tokens", [self::class, 'tokensIndex']);
        $r->post("$p/tokens", [self::class, 'tokensStore']);
        $r->add('DELETE', "$p/tokens/{id}", [self::class, 'tokensDestroy']);

        $r->get("$p/{resource}", [self::class, 'index']);
        $r->post("$p/{resource}", [self::class, 'store']);
        $r->get("$p/{resource}/{id}", [self::class, 'show']);
        $r->add('PUT', "$p/{resource}/{id}", [self::class, 'update']);
        $r->add('PATCH', "$p/{resource}/{id}", [self::class, 'update']);
        $r->add('DELETE', "$p/{resource}/{id}", [self::class, 'destroy']);
    }

    // ---------------------------------------------------------------- helpers
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private static function input(): array
    {
        static $data = null;
        if ($data !== null) return $data;
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($ct, 'application/json')) {
            $data = json_decode(file_get_contents('php://input') ?: '[]', true);
            if (!is_array($data)) throw new HttpException(400, 'தவறான JSON');
        } else {
            $data = $_POST;
        }
        return $data;
    }

    private static function resource(string $key): array
    {
        $res = Resources::get($key);
        if (!$res) throw new HttpException(404, "Resource '$key' இல்லை");
        return $res;
    }

    /** Validate + normalise input against the resource schema. Only schema columns are ever written. */
    private static function clean(array $res, array $in, bool $partial): array
    {
        $out = []; $errors = [];
        foreach ($res['fields'] as $f) {
            $n = $f['name'];
            if (!array_key_exists($n, $in)) {
                if (!$partial && !empty($f['required'])) $errors[$n] = $f['label'] . ' அவசியம்';
                elseif (!$partial && array_key_exists('default', $f)) $out[$n] = $f['default'];
                continue;
            }
            $v = $in[$n];
            if (is_string($v)) $v = trim($v);
            if ($v === '' || $v === null) {
                if (!empty($f['required'])) { $errors[$n] = $f['label'] . ' அவசியம்'; continue; }
                $out[$n] = $f['type'] === 'bool' ? 0 : null; continue;
            }
            switch ($f['type']) {
                case 'number': case 'branch':
                    if (!is_numeric($v)) { $errors[$n] = $f['label'] . ': எண் தேவை'; continue 2; }
                    $v = (int)$v;
                    if ($f['type'] === 'branch' && !DB::value('SELECT 1 FROM branches WHERE id = ?', [$v])) { $errors[$n] = 'கிளை இல்லை'; continue 2; }
                    break;
                case 'bool': $v = filter_var($v, FILTER_VALIDATE_BOOLEAN) ? 1 : 0; break;
                case 'date':
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v)) { $errors[$n] = $f['label'] . ': YYYY-MM-DD'; continue 2; }
                    break;
                case 'datetime':
                    $t = strtotime((string)$v);
                    if ($t === false) { $errors[$n] = $f['label'] . ': தவறான நேரம்'; continue 2; }
                    $v = date('Y-m-d H:i:s', $t); break;
                case 'select':
                    if (!in_array($v, $f['options'], true)) { $errors[$n] = $f['label'] . ': அனுமதிக்கப்பட்ட மதிப்பு அல்ல'; continue 2; }
                    break;
                case 'phone':
                    $v = preg_replace('/[^\d+ \-]/', '', (string)$v);
                    if (strlen(preg_replace('/\D/', '', $v)) < 8) { $errors[$n] = $f['label'] . ': சரியான எண் தேவை'; continue 2; }
                    break;
                case 'url':
                    if (!preg_match('~^https?://~i', (string)$v)) { $errors[$n] = $f['label'] . ': http(s):// இணைப்பு தேவை'; continue 2; }
                    break;
                case 'image': case 'file':
                    if (!preg_match('~^(https?://|/uploads/)~i', (string)$v)) { $errors[$n] = $f['label'] . ': தவறான கோப்பு'; continue 2; }
                    break;
                case 'html': $v = safe_html((string)$v); break;
                default: $v = mb_substr((string)$v, 0, 65000);
            }
            $out[$n] = $v;
        }
        if ($errors) throw new HttpException(422, 'உள்ளீட்டில் பிழை', $errors);
        return $out;
    }

    private static function hydrate(array $res, array $rows, bool $publicView): array
    {
        $hidden = $publicView ? ($res['private'] ?? []) : [];
        foreach ($rows as &$row) {
            foreach ($res['fields'] as $f) {
                $n = $f['name'];
                if (!array_key_exists($n, $row)) continue;
                if (in_array($f['type'], ['number', 'branch'], true) && $row[$n] !== null) $row[$n] = (int)$row[$n];
                if ($f['type'] === 'bool') $row[$n] = (bool)$row[$n];
            }
            $row['id'] = (int)$row['id'];
            $hide = $hidden;
            if ($publicView && $res['table'] === 'matrimony' && !empty($row['show_phone'])) $hide = array_diff($hide, ['contact_phone']);
            foreach ($hide as $h) unset($row[$h]);
        }
        return $rows;
    }

    // ---------------------------------------------------------------- auth
    public static function login(): never
    {
        $in = self::input();
        $email = strtolower(trim((string)($in['email'] ?? '')));
        $pass = (string)($in['password'] ?? '');
        $ip = client_ip();

        $recent = (int)DB::value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > NOW() - INTERVAL 15 MINUTE', [$ip]);
        if ($recent >= 8) throw new HttpException(429, 'அதிக முயற்சிகள். 15 நிமிடம் கழித்து முயற்சிக்கவும்.');

        $u = DB::one('SELECT * FROM users WHERE email = ? AND active = 1', [$email]);
        if (!$u || !password_verify($pass, $u['password_hash'])) {
            DB::run('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [$ip, mb_substr($email, 0, 190)]);
            usleep(400000);
            throw new HttpException(401, 'மின்னஞ்சல் அல்லது கடவுச்சொல் தவறு');
        }
        DB::run('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
        DB::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$u['id']]);
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
        }
        DB::run('DELETE FROM api_tokens WHERE expires_at IS NOT NULL AND expires_at < NOW()');
        $ttl = (int)config('token_ttl_hours', 12);
        $token = Auth::issue((int)$u['id'], 'admin-login ' . substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 60), 'read,write', $ttl);
        self::json([
            'token_type' => 'Bearer', 'access_token' => $token, 'expires_in' => $ttl * 3600,
            'user' => ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']],
        ]);
    }

    public static function logout(): never
    {
        $a = Auth::require('read');
        DB::run('DELETE FROM api_tokens WHERE id = ?', [$a['token']['id']]);
        self::json(['ok' => true]);
    }

    public static function me(): never
    {
        $a = Auth::require('read');
        self::json(['user' => $a['user'], 'abilities' => $a['abilities'], 'token' => ['name' => $a['token']['name'], 'expires_at' => $a['token']['expires_at']]]);
    }

    public static function meta(): never
    {
        Auth::require('read');
        $out = [];
        foreach (Resources::all() as $k => $r) {
            $out[$k] = array_intersect_key($r, array_flip(['label', 'icon', 'group', 'list', 'fields', 'filter', 'no_create'])) + ['has_pending' => isset($r['pending'])];
        }
        self::json(['resources' => $out, 'branches' => DB::all('SELECT id, name FROM branches ORDER BY name')]);
    }

    public static function dashboard(): never
    {
        Auth::require('write');
        $counts = []; $pending = [];
        foreach (Resources::all() as $k => $r) {
            $counts[$k] = (int)DB::value("SELECT COUNT(*) FROM `{$r['table']}`");
            if (isset($r['pending'])) $pending[$k] = (int)DB::value("SELECT COUNT(*) FROM `{$r['table']}` WHERE {$r['pending']}");
        }
        self::json([
            'counts' => $counts, 'pending' => $pending,
            'upcoming_events' => DB::all('SELECT id, title, starts_at, location FROM events WHERE starts_at >= NOW() ORDER BY starts_at LIMIT 5'),
            'latest_news' => DB::all('SELECT id, title, published_at FROM news ORDER BY published_at DESC LIMIT 5'),
        ]);
    }

    // ---------------------------------------------------------------- generic CRUD
    public static function index(string $key): never
    {
        $res = self::resource($key);
        $auth = Auth::require('read');
        $canWrite = in_array('write', $auth['abilities'], true);
        if (!$canWrite && !isset($res['public_where'])) throw new HttpException(403, 'இந்த resource-க்கு write அனுமதி தேவை');

        $where = []; $params = [];
        if (!$canWrite) $where[] = $res['public_where'];
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(' . implode(' OR ', array_map(fn($c) => "`$c` LIKE ?", $res['search'])) . ')';
            foreach ($res['search'] as $_) $params[] = '%' . $q . '%';
        }
        $fieldNames = array_column($res['fields'], 'name');
        foreach ($_GET as $k => $v) {           // exact-match filters: ?branch_id=3&blood_group=O%2B
            if (in_array($k, $fieldNames, true) && $v !== '') { $where[] = "`$k` = ?"; $params[] = $v; }
        }
        if (($_GET['pending'] ?? '') === '1' && isset($res['pending'])) $where[] = $res['pending'];
        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $order = $res['order'];
        if (preg_match('/^(-?)(\w+)$/', (string)($_GET['sort'] ?? ''), $m) && in_array($m[2], [...$fieldNames, 'id', 'created_at'], true)) {
            $order = "`{$m[2]}` " . ($m[1] ? 'DESC' : 'ASC');
        }
        $per = max(1, min(200, (int)($_GET['per_page'] ?? 25)));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $total = (int)DB::value("SELECT COUNT(*) FROM `{$res['table']}` $w", $params);
        $cols = $key === 'news' && empty($_GET['full']) ? 'id, title, slug, category, branch_id, image, published, published_at, created_at, updated_at' : '*';
        $rows = DB::all("SELECT $cols FROM `{$res['table']}` $w ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $params);
        self::json(['data' => self::hydrate($res, $rows, !$canWrite), 'meta' => ['total' => $total, 'page' => $page, 'per_page' => $per, 'pages' => (int)ceil($total / $per)]]);
    }

    public static function show(string $key, string $id): never
    {
        $res = self::resource($key);
        $auth = Auth::require('read');
        $canWrite = in_array('write', $auth['abilities'], true);
        if (!$canWrite && !isset($res['public_where'])) throw new HttpException(403, 'write அனுமதி தேவை');
        $w = $canWrite ? '' : ' AND ' . $res['public_where'];
        $row = DB::one("SELECT * FROM `{$res['table']}` WHERE id = ?$w", [(int)$id]);
        if (!$row) throw new HttpException(404, 'பதிவு இல்லை');
        self::json(['data' => self::hydrate($res, [$row], !$canWrite)[0]]);
    }

    public static function store(string $key): never
    {
        $res = self::resource($key);
        Auth::require('write');
        $data = self::clean($res, self::input(), false);
        if ($key === 'news') {
            $data['slug'] = self::uniqueSlug($data['slug'] ?? $data['title']);
            $data['published_at'] ??= date('Y-m-d H:i:s');
        }
        if ($key === 'matrimony' && empty($data['profile_code'])) $data['profile_code'] = null;
        $id = DB::insert($res['table'], $data);
        if ($key === 'matrimony' && empty($data['profile_code'])) DB::run('UPDATE matrimony SET profile_code = ? WHERE id = ?', [sprintf('TVT-%04d', $id), $id]);
        self::show($key, (string)$id);
    }

    public static function update(string $key, string $id): never
    {
        $res = self::resource($key);
        Auth::require('write');
        if (!DB::value("SELECT 1 FROM `{$res['table']}` WHERE id = ?", [(int)$id])) throw new HttpException(404, 'பதிவு இல்லை');
        $data = self::clean($res, self::input(), true);
        if ($key === 'news' && array_key_exists('slug', $data)) $data['slug'] = self::uniqueSlug($data['slug'] ?: ($data['title'] ?? 'post'), (int)$id);
        DB::update($res['table'], $data, 'id', (int)$id);
        self::show($key, $id);
    }

    public static function destroy(string $key, string $id): never
    {
        $res = self::resource($key);
        Auth::require('write');
        $n = DB::run("DELETE FROM `{$res['table']}` WHERE id = ?", [(int)$id])->rowCount();
        if (!$n) throw new HttpException(404, 'பதிவு இல்லை');
        self::json(['ok' => true]);
    }

    private static function uniqueSlug(string $s, int $exceptId = 0): string
    {
        $base = trim(preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', mb_strtolower($s)), '-') ?: 'post';
        $base = mb_substr($base, 0, 150);
        $slug = $base; $i = 2;
        while (DB::value('SELECT 1 FROM news WHERE slug = ? AND id <> ?', [$slug, $exceptId])) $slug = $base . '-' . $i++;
        return $slug;
    }

    // ---------------------------------------------------------------- uploads
    public static function upload(): never
    {
        Auth::require('write');
        $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new HttpException(422, 'கோப்பு பதிவேற்றம் தோல்வி');
        if ($f['size'] > (int)config('upload_max_mb', 10) * 1024 * 1024) throw new HttpException(422, 'கோப்பு அளவு அதிகம்');
        $allowed = ['jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'webp' => 'image', 'gif' => 'image',
                    'pdf' => 'doc', 'doc' => 'doc', 'docx' => 'doc', 'xls' => 'doc', 'xlsx' => 'doc'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) throw new HttpException(422, 'அனுமதிக்கப்பட்ட வகைகள்: ' . implode(', ', array_keys($allowed)));
        if ($allowed[$ext] === 'image' && !@getimagesize($f['tmp_name'])) throw new HttpException(422, 'செல்லாத படம்');
        $dir = '/uploads/' . date('Y/m');
        $abs = BASE_PATH . '/public' . $dir;
        if (!is_dir($abs) && !mkdir($abs, 0755, true)) throw new HttpException(500, 'uploads கோப்புறை உருவாக்க முடியவில்லை');
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], "$abs/$name")) throw new HttpException(500, 'கோப்பைச் சேமிக்க முடியவில்லை');
        self::json(['url' => "$dir/$name", 'name' => $f['name'], 'size' => $f['size']], 201);
    }

    // ---------------------------------------------------------------- settings
    public static function settingsIndex(): never
    {
        Auth::require('write');
        self::json(['data' => DB::all('SELECT `key`, `value`, label FROM settings ORDER BY label')]);
    }

    public static function settingsUpdate(): never
    {
        Auth::require('write');
        foreach (self::input() as $k => $v) {
            if (!preg_match('/^[a-z0-9_]{1,80}$/', (string)$k) || !is_scalar($v)) continue;
            DB::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$k, mb_substr((string)$v, 0, 5000)]);
        }
        self::settingsIndex();
    }

    // ---------------------------------------------------------------- users (super admin)
    public static function usersIndex(): never
    {
        Auth::requireSuperadmin();
        self::json(['data' => DB::all('SELECT id, name, email, role, active, last_login_at, created_at FROM users ORDER BY id')]);
    }

    private static function userData(array $in, bool $create): array
    {
        $d = []; $err = [];
        if ($create || isset($in['name'])) { $d['name'] = trim((string)($in['name'] ?? '')); if ($d['name'] === '') $err['name'] = 'பெயர் அவசியம்'; }
        if ($create || isset($in['email'])) {
            $d['email'] = strtolower(trim((string)($in['email'] ?? '')));
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'சரியான மின்னஞ்சல் தேவை';
        }
        if (isset($in['role'])) { $d['role'] = in_array($in['role'], ['superadmin', 'editor'], true) ? $in['role'] : 'editor'; }
        if (isset($in['active'])) $d['active'] = filter_var($in['active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        if ($create || !empty($in['password'])) {
            $p = (string)($in['password'] ?? '');
            if (strlen($p) < 8) $err['password'] = 'கடவுச்சொல் குறைந்தது 8 எழுத்துகள்';
            else $d['password_hash'] = password_hash($p, PASSWORD_DEFAULT);
        }
        if ($err) throw new HttpException(422, 'உள்ளீட்டில் பிழை', $err);
        return $d;
    }

    public static function usersStore(): never
    {
        Auth::requireSuperadmin();
        $d = self::userData(self::input(), true);
        if (DB::value('SELECT 1 FROM users WHERE email = ?', [$d['email']])) throw new HttpException(422, 'உள்ளீட்டில் பிழை', ['email' => 'இந்த மின்னஞ்சல் ஏற்கனவே உள்ளது']);
        $id = DB::insert('users', $d);
        self::json(['data' => DB::one('SELECT id, name, email, role, active FROM users WHERE id = ?', [$id])], 201);
    }

    public static function usersUpdate(string $id): never
    {
        $a = Auth::requireSuperadmin();
        $d = self::userData(self::input(), false);
        if ((int)$id === $a['user']['id'] && (isset($d['active']) && !$d['active'] || ($d['role'] ?? 'superadmin') !== 'superadmin')) {
            throw new HttpException(422, 'உங்கள் சொந்த கணக்கை முடக்கவோ தரம் குறைக்கவோ முடியாது');
        }
        DB::update('users', $d, 'id', (int)$id);
        if (isset($d['password_hash']) || (isset($d['active']) && !$d['active'])) DB::run('DELETE FROM api_tokens WHERE user_id = ? AND id <> ?', [(int)$id, $a['token']['id']]);
        self::json(['data' => DB::one('SELECT id, name, email, role, active FROM users WHERE id = ?', [(int)$id])]);
    }

    public static function usersDestroy(string $id): never
    {
        $a = Auth::requireSuperadmin();
        if ((int)$id === $a['user']['id']) throw new HttpException(422, 'உங்கள் சொந்த கணக்கை நீக்க முடியாது');
        DB::run('DELETE FROM users WHERE id = ?', [(int)$id]);
        self::json(['ok' => true]);
    }

    // ---------------------------------------------------------------- API tokens (super admin)
    public static function tokensIndex(): never
    {
        Auth::requireSuperadmin();
        self::json(['data' => DB::all('SELECT t.id, t.name, t.abilities, t.last_used_at, t.expires_at, t.created_at, u.email AS user_email
                                       FROM api_tokens t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.id DESC')]);
    }

    public static function tokensStore(): never
    {
        Auth::requireSuperadmin();
        $in = self::input();
        $name = trim((string)($in['name'] ?? ''));
        if ($name === '') throw new HttpException(422, 'உள்ளீட்டில் பிழை', ['name' => 'பெயர் அவசியம்']);
        $abilities = ($in['abilities'] ?? 'read') === 'read,write' ? 'read,write' : 'read';
        $days = (int)($in['expires_days'] ?? 0);
        $token = Auth::issue(null, $name, $abilities, $days > 0 ? $days * 24 : null);
        self::json(['access_token' => $token, 'token_type' => 'Bearer', 'abilities' => $abilities, 'note' => 'இந்த token ஒருமுறை மட்டுமே காட்டப்படும் — பாதுகாப்பாக சேமிக்கவும்'], 201);
    }

    public static function tokensDestroy(string $id): never
    {
        Auth::requireSuperadmin();
        DB::run('DELETE FROM api_tokens WHERE id = ?', [(int)$id]);
        self::json(['ok' => true]);
    }
}
