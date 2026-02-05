<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEnrollmentFees extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('enrollment_fees', ['signed' => false]);
        
        $table
            ->addColumn('plantel_id', 'integer', ['signed' => false])
            ->addColumn('cycle_id', 'integer', ['signed' => false])
            ->addColumn('career_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('fee_type', 'enum', ['values' => ['enrollment', 'reinscription', 'credential', 'exam', 'other'], 'default' => 'enrollment'])
            ->addColumn('applies_to_semester', 'integer', ['null' => true])
            ->addColumn('is_required', 'boolean', ['default' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['plantel_id'])
            ->addIndex(['cycle_id'])
            ->addIndex(['career_id'])
            ->addIndex(['fee_type'])
            ->addIndex(['status'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('cycle_id', 'school_cycles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('career_id', 'careers', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
