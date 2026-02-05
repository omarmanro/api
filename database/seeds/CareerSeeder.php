<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CareerSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'code' => 'LAE',
                'name' => 'Licenciatura en Administración de Empresas',
                'description' => 'Formación de profesionales en gestión y administración empresarial',
                'duration_semesters' => 8,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'LCP',
                'name' => 'Licenciatura en Contaduría Pública',
                'description' => 'Formación de profesionales en contabilidad y finanzas',
                'duration_semesters' => 8,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'LDE',
                'name' => 'Licenciatura en Derecho',
                'description' => 'Formación de profesionales en ciencias jurídicas',
                'duration_semesters' => 10,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'ISC',
                'name' => 'Ingeniería en Sistemas Computacionales',
                'description' => 'Formación de profesionales en desarrollo de software y tecnología',
                'duration_semesters' => 9,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('careers')->insert($data)->saveData();
    }
}
