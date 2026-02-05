<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBackupLogs extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('backup_logs', ['signed' => false]);
        
        $table
            ->addColumn('filename', 'string', ['limit' => 255])
            ->addColumn('filepath', 'string', ['limit' => 500])
            ->addColumn('size', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('type', 'enum', ['values' => ['database', 'audit', 'full']])
            ->addColumn('status', 'enum', ['values' => ['completed', 'failed', 'in_progress'], 'default' => 'in_progress'])
            ->addColumn('error_message', 'text', ['null' => true])
            ->addColumn('started_at', 'datetime')
            ->addColumn('completed_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['type'])
            ->addIndex(['status'])
            ->addIndex(['created_at'])
            ->create();
    }
}
