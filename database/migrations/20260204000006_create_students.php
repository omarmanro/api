<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStudents extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('students', ['signed' => false]);
        
        $table
            ->addColumn('plantel_id', 'integer', ['signed' => false])
            ->addColumn('student_id', 'string', ['limit' => 20])
            ->addColumn('first_name', 'string', ['limit' => 100])
            ->addColumn('last_name', 'string', ['limit' => 100])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('curp', 'string', ['limit' => 18, 'null' => true])
            ->addColumn('gender', 'enum', ['values' => ['M', 'F', 'O'], 'null' => true])
            ->addColumn('birth_date', 'date', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive', 'graduated', 'suspended'], 'default' => 'active'])
            ->addColumn('enrollment_date', 'date', ['null' => true])
            ->addColumn('photo_url', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['student_id'], ['unique' => true])
            ->addIndex(['plantel_id'])
            ->addIndex(['curp'])
            ->addIndex(['status'])
            ->addIndex(['email'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
