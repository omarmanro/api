<?php

declare(strict_types=1);

namespace App\Models;

class ExpenseCategory extends Model
{
    protected static string $table = 'expense_categories';

    protected array $fillable = [
        'name',
        'description',
        'color',
        'icon',
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
