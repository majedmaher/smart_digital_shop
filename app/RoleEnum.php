<?php

namespace App;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case USER = 'user';
    case CUSTOM = 'custom';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
