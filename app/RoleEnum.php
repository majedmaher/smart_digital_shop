<?php

namespace App;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case USER = 'user';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
