<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Operator => 'Оператор',
            self::Viewer => 'Наблюдатель',
        };
    }
}
