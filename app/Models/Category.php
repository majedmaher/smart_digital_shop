<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations, SoftDeletes;

    protected $guarded = [];
    public array $translatable = ['name'];

    public function scopeGetNecessaryData($query)
    {
        return $query->select('id', 'name', 'icon');
        // locale = app()->getLocale();  // الحصول على اللغة الحالية
        // return $query->select('id', 'created_at', 'updated_at')  // استرجاع الأعمدة الأساسية
        //              ->addSelect(['name' => 'categories.name->' . $locale]);  // إضافة الترجمة للـ name بناءً على اللغة الحالية

    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }
}
