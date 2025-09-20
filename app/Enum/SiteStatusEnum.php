<?php

namespace App\Enum;

enum SiteStatusEnum: string
{
    case DEMO = 'demo'; // موقع وهمي
    case LIVE = 'live'; // موقع رسمي

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return match($this) {
            self::DEMO => 'موقع وهمي',
            self::LIVE => 'موقع رسمي',
        };
    }
}
