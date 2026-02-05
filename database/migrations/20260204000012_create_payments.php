<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePayments extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('payments', ['signed' => false]);
        
        $table
            ->addColumn('plantel_id', 'integer', ['signed' => false])
            ->addColumn('student_id', 'integer', ['signed' => false])
            ->addColumn('cycle_id', 'integer', ['signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('concept_type', 'enum', ['values' => ['monthly_fee', 'enrollment_fee', 'material', 'other']])
            ->addColumn('concept_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('concept_description', 'string', ['limit' => 255])
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('discount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
            ->addColumn('late_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
            ->addColumn('total', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('payment_method', 'enum', ['values' => ['cash', 'card', 'transfer', 'check', 'other'], 'default' => 'cash'])
            ->addColumn('reference_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('receipt_number', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('payment_date', 'date')
            ->addColumn('period_month', 'integer', ['null' => true])
            ->addColumn('period_year', 'integer', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['completed', 'pending', 'cancelled', 'refunded'], 'default' => 'completed'])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['plantel_id'])
            ->addIndex(['student_id'])
            ->addIndex(['cycle_id'])
            ->addIndex(['user_id'])
            ->addIndex(['payment_date'])
            ->addIndex(['status'])
            ->addIndex(['concept_type'])
            ->addIndex(['receipt_number'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('student_id', 'students', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('cycle_id', 'school_cycles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
