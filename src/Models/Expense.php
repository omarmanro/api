<?php

declare(strict_types=1);

namespace App\Models;

class Expense extends Model
{
    protected static string $table = 'expenses';

    protected array $fillable = [
        'plantel_id',
        'cycle_id',
        'category_id',
        'user_id',
        'description',
        'amount',
        'expense_date',
        'receipt_number',
        'receipt_file',
        'vendor',
        'notes',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'plantel_id' => 'integer',
        'cycle_id' => 'integer',
        'category_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'float',
        'expense_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
