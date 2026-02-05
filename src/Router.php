<?php

declare(strict_types=1);

namespace App;

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\PlantelScopeMiddleware;

class Router
{
    private static ?Router $instance = null;
    private array $routes = [];
    private array $middlewares = [];
    private ?array $currentUser = null;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function getInstance(): ?Router
    {
        return self::$instance;
    }

    public function get(string $path, array $handler, array $options = []): self
    {
        return $this->addRoute('GET', $path, $handler, $options);
    }

    public function post(string $path, array $handler, array $options = []): self
    {
        return $this->addRoute('POST', $path, $handler, $options);
    }

    public function put(string $path, array $handler, array $options = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $options);
    }

    public function delete(string $path, array $handler, array $options = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $options);
    }

    public function patch(string $path, array $handler, array $options = []): self
    {
        return $this->addRoute('PATCH', $path, $handler, $options);
    }

    private function addRoute(string $method, string $path, array $handler, array $options = []): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'options' => $options
        ];
        return $this;
    }

    public function setCurrentUser(?array $user): void
    {
        $this->currentUser = $user;
    }

    public function getCurrentUser(): ?array
    {
        return $this->currentUser;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);
            
            if (preg_match($pattern, $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middlewares
                if (!$this->runMiddlewares($route['options'])) {
                    return;
                }

                // Get request body for POST/PUT/PATCH
                $body = $this->getRequestBody();

                // Create request object
                $request = [
                    'params' => $params,
                    'query' => $_GET,
                    'body' => $body,
                    'user' => $this->currentUser
                ];

                // Call controller method
                [$controllerClass, $methodName] = $route['handler'];
                $controller = new $controllerClass();
                $response = $controller->$methodName($request);

                $this->sendResponse($response);
                return;
            }
        }

        // No route found
        $this->sendResponse([
            'success' => false,
            'message' => 'Route not found'
        ], 404);
    }

    private function convertPathToRegex(string $path): string
    {
        // Convert path parameters like {id} to named regex groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove base path if exists
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && $basePath !== '\\') {
            $uri = str_replace($basePath, '', $uri);
        }

        // Remove /public if present
        $uri = preg_replace('#^/public#', '', $uri);

        // Ensure leading slash
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return rtrim($uri, '/') ?: '/';
    }

    private function getRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $body = file_get_contents('php://input');
            return json_decode($body, true) ?? [];
        }

        return $_POST;
    }

    private function runMiddlewares(array $options): bool
    {
        // Auth middleware
        if (!empty($options['auth'])) {
            $user = AuthMiddleware::handle();
            if ($user === null) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
                return false;
            }
            $this->currentUser = $user;

            // Role middleware
            if (!empty($options['roles'])) {
                if (!RoleMiddleware::handle($user, $options['roles'])) {
                    $this->sendResponse([
                        'success' => false,
                        'message' => 'Forbidden: Insufficient permissions'
                    ], 403);
                    return false;
                }
            }

            // Plantel scope middleware
            PlantelScopeMiddleware::handle($user);
        }

        return true;
    }

    private function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
