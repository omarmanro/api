<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCareers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('careers', ['signed' => false]);
        
        $table
            ->addColumn('code', 'string', ['limit' => 20])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('duration_semesters', 'integer', ['default' => 6])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['status'])
            ->create();
    }
}
