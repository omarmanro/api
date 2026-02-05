<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository extends BaseRepository
{
    protected string $model = Payment::class;

    public function findByPlantel(int $plantelId, array $filters = []): array
    {
        $conditions = array_merge(['plantel_id' => $plantelId], $filters);
        return $this->all($conditions, ['payment_date' => 'DESC']);
    }

    public function findByStudent(int $studentId): array
    {
        return $this->all(['student_id' => $studentId], ['payment_date' => 'DESC']);
    }

    public function findByDateRange(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        $sql = "SELECT p.*, s.first_name, s.last_name, s.student_id as student_code,
                       u.name as registered_by, pl.name as plantel_name
                FROM {$this->table} p
                LEFT JOIN students s ON p.student_id = s.id
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN planteles pl ON p.plantel_id = pl.id
                WHERE p.payment_date BETWEEN :start_date AND :end_date
                AND p.status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate . ' 23:59:59',
            'status' => Payment::STATUS_COMPLETED
        ];

        if ($plantelId !== null) {
            $sql .= " AND p.plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " ORDER BY p.payment_date DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getTotalByDateRange(string $startDate, string $endDate, ?int $plantelId = null): float
    {
        $sql = "SELECT COALESCE(SUM(total), 0) as total FROM {$this->table}
                WHERE payment_date BETWEEN :start_date AND :end_date
                AND status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate . ' 23:59:59',
            'status' => Payment::STATUS_COMPLETED
        ];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $result = $this->db->fetch($sql, $params);
        return (float) ($result['total'] ?? 0);
    }

    public function getByConceptType(string $conceptType, ?int $plantelId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE concept_type = :concept_type AND status = :status";
        $params = [
            'concept_type' => $conceptType,
            'status' => Payment::STATUS_COMPLETED
        ];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        if ($startDate !== null && $endDate !== null) {
            $sql .= " AND payment_date BETWEEN :start_date AND :end_date";
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate . ' 23:59:59';
        }

        $sql .= " ORDER BY payment_date DESC";

        $rows = $this->db->fetchAll($sql, $params);
        return array_map(fn($row) => new Payment($row), $rows);
    }

    public function getDailyTotals(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        $sql = "SELECT DATE(payment_date) as date, 
                       COUNT(*) as count,
                       SUM(total) as total
                FROM {$this->table}
                WHERE payment_date BETWEEN :start_date AND :end_date
                AND status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate . ' 23:59:59',
            'status' => Payment::STATUS_COMPLETED
        ];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " GROUP BY DATE(payment_date) ORDER BY date ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getByPaymentMethod(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        $sql = "SELECT payment_method, 
                       COUNT(*) as count,
                       SUM(total) as total
                FROM {$this->table}
                WHERE payment_date BETWEEN :start_date AND :end_date
                AND status = :status";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate . ' 23:59:59',
            'status' => Payment::STATUS_COMPLETED
        ];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " GROUP BY payment_method ORDER BY total DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function findByReference(string $referenceNumber): ?Payment
    {
        return $this->findBy('reference_number', $referenceNumber);
    }

    public function generateReferenceNumber(): string
    {
        do {
            $reference = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(md5((string) mt_rand()), 0, 6));
        } while ($this->findByReference($reference) !== null);

        return $reference;
    }
}
