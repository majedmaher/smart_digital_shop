<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HidesTimestamps, SoftDeletes;
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
