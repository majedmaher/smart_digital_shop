<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations, SoftDeletes, HidesTimestamps, Slugable;

    protected $guarded = [];
    public array $translatable = ['name', 'slug'];
    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
    ];

    public function scopeGetNecessaryData($query)
    {
        return $query->select('id', 'name', 'icon', 'image', 'slug');
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
