<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ExpenseCategorySeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Nómina',
                'description' => 'Pago de salarios y prestaciones al personal',
                'color' => '#EF4444',
                'icon' => 'users',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Servicios',
                'description' => 'Luz, agua, teléfono, internet',
                'color' => '#3B82F6',
                'icon' => 'zap',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Materiales',
                'description' => 'Materiales de oficina y didácticos',
                'color' => '#10B981',
                'icon' => 'package',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Mantenimiento',
                'description' => 'Reparaciones y mantenimiento de instalaciones',
                'color' => '#F59E0B',
                'icon' => 'tool',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Equipamiento',
                'description' => 'Compra de equipo y mobiliario',
                'color' => '#8B5CF6',
                'icon' => 'monitor',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Capacitación',
                'description' => 'Cursos y capacitación del personal',
                'color' => '#EC4899',
                'icon' => 'book-open',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Otros',
                'description' => 'Gastos varios no clasificados',
                'color' => '#6B7280',
                'icon' => 'more-horizontal',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('expense_categories')->insert($data)->saveData();
    }
}
