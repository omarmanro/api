#!/usr/bin/env php
<?php

/**
 * Script de backup automático para ejecutar vía cron
 * 
 * Programar en crontab:
 * 0 2 * * * /usr/bin/php /path/to/api/bin/backup.php >> /var/log/school-backup.log 2>&1
 * 
 * O en Windows Task Scheduler:
 * php C:\path\to\api\bin\backup.php
 */

declare(strict_types=1);

// Cambiar al directorio del proyecto
chdir(dirname(__DIR__));

// Cargar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Configurar timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City');

use App\Services\BackupService;

echo "[" . date('Y-m-d H:i:s') . "] Iniciando backup automático...\n";

try {
    $backupService = new BackupService();
    
    // Crear backup
    $result = $backupService->createBackup('scheduled');
    
    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Backup creado exitosamente:\n";
        echo "  - Archivo: {$result['filename']}\n";
        echo "  - Tamaño: {$result['size']}\n";
        echo "  - Duración: {$result['duration']}\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: {$result['error']}\n";
        exit(1);
    }
    
    // Limpiar backups antiguos
    $deleted = $backupService->cleanOldBackups();
    if ($deleted > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Se eliminaron {$deleted} backup(s) antiguo(s)\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Proceso completado.\n";
    exit(0);
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
