<?php

namespace App\Enum;

enum PaymentProviderEnum: string
{
    case PAYMOB = 'paymob';
    case WALLET = 'wallet';
}
