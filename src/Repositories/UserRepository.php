<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    protected string $model = User::class;

    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', $email);
    }

    public function findByPlantel(int $plantelId): array
    {
        return $this->all(['plantel_id' => $plantelId, 'status' => 'active']);
    }

    public function getAdmins(): array
    {
        return $this->all(['role' => User::ROLE_ADMIN, 'status' => 'active']);
    }

    public function updateLastLogin(int $userId): void
    {
        $this->db->update(
            $this->table,
            ['last_login_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $userId]
        );
    }

    public function changePassword(int $userId, string $newPassword): bool
    {
        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]) !== null;
    }

    public function findActiveByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND status = :status LIMIT 1";
        $row = $this->db->fetch($sql, [
            'email' => $email,
            'status' => User::STATUS_ACTIVE
        ]);
        
        return $row ? new User($row) : null;
    }
}
