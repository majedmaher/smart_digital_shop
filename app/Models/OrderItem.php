<?php

namespace App\Models;

use App\Traits\HasCurrencyConversion;
use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Request;

class OrderItem extends Model
{
    use HidesTimestamps, SoftDeletes, HasCurrencyConversion;
    protected $guarded = [];


    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($order) {
    //         $currencyCode = Request::header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value);
    //         $rate = currencyConverter($order->total, $currencyCode, 2);
    //         $order->currency_code = $rate['currency'];
    //         $order->total_price_user_currency = $rate['amount'];
    //     });

    //     static::updating(function ($order) {
    //         $currencyCode = Request::header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value);
    //         $rate = currencyConverter($order->total, $currencyCode, 2);
    //         $order->currency_code = $rate['currency'];
    //         $order->total_price_user_currency = $rate['amount'];
    //     });
    // }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
