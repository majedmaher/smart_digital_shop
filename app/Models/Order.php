<?php

namespace App\Models;

use App\Traits\HasCurrencyConversion;
use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Request;

class Order extends Model
{
    use HidesTimestamps, SoftDeletes, HasCurrencyConversion;
    protected $guarded = [];


    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($order) {
    //         $currencyCode = Request::header('Currency', 'SAR');
    //         $rate = currencyConverter($order->price_before, $currencyCode, 2);
    //         $order->currency_code = $rate['currency'];
    //         $order->total_price_user_currency = $rate['amount'];
    //     });

    //     static::updating(function ($order) {
    //         $currencyCode = Request::header('Currency', 'SAR');
    //         $rate = currencyConverter($order->price_before, $currencyCode, 2);
    //         $order->currency_code = $rate['currency'];
    //         $order->total_price_user_currency = $rate['amount'];
    //     });
    // }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
