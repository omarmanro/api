<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Expense;

class ExpenseRepository extends BaseRepository
{
    protected string $model = Expense::class;

    public function findByPlantel(int $plantelId, array $filters = []): array
    {
        $conditions = array_merge(['plantel_id' => $plantelId], $filters);
        return $this->all($conditions, ['expense_date' => 'DESC']);
    }

    public function findByDateRange(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        $sql = "SELECT e.*, 
                       c.name as category_name, c.color as category_color,
                       u.name as registered_by, pl.name as plantel_name
                FROM {$this->table} e
                LEFT JOIN expense_categories c ON e.category_id = c.id
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN planteles pl ON e.plantel_id = pl.id
                WHERE e.expense_date BETWEEN :start_date AND :end_date
                AND e.status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Expense::STATUS_APPROVED
        ];

        if ($plantelId !== null) {
            $sql .= " AND e.plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " ORDER BY e.expense_date DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getTotalByDateRange(string $startDate, string $endDate, ?int $plantelId = null): float
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table}
                WHERE expense_date BETWEEN :start_date AND :end_date
                AND status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Expense::STATUS_APPROVED
        ];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $result = $this->db->fetch($sql, $params);
        return (float) ($result['total'] ?? 0);
    }

    public function getByCategory(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        $sql = "SELECT c.id, c.name, c.color, c.icon,
                       COUNT(e.id) as count,
                       COALESCE(SUM(e.amount), 0) as total
                FROM expense_categories c
                LEFT JOIN {$this->table} e ON c.id = e.category_id 
                    AND e.expense_date BETWEEN :start_date AND :end_date
                    AND e.status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Expense::STATUS_APPROVED
        ];

        if ($plantelId !== null) {
            $sql .= " AND e.plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " GROUP BY c.id, c.name, c.color, c.icon
                  HAVING total > 0
                  ORDER BY total DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getDailyTotals(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        $sql = "SELECT expense_date as date,
                       COUNT(*) as count,
                       SUM(amount) as total
                FROM {$this->table}
                WHERE expense_date BETWEEN :start_date AND :end_date
                AND status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Expense::STATUS_APPROVED
        ];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " GROUP BY expense_date ORDER BY date ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getPending(?int $plantelId = null): array
    {
        $conditions = ['status' => Expense::STATUS_PENDING];
        if ($plantelId !== null) {
            $conditions['plantel_id'] = $plantelId;
        }
        return $this->all($conditions, ['created_at' => 'DESC']);
    }

    public function approve(int $id): ?Expense
    {
        return $this->update($id, ['status' => Expense::STATUS_APPROVED]);
    }

    public function reject(int $id): ?Expense
    {
        return $this->update($id, ['status' => Expense::STATUS_REJECTED]);
    }
}
