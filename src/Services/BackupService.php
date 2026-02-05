<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

class BackupService
{
    private Database $db;
    private string $backupPath;
    private int $retentionDays;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->backupPath = $_ENV['BACKUP_PATH'] ?? __DIR__ . '/../../storage/backups';
        $this->retentionDays = (int) ($_ENV['BACKUP_RETENTION_DAYS'] ?? 30);

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function createBackup(string $type = 'full'): array
    {
        $filename = sprintf(
            'backup_%s_%s.sql',
            $type,
            date('Y-m-d_His')
        );
        $filepath = $this->backupPath . DIRECTORY_SEPARATOR . $filename;

        // Registrar inicio del backup
        $logId = $this->logBackupStart($filename, $filepath, $type);

        try {
            $startTime = microtime(true);

            // Ejecutar mysqldump
            $command = $this->buildMysqldumpCommand($filepath);
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('Error ejecutando mysqldump: ' . implode("\n", $output));
            }

            // Comprimir el archivo
            $gzFilepath = $this->compressBackup($filepath);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            $size = filesize($gzFilepath);

            // Actualizar log con éxito
            $this->logBackupComplete($logId, $gzFilepath, $size);

            // Limpiar backups antiguos
            $this->cleanOldBackups();

            return [
                'success' => true,
                'filename' => basename($gzFilepath),
                'filepath' => $gzFilepath,
                'size' => $this->formatBytes($size),
                'duration' => $duration . 's',
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            $this->logBackupFailed($logId, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function buildMysqldumpCommand(string $outputPath): string
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_DATABASE'] ?? '';
        $username = $_ENV['DB_USERNAME'] ?? '';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        return $command;
    }

    private function compressBackup(string $filepath): string
    {
        $gzFilepath = $filepath . '.gz';
        
        $fp = fopen($filepath, 'rb');
        $gzfp = gzopen($gzFilepath, 'wb9');

        while (!feof($fp)) {
            gzwrite($gzfp, fread($fp, 524288)); // 512KB chunks
        }

        fclose($fp);
        gzclose($gzfp);

        // Eliminar archivo SQL original
        unlink($filepath);

        return $gzFilepath;
    }

    public function cleanOldBackups(): int
    {
        $deleted = 0;
        $cutoffDate = time() - ($this->retentionDays * 86400);

        $files = glob($this->backupPath . '/*.sql.gz');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupPath . '/*.sql.gz');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'created_at' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        // Ordenar por fecha descendente
        usort($backups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $backups;
    }

    public function restoreBackup(string $filename): array
    {
        $filepath = $this->backupPath . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'error' => 'Archivo de backup no encontrado'
            ];
        }

        try {
            // Descomprimir si es necesario
            $sqlFile = $filepath;
            if (str_ends_with($filepath, '.gz')) {
                $sqlFile = str_replace('.gz', '', $filepath);
                $gzfp = gzopen($filepath, 'rb');
                $fp = fopen($sqlFile, 'wb');
                while (!gzeof($gzfp)) {
                    fwrite($fp, gzread($gzfp, 524288));
                }
                gzclose($gzfp);
                fclose($fp);
            }

            // Ejecutar restauración
            $command = $this->buildMysqlCommand($sqlFile);
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            // Limpiar archivo temporal
            if ($sqlFile !== $filepath) {
                unlink($sqlFile);
            }

            if ($returnVar !== 0) {
                throw new \Exception('Error restaurando backup: ' . implode("\n", $output));
            }

            return [
                'success' => true,
                'message' => 'Backup restaurado exitosamente',
                'filename' => $filename,
                'restored_at' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function buildMysqlCommand(string $inputPath): string
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_DATABASE'] ?? '';
        $username = $_ENV['DB_USERNAME'] ?? '';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        return sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($inputPath)
        );
    }

    private function logBackupStart(string $filename, string $filepath, string $type): int
    {
        return $this->db->insert('backup_logs', [
            'filename' => $filename,
            'filepath' => $filepath,
            'type' => $type,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function logBackupComplete(int $logId, string $filepath, int $size): void
    {
        $this->db->update('backup_logs', [
            'filepath' => $filepath,
            'filename' => basename($filepath),
            'size' => $size,
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $logId]);
    }

    private function logBackupFailed(int $logId, string $error): void
    {
        $this->db->update('backup_logs', [
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $logId]);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getBackupLogs(int $limit = 20): array
    {
        $sql = "SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }
}
