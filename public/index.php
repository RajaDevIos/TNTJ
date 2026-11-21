<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\{Api, Site};
use App\Core\{HttpException, Router};

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// PHP built-in dev server: let it serve real static files directly.
if (PHP_SAPI === 'cli-server' && $path !== '/' && is_file(__DIR__ . $path)) return false;
if (PHP_SAPI === 'cli-server' && rtrim($path, '/') === '/admin') { readfile(__DIR__ . '/admin/index.html'); return true; }

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

$isApi = str_starts_with($path, '/api/');

if ($isApi) {
    $origins = array_filter(array_map('trim', explode(',', (string)config('cors_origins', ''))));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && in_array($origin, $origins, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Vary: Origin');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    }
    if ($method === 'OPTIONS') { http_response_code(204); exit; }
}

$router = new Router();
Api::routes($router);
Site::routes($router);

try {
    $router->dispatch($method, $path);
} catch (HttpException $e) {
    if ($isApi) Api::json(['error' => $e->getMessage()] + ($e->errors ? ['errors' => $e->errors] : []), $e->status);
    Site::render('error', ['title' => $e->status === 404 ? 'பக்கம் கிடைக்கவில்லை' : 'பிழை', 'status' => $e->status, 'message' => $e->getMessage()], $e->status);
} catch (\Throwable $e) {
    error_log((string)$e);
    $msg = config('debug') ? $e->getMessage() : 'சர்வர் பிழை. பின்னர் முயற்சிக்கவும்.';
    if ($isApi) Api::json(['error' => $msg], 500);
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>500</title><p style="font-family:sans-serif;padding:2rem">' . e($msg) . '</p>';
}
