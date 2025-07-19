<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CodeController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;

// Route::get('a', function () {
//     return response()->json(['message' => 'Hello, API!']);
// });


Route::controller(AuthController::class)->as('auth.')->group(function () {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::post('confirm-otp', 'confirmOtp')->name('confirmOtp');
    Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('logout');
});

Route::prefix('auth')->group(function () {
    Route::get('redirect/{provider}', [SocialAuthController::class, 'redirect']);
    Route::get('callback/{provider}', [SocialAuthController::class, 'callback']);
});

Route::get('/rating', [RatingController::class, 'all']);
Route::middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::post('/rating/create', [RatingController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::get('/categories-subcategories', [MainController::class, 'getCategoriesWithSubCategories']);

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
        Route::group(['prefix' => '/sub-category', 'as' => 'subcategory.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    });
    Route::controller(ProductController::class)->group(function () {
        Route::get('/sub-categories/{sub_category_id}/products', 'index');
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
});
