<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAuditLogs extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('audit_logs', ['signed' => false]);
        
        $table
            ->addColumn('table_name', 'string', ['limit' => 100])
            ->addColumn('record_id', 'integer', ['signed' => false])
            ->addColumn('action', 'enum', ['values' => ['create', 'update', 'delete']])
            ->addColumn('old_values', 'json', ['null' => true])
            ->addColumn('new_values', 'json', ['null' => true])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('plantel_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['table_name', 'record_id'])
            ->addIndex(['user_id'])
            ->addIndex(['plantel_id'])
            ->addIndex(['action'])
            ->addIndex(['created_at'])
            ->create();
    }
}
