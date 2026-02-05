<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMaterials extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('materials', ['signed' => false]);
        
        $table
            ->addColumn('plantel_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('stock', 'integer', ['default' => 0])
            ->addColumn('min_stock', 'integer', ['default' => 5])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['available', 'out_of_stock', 'discontinued'], 'default' => 'available'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['plantel_id'])
            ->addIndex(['name'])
            ->addIndex(['status'])
            ->addForeignKey('plantel_id', 'planteles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
