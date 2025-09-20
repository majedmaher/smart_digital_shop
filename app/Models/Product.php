<?php

namespace App\Models;

use App\Traits\HidesTimestamps;
use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations, SoftDeletes, HidesTimestamps, Slugable;

    protected $casts = [
        'title' => 'array',
        'slug' => 'array',
        'sub_category_id' => 'integer',
    ];

    protected $guarded = [];

    public array $translatable = ['title', 'content', 'description', 'slug', 'terms_and_conditions'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default subcategory if not provided
        static::creating(function ($product) {
            if (empty($product->sub_category_id)) {
                $product->sub_category_id = self::getDefaultSubCategoryId();
            }
        });
    }

    public function scopeGetNecessaryData($query)
    {
        return $query->select('id', 'title', 'slug', 'image', 'price', 'price_before', 'discount', 'shipping_payment');
    }

    public function getPriceWithVatAttribute()
    {
        return $this->price + ($this->price * ($this->vat_rate / 100));
    }

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

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the default subcategory ID
     */
    public static function getDefaultSubCategoryId(): int
    {
        $defaultSubCategory = SubCategory::where('name->en', 'General')->first();
        
        if (!$defaultSubCategory) {
            // Create default subcategory if it doesn't exist
            $defaultSubCategory = SubCategory::create([
                'user_id' => 1, // Admin user ID
                'category_id' => Category::first()?->id ?? 1,
                'parent_id' => null,
                'name' => [
                    'en' => 'General',
                    'ar' => 'عام'
                ],
                'icon' => 'fas fa-box',
                'image' => 'default-subcategory.png',
                'slug' => [
                    'en' => 'general',
                    'ar' => 'عام'
                ],
            ]);
        }
        
        return $defaultSubCategory->id;
    }

    /**
     * Scope for products with default subcategory
     */
    public function scopeWithDefaultSubCategory($query)
    {
        return $query->where('sub_category_id', self::getDefaultSubCategoryId());
    }

    /**
     * Scope for products without subcategory
     */
    public function scopeWithoutSubCategory($query)
    {
        return $query->whereNull('sub_category_id');
    }

    /**
     * Ensure product has a subcategory
     */
    public function ensureSubCategory(): void
    {
        if (empty($this->sub_category_id)) {
            $this->sub_category_id = self::getDefaultSubCategoryId();
            $this->save();
        }
    }
}
