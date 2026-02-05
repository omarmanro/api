<?php

declare(strict_types=1);

namespace App\Models;

class Material extends Model
{
    protected static string $table = 'materials';

    protected array $fillable = [
        'plantel_id',
        'name',
        'description',
        'price',
        'cost',
        'stock',
        'min_stock',
        'category',
        'sku',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'plantel_id' => 'integer',
        'price' => 'float',
        'cost' => 'float',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function getProfit(): float
    {
        return (float) $this->price - (float) $this->cost;
    }

    public function getProfitMargin(): float
    {
        if ($this->price <= 0) {
            return 0.0;
        }
        return ($this->getProfit() / (float) $this->price) * 100;
    }
}
