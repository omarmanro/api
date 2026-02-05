<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMonthlyFees extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('monthly_fees', ['signed' => false]);
        
        $table
            ->addColumn('plantel_id', 'integer', ['signed' => false])
            ->addColumn('cycle_id', 'integer', ['signed' => false])
            ->addColumn('career_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('due_day', 'integer', ['default' => 10])
            ->addColumn('late_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
            ->addColumn('late_fee_type', 'enum', ['values' => ['fixed', 'percentage'], 'default' => 'fixed'])
            ->addColumn('applies_to_semester', 'integer', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['plantel_id'])
            ->addIndex(['cycle_id'])
            ->addIndex(['career_id'])
            ->addIndex(['status'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('cycle_id', 'school_cycles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('career_id', 'careers', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
