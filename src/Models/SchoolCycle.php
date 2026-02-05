<?php

declare(strict_types=1);

namespace App\Models;

class SchoolCycle extends Model
{
    protected static string $table = 'school_cycles';

    protected array $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_UPCOMING = 'upcoming';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isUpcoming(): bool
    {
        return $this->status === self::STATUS_UPCOMING;
    }
}
