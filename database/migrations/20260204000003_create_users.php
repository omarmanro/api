<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users', ['signed' => false]);
        
        $table
            ->addColumn('plantel_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 100])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('role', 'enum', ['values' => ['admin', 'contador', 'consulta'], 'default' => 'consulta'])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
            ->addColumn('last_login', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['plantel_id'])
            ->addIndex(['role'])
            ->addIndex(['status'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
