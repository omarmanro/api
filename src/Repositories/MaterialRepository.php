<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Material;

class MaterialRepository extends BaseRepository
{
    protected string $model = Material::class;

    public function findByPlantel(int $plantelId): array
    {
        return $this->all(['plantel_id' => $plantelId], ['name' => 'ASC']);
    }

    public function getActiveByPlantel(int $plantelId): array
    {
        return $this->all([
            'plantel_id' => $plantelId,
            'status' => Material::STATUS_ACTIVE
        ], ['name' => 'ASC']);
    }

    public function findBySku(string $sku, int $plantelId): ?Material
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE sku = :sku AND plantel_id = :plantel_id LIMIT 1";
        
        $row = $this->db->fetch($sql, [
            'sku' => $sku,
            'plantel_id' => $plantelId
        ]);

        return $row ? new Material($row) : null;
    }

    public function getLowStock(int $plantelId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE plantel_id = :plantel_id 
                AND status = :status
                AND stock <= min_stock
                ORDER BY stock ASC";
        
        $rows = $this->db->fetchAll($sql, [
            'plantel_id' => $plantelId,
            'status' => Material::STATUS_ACTIVE
        ]);

        return array_map(fn($row) => new Material($row), $rows);
    }

    public function updateStock(int $id, int $quantity): bool
    {
        $sql = "UPDATE {$this->table} SET stock = stock + :quantity, updated_at = :updated_at WHERE id = :id";
        $this->db->query($sql, [
            'quantity' => $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id
        ]);
        return true;
    }

    public function search(string $query, int $plantelId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE plantel_id = :plantel_id
                AND (name LIKE :query OR sku LIKE :query OR category LIKE :query)
                AND status = :status
                ORDER BY name ASC";
        
        $rows = $this->db->fetchAll($sql, [
            'plantel_id' => $plantelId,
            'query' => "%{$query}%",
            'status' => Material::STATUS_ACTIVE
        ]);

        return array_map(fn($row) => new Material($row), $rows);
    }

    public function getByCategory(int $plantelId): array
    {
        $sql = "SELECT category, COUNT(*) as count, SUM(stock) as total_stock
                FROM {$this->table}
                WHERE plantel_id = :plantel_id AND status = :status
                GROUP BY category
                ORDER BY category ASC";
        
        return $this->db->fetchAll($sql, [
            'plantel_id' => $plantelId,
            'status' => Material::STATUS_ACTIVE
        ]);
    }
}
