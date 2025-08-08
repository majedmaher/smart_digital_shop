<?php

namespace App\Traits;

use Illuminate\Support\Facades\Request;

trait HasCurrencyConversion
{
    protected static function bootHasCurrencyConversion()
    {
        static::creating(function ($model) {
            $currencyCode = Request::header('Currency', 'SAR');
            $total_price = currencyConverter((float) $model->total_price ?? (float) $model->total, $currencyCode, 2);
            $model->currency_code = $total_price['currency'];
            $model->total_price_user_currency = $total_price['amount'];

            $total_discount = currencyConverter((float) $model->discount, $currencyCode, 2);
            $model->discount_user_currency = $total_discount['amount'];
        });

        static::updating(function ($model) {
            $currencyCode = Request::header('Currency', 'SAR');
            $total_price = currencyConverter((float) $model->total_price ?? (float) $model->total, $currencyCode, 2);
            $model->currency_code = $total_price['currency'];
            $model->total_price_user_currency = $total_price['amount'];

            $total_discount = currencyConverter((float) $model->discount, $currencyCode, 2);
            $model->discount_user_currency = $total_discount['amount'];
        });
    }
}
