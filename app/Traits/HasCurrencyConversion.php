<?php

namespace App\Traits;

use App\Enum\PaymentCurrencyEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

trait HasCurrencyConversion
{
    protected static function bootHasCurrencyConversion()
    {
        static::creating(function ($model) {
            $currencyCode = Request::header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value);
            $total = isset($model->total_price) ? $model->total_price : $model->total;
            $total_price = currencyConverter($total, $currencyCode, 2);
            $model->currency_code = $currencyCode;
            $model->currency_symbol = $total_price['currency'];
            $model->total_price_user_currency = $total_price['amount'];

            $total_discount = currencyConverter((float) $model->discount, $currencyCode, 2);
            $model->discount_user_currency = $total_discount['amount'];

            $vat = currencyConverter((float) $model->vat, $currencyCode, 2);
            $model->vat_user_currency = $vat['amount'];
        });

        static::updating(function ($model) {
            $currencyCode = Request::header('Currency', $model->currency_code);
            // $currencyCode = $model->currency_code;
            $total = isset($model->total_price) ? $model->total_price : $model->total;
            $total_price = currencyConverter($total, $currencyCode, 2);
            $model->currency_code = $currencyCode;
            $model->currency_symbol = $total_price['currency'];
            $model->total_price_user_currency = $total_price['amount'];

            $total_discount = currencyConverter((float) $model->discount, $currencyCode, 2);
            $model->discount_user_currency = $total_discount['amount'];

            $vat = currencyConverter((float) $model->vat, $currencyCode, 2);
            $model->vat_user_currency = $vat['amount'];
        });
    }
}
