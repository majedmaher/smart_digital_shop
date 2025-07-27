<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class SubCategory extends Model
{
    use HasTranslations, SoftDeletes, HidesTimestamps, Slugable;

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
    ];

    protected $guarded = [];
    public array $translatable = ['name', 'slug'];

    public function scopeGetNecessaryData($query)
    {
        return $query->select('id', 'category_id', 'parent_id', 'name', 'icon', 'image', 'slug');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(SubCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SubCategory::class, 'parent_id');
    }
}
