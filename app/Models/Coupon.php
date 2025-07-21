<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HidesTimestamps, SoftDeletes;
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
