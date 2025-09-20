<?php

namespace App\Enum;

enum SocialProviderEnum: string
{
    case GOOGLE = 'google';
    case FACEBOOK = 'facebook';
    case APPLE = 'apple';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return match($this) {
            self::GOOGLE => 'جوجل',
            self::FACEBOOK => 'فيسبوك',
            self::APPLE => 'آبل',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::GOOGLE => 'fab fa-google',
            self::FACEBOOK => 'fab fa-facebook-f',
            self::APPLE => 'fab fa-apple',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::GOOGLE => '#4285F4',
            self::FACEBOOK => '#1877F2',
            self::APPLE => '#000000',
        };
    }

    public function isEnabled(): bool
    {
        return match($this) {
            self::GOOGLE => config('services.google.enabled', true),
            self::FACEBOOK => config('services.facebook.enabled', true),
            self::APPLE => config('services.apple.enabled', true),
        };
    }

    public function getConfigKey(): string
    {
        return match($this) {
            self::GOOGLE => 'google',
            self::FACEBOOK => 'facebook',
            self::APPLE => 'apple',
        };
    }
}
