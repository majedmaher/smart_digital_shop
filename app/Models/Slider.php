<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Slider extends Model
{
    use HasTranslations, HidesTimestamps, SoftDeletes;

    protected $guarded = [];
    public array $translatable = ['image'];
}
