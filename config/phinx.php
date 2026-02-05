<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/../database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/../database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'name' => $_ENV['DB_DATABASE'] ?? 'eeea_contable',
            'user' => $_ENV['DB_USERNAME'] ?? 'root',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'name' => $_ENV['DB_DATABASE'] ?? 'eeea_contable',
            'user' => $_ENV['DB_USERNAME'] ?? 'root',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ]
    ],
    'version_order' => 'creation'
];
