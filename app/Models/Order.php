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


    public function getTotalWithVatAttribute()
    {
        return $this->orderItems->sum(fn($item) => $item->total_price);
    }

    public function getVatTotalAttribute()
    {
        return $this->orderItems->sum(fn($item) => $item->vat);
    }


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
