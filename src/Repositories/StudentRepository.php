<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Student;

class StudentRepository extends BaseRepository
{
    protected string $model = Student::class;

    public function findByStudentId(string $studentId): ?Student
    {
        return $this->findBy('student_id', $studentId);
    }

    public function findByCurp(string $curp): ?Student
    {
        return $this->findBy('curp', $curp);
    }

    public function findByPlantel(int $plantelId, array $filters = []): array
    {
        $conditions = array_merge(['plantel_id' => $plantelId], $filters);
        return $this->all($conditions, ['last_name' => 'ASC', 'first_name' => 'ASC']);
    }

    public function getActiveByPlantel(int $plantelId): array
    {
        return $this->findByPlantel($plantelId, ['status' => Student::STATUS_ACTIVE]);
    }

    public function search(string $query, ?int $plantelId = null): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (first_name LIKE :query OR last_name LIKE :query 
                       OR student_id LIKE :query OR email LIKE :query OR curp LIKE :query)";
        
        $params = ['query' => "%{$query}%"];
        
        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }
        
        $sql .= " ORDER BY last_name ASC, first_name ASC LIMIT 50";
        
        $rows = $this->db->fetchAll($sql, $params);

        return array_map(fn($row) => new Student($row), $rows);
    }

    public function countByPlantel(int $plantelId, ?string $status = null): int
    {
        $conditions = ['plantel_id' => $plantelId];
        if ($status !== null) {
            $conditions['status'] = $status;
        }
        return $this->count($conditions);
    }

    public function getWithAcademicInfo(int $studentId): ?array
    {
        $sql = "SELECT s.*, 
                       a.career_id, a.current_semester, a.schedule, a.status as academic_status,
                       c.name as career_name, c.code as career_code,
                       p.name as plantel_name
                FROM students s
                LEFT JOIN academic_info a ON s.id = a.student_id
                LEFT JOIN careers c ON a.career_id = c.id
                LEFT JOIN planteles p ON s.plantel_id = p.id
                WHERE s.id = :id";
        
        return $this->db->fetch($sql, ['id' => $studentId]);
    }
}
