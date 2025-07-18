<?php

namespace App\Services;

class OtpService
{
    public static function generate(): string
    {
        return (string) rand(100000, 999999);
    }

    public static function expiresAt(): \DateTime
    {
        return now()->addMinutes(5);
    }
}
