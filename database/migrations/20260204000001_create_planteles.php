<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlanteles extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('planteles', ['signed' => false]);
        
        $table
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('code', 'string', ['limit' => 20])
            ->addColumn('address', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('director_name', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['status'])
            ->create();
    }
}
