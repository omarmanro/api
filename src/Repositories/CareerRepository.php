<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Career;

class CareerRepository extends BaseRepository
{
    protected string $model = Career::class;

    public function findByCode(string $code): ?Career
    {
        return $this->findBy('code', $code);
    }

    public function getActive(): array
    {
        return $this->all(['status' => Career::STATUS_ACTIVE], ['name' => 'ASC']);
    }

    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE :query OR code LIKE :query)
                AND status = :status
                ORDER BY name ASC";
        
        $rows = $this->db->fetchAll($sql, [
            'query' => "%{$query}%",
            'status' => Career::STATUS_ACTIVE
        ]);

        return array_map(fn($row) => new Career($row), $rows);
    }
}
