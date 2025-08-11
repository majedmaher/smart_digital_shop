<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CodeController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::controller(MainController::class)->group(function () {
    Route::get('/mobile-main-content', 'getMobileMainScreen');
    Route::get('/categories-subcategories', 'getCategoriesWithSubCategories');
    Route::get('/main-content', 'getMainContent');
    Route::get('/category/{slug}', 'getCategory')->name('getCategory');
    Route::get('/sub-category/{slug}', 'getSubCategory')->name('getSubCategory');
    Route::get('/product/{slug}', 'getProduct')->name('getProduct');
    Route::get('/faqs', 'getFAQS');
    Route::get('/orders', 'getOrders')->middleware(['should_auth', 'auth:sanctum']);
    Route::get('/search', 'search');
});
Route::post('/assistant/ask', [FaqController::class, 'ask']);

Route::controller(PaymentController::class)->group(function () {
    Route::post('/payment/paymob/callback/processed', 'handlePaymobWebhook')->name('handlePaymobWebhook');
    Route::get('/payment/paymob/result', 'result')->name('result');
});

Route::controller(AuthController::class)->as('auth.')->group(function () {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::post('confirm-otp', 'confirmOtp')->name('confirmOtp');
    Route::post('/logout', 'logout')->middleware(['should_auth', 'auth:sanctum'])->name('logout');
});
Route::post('social-login', [SocialAuthController::class, 'socialLogin']);

Route::prefix('social')->group(function () {
    Route::get('redirect/{provider}', [SocialAuthController::class, 'redirect']);
    Route::get('callback/{provider}', [SocialAuthController::class, 'callback']);
});

Route::get('/rating', [RatingController::class, 'all']);
Route::middleware(['should_auth', 'auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/rating/create', [RatingController::class, 'store']);
    Route::post('/faq/create', [FaqController::class, 'store']);
});

Route::middleware(['should_auth', 'auth:sanctum', 'custom_permission:role:admin'])->group(function () {

    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::group(['prefix' => '/category', 'as' => 'category.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    });
    Route::controller(SubCategoryController::class)->group(function () {
        Route::get('/sub-categories', 'index');
        Route::get('/categories-and-sub-categories', 'getCategoriesAndSubcategories');
        Route::group(['prefix' => '/sub-category', 'as' => 'subcategory.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    });
    Route::controller(ProductController::class)->group(function () {
        Route::get('/sub-categories/{sub_category_id}/products', 'subcategoryProducts');
        Route::get('/products', 'index');
        Route::group(['prefix' => '/product', 'as' => 'product.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    });
    Route::controller(CodeController::class)->group(function () {
        Route::get('/product/{product_id}/codes', 'index');
        Route::group(['prefix' => '/code', 'as' => 'code.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    });
    Route::post('/coupon/create', [CouponController::class, 'create']);
    Route::post('/coupon/apply-coupon', [CouponController::class, 'applyCoupon'])->withoutMiddleware('custom_permission:role:admin');

    Route::controller(SliderController::class)->group(function () {
        Route::get('/sliders', 'index');
        Route::post('/slider/create', 'store');
        Route::get('/slider/{id}', 'delete');
    });

    Route::group(['prefix' => '/order', 'as' => 'order.', 'controller' => OrderController::class], function () {
        Route::withoutMiddleware(['custom_permission:role:admin'])->group(function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/pay', 'pay')->name('pay');
        });
        Route::post('/refund/transaction', 'refundTransaction');
        Route::post('/refund', 'refundOrder');
    });
});
Route::group(['prefix' => '/tickets', 'middleware' => ['should_auth', 'auth:sanctum'], 'controller' => TicketController::class], function () {
    Route::get('/', 'index');
    Route::post('/create', 'store');
    Route::get('/{ticket}', 'show');

    // رسائل التذكرة
    Route::post('/{ticket}/reply', 'reply');

    // صلاحيات المشرف فقط
    Route::middleware('can:reply to messages')->group(function () {
        Route::get('/admin/tickets', 'adminIndex');
        Route::patch('/tickets/{ticket}/status', 'updateStatus');
    });
});
