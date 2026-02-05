<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AdminUserSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return [
            'PlantelSeeder'
        ];
    }

    public function run(): void
    {
        $data = [
            [
                'plantel_id' => null, // Admin global
                'email' => 'admin@eeea.edu.mx',
                'password' => password_hash('Admin123!', PASSWORD_DEFAULT),
                'name' => 'Administrador General',
                'role' => 'admin',
                'phone' => '555-000-0001',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'plantel_id' => 1, // Plantel Central
                'email' => 'contador.central@eeea.edu.mx',
                'password' => password_hash('Contador123!', PASSWORD_DEFAULT),
                'name' => 'Contador Plantel Central',
                'role' => 'contador',
                'phone' => '555-000-0002',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'plantel_id' => 2, // Plantel Norte
                'email' => 'contador.norte@eeea.edu.mx',
                'password' => password_hash('Contador123!', PASSWORD_DEFAULT),
                'name' => 'Contador Plantel Norte',
                'role' => 'contador',
                'phone' => '555-000-0003',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'plantel_id' => 1,
                'email' => 'consulta.central@eeea.edu.mx',
                'password' => password_hash('Consulta123!', PASSWORD_DEFAULT),
                'name' => 'Usuario Consulta Central',
                'role' => 'consulta',
                'phone' => '555-000-0004',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('users')->insert($data)->saveData();
    }
}
