<?php

declare(strict_types=1);

namespace App\Models;

class AuditLog extends Model
{
    protected static string $table = 'audit_logs';

    protected array $fillable = [
        'table_name',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'plantel_id',
        'ip_address',
        'user_agent'
    ];

    protected array $casts = [
        'id' => 'integer',
        'record_id' => 'integer',
        'old_values' => 'json',
        'new_values' => 'json',
        'user_id' => 'integer',
        'plantel_id' => 'integer',
        'created_at' => 'datetime'
    ];

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    public function isCreate(): bool
    {
        return $this->action === self::ACTION_CREATE;
    }

    public function isUpdate(): bool
    {
        return $this->action === self::ACTION_UPDATE;
    }

    public function isDelete(): bool
    {
        return $this->action === self::ACTION_DELETE;
    }

    public function getChanges(): array
    {
        if (!$this->isUpdate()) {
            return [];
        }

        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $changes = [];

        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value
                ];
            }
        }

        return $changes;
    }
}
