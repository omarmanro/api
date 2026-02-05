<?php

declare(strict_types=1);

namespace App\Models;

class Plantel extends Model
{
    protected static string $table = 'planteles';

    protected array $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'director_name',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
