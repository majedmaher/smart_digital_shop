<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class SubCategory extends Model
{
    use HasTranslations, SoftDeletes, HidesTimestamps;

    protected $guarded = [];
    public array $translatable = ['name'];

    public function scopeGetNecessaryData($query)
    {
        return $query->select('id', 'name', 'icon');
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
