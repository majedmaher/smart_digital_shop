<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations, SoftDeletes, HidesTimestamps, Slugable;

    protected $casts = [
        'title' => 'array',
        'slug' => 'array',
    ];

    protected $guarded = [];

    public array $translatable = ['title', 'content', 'description', 'slug'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function codes()
    {
        return $this->hasMany(Code::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
