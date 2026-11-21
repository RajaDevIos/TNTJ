<?php
// Configuration from environment variables, used when config/config.php does not exist.
// Accepts either DATABASE_URL / MYSQL_URL (mysql://user:pass@host:port/name) or the
// separate DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (Railway's MYSQLHOST… also work).
$env = fn(string ...$names) => array_reduce($names, fn($v, $n) => $v ?? (getenv($n) === false || getenv($n) === '' ? null : getenv($n)), null);
$url = $env('DATABASE_URL', 'MYSQL_URL');
$u = $url ? parse_url($url) : [];

return [
    'app_url'  => rtrim((string)($env('APP_URL') ?? 'http://localhost'), '/'),
    'debug'    => filter_var($env('APP_DEBUG') ?? false, FILTER_VALIDATE_BOOL),
    'timezone' => $env('APP_TIMEZONE') ?? 'Asia/Kolkata',
    // true when the site is reached through Netlify: trust its forwarded IP / https headers
    'trust_proxy' => filter_var($env('TRUST_PROXY') ?? false, FILTER_VALIDATE_BOOL),

    'db' => [
        'host'    => $u['host'] ?? $env('DB_HOST', 'MYSQLHOST') ?? '127.0.0.1',
        'port'    => (int)($u['port'] ?? $env('DB_PORT', 'MYSQLPORT') ?? 3306),
        'name'    => isset($u['path']) ? ltrim($u['path'], '/') : ($env('DB_NAME', 'MYSQLDATABASE') ?? 'tvtntj'),
        'user'    => isset($u['user']) ? urldecode($u['user']) : ($env('DB_USER', 'MYSQLUSER') ?? 'tvtntj'),
        'pass'    => isset($u['pass']) ? urldecode($u['pass']) : ($env('DB_PASS', 'MYSQLPASSWORD') ?? ''),
        'charset' => 'utf8mb4',
    ],

    'token_ttl_hours' => (int)($env('TOKEN_TTL_HOURS') ?? 12),
    'upload_max_mb'   => (int)($env('UPLOAD_MAX_MB') ?? 10),
    'cors_origins'    => $env('CORS_ORIGINS') ?? '',
];
