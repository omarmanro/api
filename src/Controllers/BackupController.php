<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BackupService;

class BackupController extends BaseController
{
    private BackupService $backupService;

    public function __construct()
    {
        $this->backupService = new BackupService();
    }

    public function index(): array
    {
        $backups = $this->backupService->listBackups();
        return $this->success($backups);
    }

    public function create(): array
    {
        $result = $this->backupService->createBackup('manual');

        if (!$result['success']) {
            return $this->error('Error al crear backup: ' . ($result['error'] ?? 'Unknown error'), 500);
        }

        return $this->created($result, 'Backup creado exitosamente');
    }

    public function restore(array $body): array
    {
        $errors = $this->validate($body, [
            'filename' => 'required|string'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $result = $this->backupService->restoreBackup($body['filename']);

        if (!$result['success']) {
            return $this->error('Error al restaurar backup: ' . ($result['error'] ?? 'Unknown error'), 500);
        }

        return $this->success($result, 'Backup restaurado exitosamente');
    }

    public function logs(): array
    {
        $logs = $this->backupService->getBackupLogs();
        return $this->success($logs);
    }

    public function clean(): array
    {
        $deleted = $this->backupService->cleanOldBackups();
        return $this->success([
            'deleted_count' => $deleted
        ], "Se eliminaron {$deleted} backups antiguos");
    }
}
