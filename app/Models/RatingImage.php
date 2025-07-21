<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Model;

class RatingImage extends Model
{

    use HidesTimestamps;

    protected $guarded = [];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }
}
