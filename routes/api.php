<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

// Route::get('a', function () {
//     return response()->json(['message' => 'Hello, API!']);
// });


Route::controller(AuthController::class)->as('auth.')->group(function () {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('logout');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::group(['prefix' => '/category', 'as' => 'category.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    });
});
