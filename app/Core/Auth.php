<?php
declare(strict_types=1);

namespace App\Core;

/** Bearer-token authentication for every /api route. Tokens are random 40-byte strings; only SHA-256 is stored. */
final class Auth
{
    private static ?array $current = null;

    public static function bearer(): ?string
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!$h && function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) if (strcasecmp($k, 'Authorization') === 0) $h = $v;
        }
        return preg_match('/^Bearer\s+([A-Za-z0-9_\-]{20,200})$/', trim((string)$h), $m) ? $m[1] : null;
    }

    /** Resolve the token or throw 401. Returns ['token' => row, 'user' => row|null, 'abilities' => [...]] */
    public static function require(string $ability = 'read'): array
    {
        if (self::$current === null) {
            $raw = self::bearer();
            if (!$raw) throw new HttpException(401, 'Bearer token தேவை (Authorization: Bearer <token>)');
            $row = DB::one('SELECT t.*, u.id AS uid, u.name AS uname, u.email AS uemail, u.role AS urole, u.active AS uactive
                            FROM api_tokens t LEFT JOIN users u ON u.id = t.user_id WHERE t.token_hash = ?', [hash('sha256', $raw)]);
            if (!$row) throw new HttpException(401, 'தவறான token');
            if ($row['expires_at'] && strtotime($row['expires_at']) < time()) throw new HttpException(401, 'Token காலாவதியானது — மீண்டும் உள்நுழையவும்');
            if ($row['user_id'] && !(int)$row['uactive']) throw new HttpException(401, 'பயனர் கணக்கு முடக்கப்பட்டுள்ளது');
            if (!$row['last_used_at'] || strtotime($row['last_used_at']) < time() - 60) {
                DB::run('UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?', [$row['id']]);
            }
            self::$current = [
                'token' => $row,
                'user' => $row['user_id'] ? ['id' => (int)$row['uid'], 'name' => $row['uname'], 'email' => $row['uemail'], 'role' => $row['urole']] : null,
                'abilities' => array_map('trim', explode(',', $row['abilities'])),
            ];
        }
        if (!in_array($ability, self::$current['abilities'], true)) {
            throw new HttpException(403, 'இந்த token-க்கு "' . $ability . '" அனுமதி இல்லை');
        }
        return self::$current;
    }

    public static function can(string $ability): bool
    {
        try { self::require($ability); return true; } catch (HttpException) { return false; }
    }

    public static function requireSuperadmin(): array
    {
        $a = self::require('write');
        if (($a['user']['role'] ?? '') !== 'superadmin') throw new HttpException(403, 'Super admin மட்டும்');
        return $a;
    }

    /** @return string the plain token (shown once) */
    public static function issue(?int $userId, string $name, string $abilities, ?int $ttlHours): string
    {
        $raw = rtrim(strtr(base64_encode(random_bytes(40)), '+/', '-_'), '=');
        DB::insert('api_tokens', [
            'user_id' => $userId,
            'name' => mb_substr($name, 0, 120),
            'token_hash' => hash('sha256', $raw),
            'abilities' => $abilities,
            'expires_at' => $ttlHours ? date('Y-m-d H:i:s', time() + $ttlHours * 3600) : null,
        ]);
        return $raw;
    }
}
