<?php

declare(strict_types=1);

namespace App\Models;

class EnrollmentFee extends Model
{
    protected static string $table = 'enrollment_fees';

    protected array $fillable = [
        'plantel_id',
        'cycle_id',
        'career_id',
        'name',
        'description',
        'amount',
        'fee_type',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'plantel_id' => 'integer',
        'cycle_id' => 'integer',
        'career_id' => 'integer',
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const TYPE_INSCRIPTION = 'inscription';
    public const TYPE_REINSCRIPTION = 'reinscription';
    public const TYPE_CREDENTIAL = 'credential';
    public const TYPE_EXAM = 'exam';
    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
