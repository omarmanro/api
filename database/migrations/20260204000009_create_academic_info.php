<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAcademicInfo extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('academic_info', ['signed' => false]);
        
        $table
            ->addColumn('student_id', 'integer', ['signed' => false])
            ->addColumn('career_id', 'integer', ['signed' => false])
            ->addColumn('plantel_id', 'integer', ['signed' => false])
            ->addColumn('cycle_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('current_semester', 'integer', ['default' => 1])
            ->addColumn('group_name', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('schedule', 'enum', ['values' => ['morning', 'afternoon', 'evening'], 'default' => 'morning'])
            ->addColumn('enrollment_date', 'date')
            ->addColumn('expected_graduation', 'date', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['enrolled', 'on_leave', 'graduated', 'dropped'], 'default' => 'enrolled'])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['student_id'])
            ->addIndex(['career_id'])
            ->addIndex(['plantel_id'])
            ->addIndex(['cycle_id'])
            ->addIndex(['status'])
            ->addForeignKey('student_id', 'students', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('career_id', 'careers', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('cycle_id', 'school_cycles', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
