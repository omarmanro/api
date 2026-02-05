<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStudentAddresses extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('student_addresses', ['signed' => false]);
        
        $table
            ->addColumn('student_id', 'integer', ['signed' => false])
            ->addColumn('street', 'string', ['limit' => 200])
            ->addColumn('number', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('neighborhood', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('postal_code', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('city', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('state', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('country', 'string', ['limit' => 100, 'default' => 'México'])
            ->addColumn('is_primary', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['student_id'])
            ->addForeignKey('student_id', 'students', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
