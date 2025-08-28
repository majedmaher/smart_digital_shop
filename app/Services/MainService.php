<?php

namespace App\Services;

use App\Enum\PaymentProviderEnum;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\OrderItemResponseResource;
use App\Http\Resources\OrderResponseResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\RatingResource;
use App\Http\Resources\SliderResource;
use App\Http\Resources\SubCategoryResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Slider;
use App\Models\SubCategory;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;

use function PHPUnit\Framework\isEmpty;

class MainService extends Controller
{
    static function getMobileMainScreen(): JsonResponse
    {
        try {

            $categories = Category::with(['subCategories' => function ($query) {
                $query->withCount('children')->whereNull('parent_id')->latest()->get();
            }])->getNecessaryData()
                ->latest()
                ->get();

            $sliders = Slider::latest()->get();
            $products = Product::where('is_active', 1)->latest()->take(4)->get();


            $data = ['categories' => CategoryResource::collection($categories), 'sliders' => SliderResource::collection($sliders), 'best_seller' => ProductResource::collection($products), 'newly_arrived' => ProductResource::collection($products), 'suggested_products' => ProductResource::collection($products)];

            return BaseController::sendResponse($data, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    static function getCategoriesWithSubCategories(): JsonResponse
    {
        try {

            $categories = Category::with(['subCategories' => function ($query) {
                $query->withCount('children')->whereNull('parent_id')->latest()->get();
            }])->getNecessaryData()
                ->latest()
                ->get();

            return BaseController::sendResponse(CategoryResource::collection($categories), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    static function getMainContent(): JsonResponse
    {
        try {

            $sliders = Slider::latest()->get();
            $products = Product::where('is_active', 1)->latest()->take(4)->get();


            $data = ['sliders' => SliderResource::collection($sliders), 'best_seller' => ProductResource::collection($products), 'newly_arrived' => ProductResource::collection($products), 'suggested_products' => ProductResource::collection($products)];

            return BaseController::sendResponse($data, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getCategory($slug): JsonResponse
    {
        try {
            $category = Category::with(['subCategories' => function ($query) {
                $query->withCount('children')->whereNull('parent_id')->latest()->get();
            }])->getNecessaryData()->whereJsonContainsLocales('slug', ['en', 'ar'], $slug)->first();
            if ($category === null) return BaseController::sendError(__('messages.search_item_not_found'), [], 422);
            return BaseController::sendResponse(CategoryResource::make($category), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getSubCategory($slug): JsonResponse
    {
        try {
            // $locale = app()->getLocale(); // 'en' أو 'ar'

            // $subCategory = SubCategory::where("slug->{$locale}", $slug)->first();
            $subCategory = SubCategory::query()->whereJsonContainsLocales('slug', ['en', 'ar'], $slug)->first();
            if ($subCategory === null) return BaseController::sendError(__('messages.search_item_not_found'), [], 422);

            if ($subCategory->children()->exists()) {
                $children = $subCategory->children()->getNecessaryData()->get();
                $response = [
                    'type' => 'children',
                    'children' => SubCategoryResource::collection($children)
                ];
            } else {
                $products = $subCategory->products()->get();
                $response = [
                    'type' => 'products',
                    'products' => ProductResource::collection($products)
                ];
            }

            return BaseController::sendResponse($response, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getProduct($slug): JsonResponse
    {
        try {
            $product = Product::with('ratings')->whereJsonContainsLocales('slug', ['en', 'ar'], $slug)->first();
            if ($product === null) return BaseController::sendError(__('messages.search_item_not_found'), [], 422);
            return BaseController::sendResponse(ProductResource::make($product), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getProductRatings($id): JsonResponse
    {
        try {
            $ratings = Rating::where('product_id', $id)->latest()->get();
            return BaseController::sendResponse(RatingResource::collection($ratings), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getFAQS(): JsonResponse
    {
        try {
            $faqs = Faq::latest()->get();
            return BaseController::sendResponse(FaqResource::collection($faqs), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getOrders(): JsonResponse
    {
        try {
            $orders = Order::where('user_id', auth()->id())->latest()->get();
            return BaseController::sendResponse(OrderResponseResource::collection($orders), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getOrderItems($id): JsonResponse
    {
        try {
            $items = Order::find($id)->items()->with(['product' => function ($query) {
                $query->select('id', 'title'); // تحديد الأعمدة التي تريد جلبها من جدول products
            }])->get();

            return BaseController::sendResponse(OrderItemResponseResource::collection($items), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [$th->getMessage()], 500);
        }
    }

    public static function getTotalPaid($currency): JsonResponse
    {
        try {
            $totalPaid = Order::where('user_id', auth()->id())
                ->where('status', 'paid') // إذا عندك حالة دفع
                ->sum('total_price');
            return BaseController::sendResponse(currencyConverter($totalPaid ?? 0, $currency), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function myReferrals(): JsonResponse
    {
        try {
            $data = [
                'total_referrals' => auth()->user()->successfulReferrals()->count(),
                'points_earned' => auth()->user()->successfulReferrals()->count() * 1000,
                'referrals' => auth()->user()->successfulReferrals()->get(),
            ];
            return BaseController::sendResponse($data, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getMyPoints(): JsonResponse
    {
        try {
            return BaseController::sendResponse(auth()->user()->points, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getMyWallet($currency): JsonResponse
    {
        try {
            $user = auth()->user();
            // $walletTransactions = WalletTransaction::where('user_id', $user->id)->latest()->get();
            $response = [
                'wallet_balance' => currencyConverter($user->wallet_balance, $currency),
                'points_balance' => $user->points,
                'points_to_cash' => currencyConverter(($user->points / 1000 * 0.5), $currency),
                'wallet_transactions' => WalletTransactionResource::collection($user->walletTransactions)
            ];
            return BaseController::sendResponse($response, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [$th->getMessage()], 500);
        }
    }

    public static function myInfo(): JsonResponse
    {
        try {
            return BaseController::sendResponse(auth()->user(), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getPaymentMethods(): JsonResponse
    {
        try {
            $paymentMethods = collect(PaymentProviderEnum::cases())
                ->map(fn($method) => [
                    'value' => $method->value,
                    'label' => $method->label(),
                    'image' => asset($method->image()), // تأكد من مسار الصورة
                ])
                ->toArray();

            return BaseController::sendResponse($paymentMethods, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function search($query): JsonResponse
    {
        try {
            if (!$query) return BaseController::sendError(__('messages.please_enter_a_word_to_search'), [], 422);

            $products = Product::select('id', 'title', 'slug')->where('title->en', 'like', "%{$query}%")
                ->orWhere('title->ar', 'like', "%{$query}%")
                ->orWhere('description->ar', 'like', "%{$query}%")
                ->orWhere('description->en', 'like', "%{$query}%")
                ->get();

            $categories = Category::select('id', 'name', 'slug')->where('name->en', 'like', "%{$query}%")
                ->orWhere('name->ar', 'like', "%{$query}%")
                ->get();

            $subCategories = SubCategory::select('id', 'name', 'slug')->where('name->en', 'like', "%{$query}%")
                ->orWhere('name->ar', 'like', "%{$query}%")
                ->get();

            $response = [
                'products' => ProductResource::collection($products),
                'categories' => CategoryResource::collection($categories),
                'sub_categories' => SubCategoryResource::collection($subCategories)
            ];
            return BaseController::sendResponse($response, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }
}
