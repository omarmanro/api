<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PlantelSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Plantel Central',
                'code' => 'PC001',
                'address' => 'Av. Principal #123, Centro',
                'phone' => '555-123-4567',
                'email' => 'central@eeea.edu.mx',
                'director_name' => 'Lic. María García López',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Plantel Norte',
                'code' => 'PN002',
                'address' => 'Blvd. Norte #456, Col. Industrial',
                'phone' => '555-234-5678',
                'email' => 'norte@eeea.edu.mx',
                'director_name' => 'Ing. Carlos Rodríguez Pérez',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('planteles')->insert($data)->saveData();
    }
}
