<?php

declare(strict_types=1);

namespace App\Models;

class Payment extends Model
{
    protected static string $table = 'payments';

    protected array $fillable = [
        'plantel_id',
        'student_id',
        'cycle_id',
        'user_id',
        'concept_type',
        'concept_id',
        'reference_number',
        'amount',
        'discount',
        'surcharge',
        'total',
        'payment_method',
        'payment_date',
        'notes',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'plantel_id' => 'integer',
        'student_id' => 'integer',
        'cycle_id' => 'integer',
        'user_id' => 'integer',
        'concept_id' => 'integer',
        'amount' => 'float',
        'discount' => 'float',
        'surcharge' => 'float',
        'total' => 'float',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const CONCEPT_MONTHLY = 'monthly';
    public const CONCEPT_ENROLLMENT = 'enrollment';
    public const CONCEPT_MATERIAL = 'material';
    public const CONCEPT_OTHER = 'other';

    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_CHECK = 'check';

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function calculateTotal(): float
    {
        return (float) $this->amount - (float) $this->discount + (float) $this->surcharge;
    }
}
