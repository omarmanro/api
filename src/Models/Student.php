<?php

declare(strict_types=1);

namespace App\Models;

class Student extends Model
{
    protected static string $table = 'students';

    protected array $fillable = [
        'plantel_id',
        'student_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'curp',
        'gender',
        'blood_type',
        'profile_photo',
        'status'
    ];

    protected array $casts = [
        'id' => 'integer',
        'plantel_id' => 'integer',
        'birth_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_SUSPENDED = 'suspended';

    public const GENDER_MALE = 'M';
    public const GENDER_FEMALE = 'F';
    public const GENDER_OTHER = 'O';

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isGraduated(): bool
    {
        return $this->status === self::STATUS_GRADUATED;
    }
}
