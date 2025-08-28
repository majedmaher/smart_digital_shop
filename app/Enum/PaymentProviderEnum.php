<?php

namespace App\Enum;

enum PaymentProviderEnum: string
{
    case PAYMOB = 'paymob';
    case WALLET = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::PAYMOB => 'باي موب',
            self::WALLET => 'محفظة',
        };
    }

    public function image(): string
    {
        return match ($this) {
            self::PAYMOB => 'payment/paymob.png',
            self::WALLET => 'payment/wallet.png',
        };
    }
}
