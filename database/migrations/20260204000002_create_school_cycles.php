<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSchoolCycles extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('school_cycles', ['signed' => false]);
        
        $table
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('start_date', 'date')
            ->addColumn('end_date', 'date')
            ->addColumn('status', 'enum', ['values' => ['active', 'closed', 'upcoming'], 'default' => 'upcoming'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['status'])
            ->addIndex(['start_date', 'end_date'])
            ->create();
    }
}
