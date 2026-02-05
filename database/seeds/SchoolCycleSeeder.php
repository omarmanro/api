<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class SchoolCycleSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'name' => '2025-2026',
                'description' => 'Ciclo escolar 2025-2026',
                'start_date' => '2025-08-01',
                'end_date' => '2026-07-31',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => '2026-2027',
                'description' => 'Ciclo escolar 2026-2027',
                'start_date' => '2026-08-01',
                'end_date' => '2027-07-31',
                'status' => 'upcoming',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('school_cycles')->insert($data)->saveData();
    }
}
