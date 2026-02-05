<?php

declare(strict_types=1);

namespace App\Models;

class Career extends Model
{
    protected static string $table = 'careers';

    protected array $fillable = [
        'code',
        'name',
        'description',
        'duration_semesters',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'duration_semesters' => 'integer',
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
