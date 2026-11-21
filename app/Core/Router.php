<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/') ?: '/') . '/?$#u';
        $this->routes[] = [$method, $regex, $handler];
    }
    public function get(string $p, callable $h): void { $this->add('GET', $p, $h); }
    public function post(string $p, callable $h): void { $this->add('POST', $p, $h); }

    public function dispatch(string $method, string $path): mixed
    {
        $allowed = [];
        foreach ($this->routes as [$m, $regex, $h]) {
            if (!preg_match($regex, $path, $mt)) continue;
            if ($m !== $method && !($m === 'GET' && $method === 'HEAD')) { $allowed[] = $m; continue; }
            $params = array_filter($mt, 'is_string', ARRAY_FILTER_USE_KEY);
            return $h(...array_values($params));
        }
        if ($allowed) throw new HttpException(405, 'Method not allowed');
        throw new HttpException(404, 'Not found');
    }
}
