<?php

namespace App;

enum PermissionEnum: string
{
    case VIEW_USERS = 'view users';
    case MANAGE_CODES = 'manage codes';
    case REPLY_TO_MESSAGES = 'reply to messages';
    case MANAGE_SETTINGS = 'manage settings';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
