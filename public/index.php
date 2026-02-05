<?php

declare(strict_types=1);

use App\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use Dotenv\Dotenv;

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoload
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City');

// Set error handler
set_exception_handler(function (Throwable $e) {
    $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'Internal Server Error',
        'trace' => $debug ? $e->getTraceAsString() : null
    ]);
    exit;
});

// Handle CORS
CorsMiddleware::handle();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize router and dispatch
$router = new Router();

// Load routes
require BASE_PATH . '/src/Routes/api.php';

// Dispatch request
$router->dispatch();
