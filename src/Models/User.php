<?php

declare(strict_types=1);

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';

    protected array $fillable = [
        'plantel_id',
        'email',
        'password',
        'name',
        'role',
        'phone',
        'status',
        'last_login_at'
    ];

    protected array $hidden = [
        'password'
    ];

    protected array $casts = [
        'id' => 'integer',
        'plantel_id' => 'integer',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CONTADOR = 'contador';
    public const ROLE_CONSULTA = 'consulta';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isContador(): bool
    {
        return $this->role === self::ROLE_CONTADOR;
    }

    public function isConsulta(): bool
    {
        return $this->role === self::ROLE_CONSULTA;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasGlobalAccess(): bool
    {
        return $this->plantel_id === null && $this->isAdmin();
    }

    public function canAccessPlantel(int $plantelId): bool
    {
        if ($this->hasGlobalAccess()) {
            return true;
        }
        return $this->plantel_id === $plantelId;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->getAttribute('password') ?? '');
    }

    public function setPassword(string $password): static
    {
        $this->setAttribute('password', password_hash($password, PASSWORD_DEFAULT));
        return $this;
    }
}
