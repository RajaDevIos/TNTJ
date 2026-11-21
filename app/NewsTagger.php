<?php
declare(strict_types=1);

namespace App;

use App\Core\DB;

/**
 * Branch posts on the old site are titled "<activity> - <branch> கிளை [n]".
 * This splits them into category (the activity) and branch_id, so the site can
 * group news by what was done and where.
 */
final class NewsTagger
{
    private static ?array $branches = null;

    /** Same place, different names used on the old site. */
    private const BRANCH_ALIASES = [
        'கும்பகோணம் மாநகர' => 'குடந்தை மாநகர', 'கும்பகோணம்' => 'குடந்தை மாநகர', 'குடந்தை' => 'குடந்தை மாநகர',
        'ஆடுதுறை அவனியாபுரம்' => 'ஆவணியாபுரம்',
    ];

    /** Activity names typed several ways on the old site → one label. */
    private const ACTIVITY_ALIASES = [
        'ஆலோசனை கூட்டம்' => 'ஆலோசனைக் கூட்டம்', 'கரும்பலகை தஃவா' => 'கரும்பலகை தாவா', 'கரும்பலகை தஃவா.' => 'கரும்பலகை தாவா',
        'பெண்கள் குழு தாவா' => 'பெண்கள் தாவா குழு', 'தனிநபர் தாஃவா' => 'தனிநபர் தாவா', 'வென்பலகை தாவா' => 'வெண்பலகை தாவா', 'வெண்பலகை தஃவா' => 'வெண்பலகை தாவா', 'வென்பலகை தஃவா' => 'வெண்பலகை தாவா',
    ];

    /**
     * Spelling-tolerant key: drops "TNTJ", "கிளை", dots and the pulli (்), and folds letters
     * people commonly swap (ன/ண, ல/ழ/ள, ர/ற), so கொரநாட்டுக் கருப்பூர் == கொரநாட்டு கருப்பூர்.
     */
    private static function key(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace(['/tntj\.?/u', '/கிளை/u', '/[\s.\-]+/u', '/\x{0BCD}/u'], [' ', ' ', ' ', ''], $s);
        $s = strtr($s, ['ன' => 'ண', 'ழ' => 'ல', 'ள' => 'ல', 'ற' => 'ர']);
        $w = array_unique(array_filter(explode(' ', $s)));
        sort($w);
        return implode('', $w);
    }

    public static function branchId(string $name): ?int
    {
        self::$branches ??= DB::pairs('SELECT id, name FROM branches');
        $name = trim(preg_replace('/^TNTJ\.?\s*/u', '', $name));
        $plain = trim(preg_replace('/\s*கிளை\s*$/u', '', $name));
        $name = self::BRANCH_ALIASES[$plain] ?? $name;
        $k = self::key($name);
        if ($k === '') return null;
        foreach (self::$branches as $id => $b) if (self::key($b) === $k) return (int)$id;
        // Typos and sandhi letters (கொரநாட்டுக் / கோராநாட்டு): nearest name within 2 letters.
        // Keys are UTF-8 and Tamil letters are 3 bytes, so 6 bytes ≈ 2 letters. Never fuzzy-match numbered branches.
        $best = null; $bestD = 7;
        foreach (self::$branches as $id => $b) {
            $bk = self::key($b);
            if (preg_match('/\d/', $bk . $k) || strlen($k) < 18) continue;
            $d = levenshtein($bk, $k);
            if ($d < $bestD) { $best = (int)$id; $bestD = $d; }
        }
        return $best;
    }

    public static function activity(string $a): string
    {
        $a = trim($a, " .\t");
        return self::ACTIVITY_ALIASES[$a] ?? $a;
    }

    /** @return array{category:?string, branch_id:?int} */
    public static function tag(string $title, ?string $fallbackCategory = null): array
    {
        if (preg_match('/மாதாந்திர\s*புள்ளி/u', $title)) return ['category' => 'மாதாந்திர புள்ளிப்பட்டியல்', 'branch_id' => null];
        if (preg_match('/^(.+?)\s+-\s+(.+?)\s*\.?$/u', $title, $m)) {
            $where = trim($m[2]);
            if (str_contains($where, 'மாவட்டம்')) return ['category' => self::activity($m[1]), 'branch_id' => null];
            $id = self::branchId($where);
            if ($id || str_contains($where, 'கிளை')) return ['category' => self::activity($m[1]), 'branch_id' => $id];
        }
        return ['category' => $fallbackCategory, 'branch_id' => null];
    }
}
