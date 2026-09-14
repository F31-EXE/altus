<?php

namespace App\Core;

/**
 * Минималистичный роутер. Поддерживает статические сегменты и {param}.
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, params:string[], handler:array}> */
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
        // поддержка method override для форм (_method=DELETE и т.п. не используем, POST достаточно)

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $args = array_combine($route['params'], $matches) ?: [];

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action(...array_values($args));
            return;
        }

        http_response_code(404);
        echo '404 — страница не найдена';
    }
}
