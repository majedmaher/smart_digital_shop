<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Resources\CouponResponseResource;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CouponService
{
    public static function store($data): JsonResponse
    {
        try {
            $data['user_id'] = auth()->id();
            $allowedUserIds = $data['allowed_user_ids'] ?? [];
            $excludedProductIds = $data['excluded_product_ids'] ?? [];
            $excludedCategoryIds = $data['excluded_category_ids'] ?? [];
            $excludedSubcategoryIds = $data['excluded_subcategory_ids'] ?? [];

            unset($data['allowed_user_ids'], $data['excluded_product_ids'], $data['excluded_category_ids'], $data['excluded_subcategory_ids']);


            $coupon = Coupon::create($data);
            if (!empty($allowedUserIds)) {
                $coupon->allowedUsers()->sync($allowedUserIds);
            }
            if (!empty($excludedProductIds)) {
                $coupon->excludedProducts()->sync($excludedProductIds);
            }
            if (!empty($excludedCategoryIds)) {
                $coupon->excludedCategories()->sync($excludedCategoryIds);
            }
            if (!empty($excludedSubcategoryIds)) {
                $coupon->excludedSubcategories()->sync($excludedSubcategoryIds);
            }

            return BaseController::sendResponse(CouponResponseResource::make($coupon), __('messages.store_successfully', ['item' => __('messages.coupon')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.coupon')]), [], 500);
        }
    }

    public static function applyCoupon(string $code, User $user, array $items)
    {
        try {
            $coupon = Coupon::where('code', $code)
                // ->with(['excludedProducts', 'excludedCategories', 'excludedSubcategories', 'allowedUsers'])
                ->first();

            if (!$coupon || !$coupon->active) {
                return [__('messages.invalid_coupon'), 422];
            }

            if ($coupon->usage_limit !== null && $coupon->used >= $coupon->usage_limit) {
                return [__('messages.used_maximum_coupon'), 422];
            }

            if (
                ($coupon->expires_from && now()->lt($coupon->expires_from)) ||
                ($coupon->expires_at && now()->gt($coupon->expires_at))
            ) {
                return [__('messages.coupon_expired'), 422];
            }

            // تحقق من أن المستخدم من ضمن المسموح لهم (إن وُجدت قائمة)
            if ($coupon->allowedUsers()->exists() && !$coupon->allowedUsers->contains($user->id)) {
                return [__('messages.coupon_not_available'), 422];
            }

            // ✅ استثناء العناصر غير المؤهلة
            $productIds = array_column($items, 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            // ✅ ربط بيانات المنتج الحقيقية بكل عنصر في السلة
            foreach ($items as &$item) {
                $product = $products[$item['product_id']] ?? null;

                if (!$product) {
                    return [__('messages.item_not_found', ['item' => __('messages.product')]), 404];
                }

                $item['price'] = (float) $product->price;
                $item['category_id'] = $product->category_id;
                $item['sub_category_id'] = $product->sub_category_id;
            }

            $excludedProductIds = $coupon->excludedProducts->pluck('id')->toArray();
            $excludedCategoryIds = $coupon->excludedCategories->pluck('id')->toArray();
            $excludedSubcategoryIds = $coupon->excludedSubcategories->pluck('id')->toArray();

            // ✅ فلترة العناصر المؤهلة
            $eligibleItems = array_filter($items, function ($item) use (
                $excludedProductIds,
                $excludedCategoryIds,
                $excludedSubcategoryIds
            ) {
                return !in_array($item['product_id'], $excludedProductIds)
                    && !in_array($item['category_id'], $excludedCategoryIds)
                    && !in_array($item['sub_category_id'], $excludedSubcategoryIds);
            });

            if (empty($eligibleItems)) {
                return [__('messages.coupon_product_not_include'), 422];
            }

            // ✅ حساب المجموع للعناصر المؤهلة فقط
            $eligibleTotal = array_reduce($eligibleItems, function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0.0);


            if ($coupon->min_order_total && $eligibleTotal < $coupon->min_order_total) {
                return [__('messages.coupon_product_must_be_at_least', ['min_order_total' => number_format($coupon->min_order_total, 2)]), 422];
            }

            // يمكنك الآن إرجاع الكوبون والمبلغ المؤهل للخصم
            $coupon->total_price = $eligibleTotal; // إن أردت إرفاقها مؤقتًا            
            // unset($coupon['excluded_products'], $coupon['excluded_categories'], $coupon['excluded_subcategories'], $data['excluded_subcategory_ids']);

            return $coupon;
        } catch (\Throwable $th) {
            return [__('messages.something_went_wrong'), 404];
        }
    }
}
