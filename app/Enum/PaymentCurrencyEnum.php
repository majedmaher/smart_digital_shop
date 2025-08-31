<?php

namespace App\Enum;

enum PaymentCurrencyEnum: string
{
    case DEFAULT_CURRENCY = 'OMR';
        // case OMR = 'OMR';
    case SAR = 'SAR';
}
