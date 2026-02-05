<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateExpenses extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('expenses', ['signed' => false]);
        
        $table
            ->addColumn('plantel_id', 'integer', ['signed' => false])
            ->addColumn('cycle_id', 'integer', ['signed' => false])
            ->addColumn('category_id', 'integer', ['signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('vendor', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('invoice_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('expense_date', 'date')
            ->addColumn('payment_method', 'enum', ['values' => ['cash', 'card', 'transfer', 'check', 'other'], 'default' => 'cash'])
            ->addColumn('reference_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('receipt_url', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['approved', 'pending', 'rejected', 'cancelled'], 'default' => 'approved'])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['plantel_id'])
            ->addIndex(['cycle_id'])
            ->addIndex(['category_id'])
            ->addIndex(['user_id'])
            ->addIndex(['expense_date'])
            ->addIndex(['status'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('cycle_id', 'school_cycles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('category_id', 'expense_categories', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
