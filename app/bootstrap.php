<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);

$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config/config.php missing — copy config/config.example.php and fill it in.');
}
$GLOBALS['config'] = require $configFile;
date_default_timezone_set(config('timezone', 'Asia/Kolkata'));
mb_internal_encoding('UTF-8');

if (config('debug')) { ini_set('display_errors', '1'); error_reporting(E_ALL); }
else { ini_set('display_errors', '0'); }

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});

// ---------------- helpers ----------------
function config(string $key, mixed $default = null): mixed
{
    $v = $GLOBALS['config'];
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}

function e(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function url(string $path = ''): string { return '/' . ltrim($path, '/'); }

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return '/assets/' . $path . '?v=' . $v;
}

/** Uploaded files are stored as "/uploads/..." ; external URLs are passed through. */
function media(?string $path): string { return $path ? (preg_match('~^https?://~', $path) ? $path : url($path)) : ''; }

/** Blogger-hosted images carry their size in the path (/s320/, /w400-h300/). Ask for the size we need. */
function bimg(?string $url, int $size = 640): string
{
    $u = media($url);
    if (!str_contains($u, 'blogger.googleusercontent.com') && !str_contains($u, 'bp.blogspot.com')) return $u;
    return preg_replace('~/(s\d+(-[a-z0-9-]+)?|w\d+-h\d+(-[a-z0-9-]+)?)/~', "/s$size/", $u, 1);
}

/** Old Blogger HTML: upgrade inline images to large renditions and drop fixed pixel sizes. */
function blogger_html(string $html): string
{
    $html = preg_replace_callback('~(<img[^>]+src=")([^"]+)(")~i', fn($m) => $m[1] . e(bimg(html_entity_decode($m[2]), 1600)) . $m[3], $html);
    return preg_replace_callback('~<img\b[^>]*>~i',
        fn($m) => preg_replace('~\s(width|height|data-original-width|data-original-height|border)="[^"]*"~i', '', $m[0]), $html);
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try { $cache = \App\Core\DB::pairs('SELECT `key`, `value` FROM settings'); }
        catch (\Throwable) { $cache = []; }
    }
    return (string)($cache[$key] ?? $default) ?: $default;
}

function tel(?string $p): string { return preg_replace('/[^\d+]/', '', (string)$p); }
function wa(?string $p): string { $d = preg_replace('/\D/', '', (string)$p); return strlen($d) === 10 ? '91' . $d : $d; }
function fmt_phone(?string $p): string { $d = preg_replace('/\D/', '', (string)$p); return strlen($d) === 10 ? substr($d, 0, 5) . ' ' . substr($d, 5) : (string)$p; }

// ---------------- language ----------------
/** Translate a Tamil source string into the visitor's language (Tamil passes through). */
function t(string $s, mixed ...$args): string { return \App\I18n::t($s, ...$args); }
function lang(): string { return \App\I18n::lang(); }

/** "<name> கிளை" / "<name> branch" / "فرع <name>" — branch names are stored in Tamil. */
function branch_label(?string $name): string
{
    $name = trim((string)$name);
    if ($name === '') return '';
    if (lang() === 'ta') return str_contains($name, 'கிளை') ? $name : $name . ' கிளை';
    return t('%s கிளை', t($name));
}

function month_names(): array
{
    return match (lang()) {
        'en' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
        'ar' => ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
        default => ['ஜனவரி','பிப்ரவரி','மார்ச்','ஏப்ரல்','மே','ஜூன்','ஜூலை','ஆகஸ்ட்','செப்டம்பர்','அக்டோபர்','நவம்பர்','டிசம்பர்'],
    };
}
function day_names(): array
{
    return match (lang()) {
        'en' => ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
        'ar' => ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'],
        default => ['ஞாயிறு','திங்கள்','செவ்வாய்','புதன்','வியாழன்','வெள்ளி','சனி'],
    };
}

/** Date in the visitor's language ("21 நவம்பர் 2026", "21 November 2026", "21 نوفمبر 2026"). */
function ta_date(?string $d, bool $withTime = false): string
{
    if (!$d) return '';
    $t = strtotime($d);
    $s = date('j', $t) . ' ' . month_names()[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
    return $withTime ? $s . ', ' . date('g:i A', $t) : $s;
}
function ta_month(?string $d): string
{
    $t = strtotime((string)$d);
    return month_names()[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

function excerpt(?string $html, int $len = 160): string
{
    $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$html), ENT_QUOTES, 'UTF-8')));
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : $t;
}

/** Light HTML sanitiser for admin/imported rich text: removes scripts, event handlers, javascript: URLs. */
function safe_html(?string $html): string
{
    $h = (string)$html;
    $h = preg_replace('#<(script|style|iframe|object|embed|form|input|button|textarea|select|meta|link)\b[^>]*>.*?</\1>#is', '', $h);
    $h = preg_replace('#<(script|style|iframe|object|embed|form|input|button|meta|link)\b[^>]*/?>#is', '', $h);
    $h = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $h);
    $h = preg_replace('#(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2#i', '$1="#"', $h);
    return $h;
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}
function csrf_check(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_csrf']);
}
function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }

\App\I18n::boot();
