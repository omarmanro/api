<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\Model;

abstract class BaseRepository
{
    protected Database $db;
    protected string $model;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->table = $this->model::getTable();
        $this->primaryKey = $this->model::getPrimaryKey();
    }

    public function find(int $id): ?Model
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $row = $this->db->fetch($sql, ['id' => $id]);
        
        if (!$row) {
            return null;
        }

        return new $this->model($row);
    }

    public function findBy(string $column, mixed $value): ?Model
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1";
        $row = $this->db->fetch($sql, ['value' => $value]);
        
        if (!$row) {
            return null;
        }

        return new $this->model($row);
    }

    public function all(array $conditions = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = $this->buildWhereClause($conditions, $params);
            $sql .= " WHERE {$where}";
        }

        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $column => $direction) {
                $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $orders[] = "{$column} {$direction}";
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset !== null) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $rows = $this->db->fetchAll($sql, $params);
        
        return array_map(fn($row) => new $this->model($row), $rows);
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = $this->buildWhereClause($conditions, $params);
            $sql .= " WHERE {$where}";
        }

        $result = $this->db->fetch($sql, $params);
        return (int) ($result['count'] ?? 0);
    }

    public function create(array $data): Model
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $this->db->insert($this->table, $data);
        
        return $this->find($id);
    }

    public function update(int $id, array $data): ?Model
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->update(
            $this->table,
            $data,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        return $this->db->delete(
            $this->table,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }

    public function softDelete(int $id): bool
    {
        return $this->update($id, ['status' => 'inactive']) !== null;
    }

    public function exists(int $id): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]) !== null;
    }

    public function paginate(int $page = 1, int $perPage = 15, array $conditions = [], array $orderBy = []): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $items = $this->all($conditions, $orderBy, $perPage, $offset);

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }

    protected function buildWhereClause(array $conditions, array &$params): string
    {
        $clauses = [];
        $i = 0;

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                // Handle operators: ['column' => ['operator' => 'value']]
                foreach ($value as $operator => $val) {
                    $paramName = "p{$i}";
                    $operator = strtoupper($operator);
                    
                    if ($operator === 'IN') {
                        $inParams = [];
                        foreach ((array) $val as $j => $inVal) {
                            $inParamName = "{$paramName}_{$j}";
                            $inParams[] = ":{$inParamName}";
                            $params[$inParamName] = $inVal;
                        }
                        $clauses[] = "{$column} IN (" . implode(', ', $inParams) . ")";
                    } elseif ($operator === 'BETWEEN') {
                        $params["{$paramName}_start"] = $val[0];
                        $params["{$paramName}_end"] = $val[1];
                        $clauses[] = "{$column} BETWEEN :{$paramName}_start AND :{$paramName}_end";
                    } elseif ($operator === 'LIKE') {
                        $params[$paramName] = $val;
                        $clauses[] = "{$column} LIKE :{$paramName}";
                    } elseif ($operator === 'IS NULL') {
                        $clauses[] = "{$column} IS NULL";
                    } elseif ($operator === 'IS NOT NULL') {
                        $clauses[] = "{$column} IS NOT NULL";
                    } else {
                        $params[$paramName] = $val;
                        $clauses[] = "{$column} {$operator} :{$paramName}";
                    }
                    $i++;
                }
            } elseif ($value === null) {
                $clauses[] = "{$column} IS NULL";
            } else {
                $paramName = "p{$i}";
                $params[$paramName] = $value;
                $clauses[] = "{$column} = :{$paramName}";
                $i++;
            }
        }

        return implode(' AND ', $clauses);
    }

    public function raw(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    public function rawOne(string $sql, array $params = []): ?array
    {
        return $this->db->fetch($sql, $params);
    }
}
