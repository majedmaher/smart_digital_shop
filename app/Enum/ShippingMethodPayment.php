<?php

namespace App\Enum;

enum ShippingMethodPayment: string
{
    case CODE = 'code';
    case ACCOUNT_ID = 'account_id';
    case MULTI_ID = 'multi_id';
    case ACCESS = 'access';
}
