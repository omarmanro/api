<?php

declare(strict_types=1);

namespace App\Models;

class MonthlyFee extends Model
{
    protected static string $table = 'monthly_fees';

    protected array $fillable = [
        'plantel_id',
        'cycle_id',
        'career_id',
        'name',
        'description',
        'amount',
        'due_day',
        'late_fee',
        'grace_days',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'plantel_id' => 'integer',
        'cycle_id' => 'integer',
        'career_id' => 'integer',
        'amount' => 'float',
        'due_day' => 'integer',
        'late_fee' => 'float',
        'grace_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function calculateLateFee(\DateTimeInterface $paymentDate, int $month): float
    {
        $dueDate = new \DateTime(sprintf('%d-%02d-%02d', 
            (int) $paymentDate->format('Y'), 
            $month, 
            $this->due_day
        ));
        
        $dueDate->modify('+' . ($this->grace_days ?? 0) . ' days');
        
        if ($paymentDate > $dueDate) {
            return (float) $this->late_fee;
        }
        
        return 0.0;
    }
}
