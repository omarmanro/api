<?php

declare(strict_types=1);

namespace App\Models;

use JsonSerializable;

abstract class Model implements JsonSerializable
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected array $attributes = [];
    protected array $fillable = [];
    protected array $hidden = ['password'];
    protected array $casts = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'array' => is_array($value) ? $value : json_decode($value, true),
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => $value instanceof \DateTimeInterface ? $value : new \DateTime($value),
            'date' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value,
            default => $value
        };
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                if ($value instanceof \DateTimeInterface) {
                    $result[$key] = $value->format('Y-m-d H:i:s');
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function getTable(): string
    {
        return static::$table;
    }

    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    public function getId(): ?int
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->attributes, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->attributes, array_flip($keys));
    }
}
