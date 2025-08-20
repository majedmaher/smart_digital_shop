<?php

namespace App\Traits;

use Illuminate\Support\Facades\Request;

trait HasCurrencyConversion
{
    protected static function bootHasCurrencyConversion()
    {
        static::creating(function ($model) {
            $currencyCode = Request::header('Currency', 'SAR');
            $total = isset($model->total_price) ? $model->total_price : $model->total;
            $total_price = currencyConverter($total, $currencyCode, 2);
            $model->currency_code = $total_price['currency'];
            $model->total_price_user_currency = $total_price['amount'];

            $total_discount = currencyConverter((float) $model->discount, $currencyCode, 2);
            $model->discount_user_currency = $total_discount['amount'];
        });

        static::updating(function ($model) {
            $currencyCode = Request::header('Currency', 'SAR') ?? $model->currency_code;
            $total = isset($model->total_price) ? $model->total_price : $model->total;
            $total_price = currencyConverter($total, $currencyCode, 2);
            $model->currency_code = $total_price['currency'];
            $model->total_price_user_currency = $total_price['amount'];

            $total_discount = currencyConverter((float) $model->discount, $currencyCode, 2);
            $model->discount_user_currency = $total_discount['amount'];
        });
    }
}
