<?php
declare(strict_types=1);

namespace App;

use App\Core\DB;

/**
 * Three-language interface: Tamil (default), English, Arabic (right-to-left).
 *
 * Strings are keyed by their Tamil source text, so views read naturally and any
 * text without a translation simply stays in Tamil.
 *   1. app/lang/{en,ar}.php   interface text, activity names, roles, branch names
 *   2. `translations` table    content entered in the admin panel (policy, pages…),
 *                              matched by the SHA-1 of the Tamil source text
 * Arabic falls back to English for proper names (branches) that have no Arabic form.
 */
final class I18n
{
    public const LANGS = [
        'ta' => ['label' => 'தமிழ்', 'short' => 'த', 'dir' => 'ltr', 'locale' => 'ta_IN'],
        'en' => ['label' => 'English', 'short' => 'EN', 'dir' => 'ltr', 'locale' => 'en_IN'],
        'ar' => ['label' => 'العربية', 'short' => 'ع', 'dir' => 'rtl', 'locale' => 'ar'],
    ];
    public const DEFAULT = 'ta';

    private static string $lang = self::DEFAULT;
    private static array $dict = [];
    private static ?array $db = null;

    /** Pick the language: ?lang= (remembered in a cookie for a year) → cookie → Tamil. */
    public static function boot(): void
    {
        $q = $_GET['lang'] ?? null;
        if (is_string($q) && isset(self::LANGS[$q])) {
            self::$lang = $q;
            if (PHP_SAPI !== 'cli' && !headers_sent()) {
                setcookie('lang', $q, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
            }
        } elseif (isset($_COOKIE['lang']) && isset(self::LANGS[$_COOKIE['lang']])) {
            self::$lang = $_COOKIE['lang'];
        }
        self::load();
    }

    public static function set(string $lang): void
    {
        if (!isset(self::LANGS[$lang])) return;
        self::$lang = $lang; self::$db = null; self::load();
    }

    private static function load(): void
    {
        self::$dict = [];
        if (self::$lang === 'ta') return;
        $file = APP_PATH . '/lang/' . self::$lang . '.php';
        self::$dict = is_file($file) ? require $file : [];
        if (self::$lang === 'ar') {
            // proper names without an Arabic spelling read better in Latin script than in Tamil
            self::$dict += array_intersect_key(require APP_PATH . '/lang/en.php', array_flip(require APP_PATH . '/lang/names.php'));
        }
    }

    public static function lang(): string { return self::$lang; }
    public static function dir(): string { return self::LANGS[self::$lang]['dir']; }

    public static function t(string $s, mixed ...$args): string
    {
        if (self::$lang !== 'ta' && $s !== '') {
            $key = trim($s);
            $hit = self::$dict[$key] ?? self::fromDb($key);
            if ($hit !== null && $hit !== '') $s = $hit;
        }
        return $args ? vsprintf($s, $args) : $s;
    }

    /** Admin-entered translations of database content. */
    private static function fromDb(string $s): ?string
    {
        if (self::$db === null) {
            try { self::$db = DB::pairs('SELECT source_hash, value FROM translations WHERE lang = ?', [self::$lang]); }
            catch (\Throwable) { self::$db = []; }
        }
        return self::$db[sha1($s)] ?? null;
    }

    /** Same page in another language (keeps the path and other query parameters). */
    public static function switchUrl(string $lang): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $q = $_GET; $q['lang'] = $lang;
        return $path . '?' . http_build_query($q);
    }
}
