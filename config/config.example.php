<?php
// Copy to config/config.php and fill in. config.php is never served (outside public/).
return [
    'app_url'   => 'https://www.tvtntj.net',   // no trailing slash
    'debug'     => false,
    'timezone'  => 'Asia/Kolkata',
    // true when visitors reach the site through Netlify (or another proxy / CDN)
    'trust_proxy' => false,

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'tvtntj',
        'user'    => 'tvtntj',
        'pass'    => 'change-me',
        'charset' => 'utf8mb4',
    ],

    // Lifetime of tokens issued by POST /api/v1/auth/login
    'token_ttl_hours' => 12,

    // Max upload size in MB (also check php.ini upload_max_filesize / post_max_size)
    'upload_max_mb' => 10,

    // Optional: comma separated origins allowed to call the API from a browser (CORS).
    'cors_origins' => '',
];
