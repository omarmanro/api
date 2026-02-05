<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Plantel;

class PlantelRepository extends BaseRepository
{
    protected string $model = Plantel::class;

    public function findByCode(string $code): ?Plantel
    {
        return $this->findBy('code', $code);
    }

    public function getActive(): array
    {
        return $this->all(['status' => Plantel::STATUS_ACTIVE], ['name' => 'ASC']);
    }

    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE :query OR code LIKE :query OR director_name LIKE :query)
                AND status = :status
                ORDER BY name ASC";
        
        $rows = $this->db->fetchAll($sql, [
            'query' => "%{$query}%",
            'status' => Plantel::STATUS_ACTIVE
        ]);

        return array_map(fn($row) => new Plantel($row), $rows);
    }
}
