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
    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'coupon_user');
    }
    public function excludedProducts()
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    public function excludedCategories()
    {
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    public function excludedSubcategories()
    {
        return $this->belongsToMany(SubCategory::class, 'coupon_subcategory');
    }
}
