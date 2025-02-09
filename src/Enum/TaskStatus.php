<?php

namespace App\Enum;

enum TaskStatus: int
{
    case PENDING = 0;
    case ACTIVE = 1;
    case EXECUTED = 2;
    case DELETED = 3;

    public static function fromString(string $status): ?self
    {
        return match ($status) {
            'pending' => self::PENDING,
            'active' => self::ACTIVE,
            'executed' => self::EXECUTED,
            'deleted' => self::DELETED,
            default => null
        };
    }
}