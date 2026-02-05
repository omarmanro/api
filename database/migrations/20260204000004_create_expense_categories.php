<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateExpenseCategories extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('expense_categories', ['signed' => false]);
        
        $table
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('color', 'string', ['limit' => 7, 'default' => '#6B7280'])
            ->addColumn('icon', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['status'])
            ->create();
    }
}
