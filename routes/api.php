<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CodeController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PointsRedemptionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZohoAuthController;
use App\Http\Controllers\SiteStatusController;
use App\Http\Controllers\SessionTimeoutController;
use App\Http\Controllers\AbandonedCartController;
use App\Http\Controllers\SuspiciousTransactionController;
use App\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;
// routes/web.php
use Torann\GeoIP\Facades\GeoIP;

Route::get('/test-geoip', function () {
    $ip = request()->ip();
    $location = GeoIP::get($ip);

    return response()->json([
        'ip' => $ip,
        'country' => $location['country'] ?? null,
        'iso_code' => $location['iso_code'] ?? null,
    ]);
});

// Maintenance Mode Management Routes (outside maintenance check middleware)
// Public route - Get maintenance status (no authentication required)
Route::get('/maintenance/status', [MaintenanceController::class, 'getStatus']);

// Admin routes - Require authentication and permissions
Route::group(['prefix' => '/maintenance', 'middleware' => ['should_auth', 'auth:sanctum', 'custom_permission:permission:manage settings'], 'controller' => MaintenanceController::class], function () {
    Route::get('/toggle', 'toggle');
});

Route::middleware(['not_blocked_country', 'throttle.by-route:5,1', 'check_maintenance'])->group(function () {

    Route::controller(MainController::class)->group(function () {
        Route::get('/mobile-main-content', 'getMobileMainScreen');
        Route::get('/categories-subcategories', 'getCategoriesWithSubCategories');
        Route::get('/main-content', 'getMainContent');
        Route::get('/category/{slug}', 'getCategory')->name('getCategory');
        Route::get('/sub-category/{slug}', 'getSubCategory')->name('getSubCategory');
        Route::get('/product/{slug}', 'getProduct')->name('getProduct');
        Route::get('/ratings/product/{id}', 'getProductRatings')->name('getProductRatings');
        Route::get('/faqs', 'getFAQS');
        Route::get('/search', 'search');
        Route::get('/payment-methods', 'getPaymentMethods');
    });

    Route::controller(SeoController::class)->group(function () {
        Route::get('/get/seo', 'get');
        Route::post('/update/seo', 'update')->middleware(['should_auth', 'auth:sanctum', 'custom_permission:permission:manage settings']);
    });

    Route::group(['middleware' => ['should_auth', 'auth:sanctum'], 'controller' => InterestController::class], function () {
        Route::get('/get/interests', 'myInterests');
        Route::post('/create/interest', 'store');
        Route::get('/delete/interest/{id}', 'delete');
    });

    Route::post('/assistant/ask', [FaqController::class, 'ask']);

    // Public route - Get current site status (no authentication required)
    Route::get('/site-status/current', [SiteStatusController::class, 'getCurrentStatus']);

    Route::controller(PaymentController::class)->group(function () {
        Route::post('/payment/paymob/callback/processed', 'handlePaymobWebhook')->name('handlePaymobWebhook');
        Route::get('/payment/paymob/result', 'result')->name('result');
    });

    Route::get('/zoho/connect', [ZohoAuthController::class, 'connect'])->name('zoho.connect');
    Route::get('/zoho/callback', [ZohoAuthController::class, 'callback'])->name('zoho.callback');


    Route::controller(AuthController::class)->as('auth.')->group(function () {
        Route::post('/register', 'register')->name('register');
        Route::post('/login', 'login')->name('login');
        Route::post('/register-phone', 'registerWithPhone');
        Route::post('/login-phone', 'loginWithPhone')->name('loginWithPhone');
        Route::post('/update-user', 'updateUser')->name('updateUser')->middleware(['should_auth', 'auth:sanctum']);
        Route::post('confirm-otp', 'confirmOtp')->name('confirmOtp');
        Route::post('/logout', 'logout')->middleware(['should_auth', 'auth:sanctum'])->name('logout');
        Route::post('/forgot-password', 'forgotPassword')->name('forgotPassword');
        Route::post('/reset-password', 'resetPassword')->name('resetPassword');
    });
    // Social Authentication Routes
    Route::prefix('social')->group(function () {
        Route::get('/providers', [SocialAuthController::class, 'getProviders']);
        Route::post('/login', [SocialAuthController::class, 'socialLogin']);
        Route::get('/redirect/{provider}', [SocialAuthController::class, 'redirect']);
        Route::get('/callback/{provider}', [SocialAuthController::class, 'callback']);
        Route::get('/stats', [SocialAuthController::class, 'getStats']);

        // Authenticated routes
        Route::middleware(['should_auth', 'auth:sanctum'])->group(function () {
            Route::post('/link-account', [SocialAuthController::class, 'linkAccount']);
            Route::post('/unlink-account', [SocialAuthController::class, 'unlinkAccount']);
        });
    });
});

Route::middleware(['should_auth', 'auth:sanctum'])->group(function () {
    Route::get('/users/paid/product-{product_id}', [OrderController::class, 'getUsersPaidProduct'])->middleware('custom_permission:permission:manage orders');

    Route::controller(MainController::class)->group(function () {
        Route::get('/user/my-info', 'myInfo');
        Route::get('/user/my-referrals', 'myReferrals');
        Route::get('/user/my-points', 'getMyPoints');
        Route::get('/user/my-wallet', 'getMyWallet');
        Route::get('/user/total-paid', 'getTotalPaid');
        Route::get('/orders', 'getOrders');
        Route::get('/order/{id}/items', 'getOrderItems');
    });
    Route::get('/user/increase-my-redeem', [PointsRedemptionController::class, 'redeem']);
    Route::post('/rating/create', [RatingController::class, 'store']);
    Route::middleware(['custom_permission:permission:manage settings'])->group(function () {
        Route::get('/faq/all', [FaqController::class, 'getAdminFAQS']);
        Route::post('/faq/create', [FaqController::class, 'store']);
        Route::get('/faq/delete/{id}', [FaqController::class, 'delete']);
    });

    Route::group(['middleware' => 'custom_permission:permission:manage categories', 'controller' => CategoryController::class], (function () {
        Route::get('/categories', 'index');
        Route::group(['prefix' => '/category', 'as' => 'category.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::get('/show/{id}', 'show')->name('show');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    }));
    Route::group(['middleware' => 'custom_permission:permission:manage subcategories', 'controller' => SubCategoryController::class], (function () {
        Route::get('/sub-categories', 'index');
        Route::get('/get/categories', 'getCategories');
        Route::get('/get/category/{categoryId}/sub-categories', 'getSubCategoriesByCategory');
        Route::group(['prefix' => '/sub-category', 'as' => 'subcategory.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::get('/show/{id}', 'show')->name('show');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    }));

    Route::group(['middleware' => 'custom_permission:permission:manage products', 'controller' => ProductController::class], (function () {
        Route::get('/categories/{category_id}/sub-categories/products', 'categorySubcategoryProducts');
        Route::get('/sub-categories/{sub_category_id}/products', 'subcategoryProducts');
        Route::get('/products', 'index');
        Route::get('/products/stats', 'getProductsStats');
        Route::get('/products/ensure-subcategories', 'ensureAllProductsHaveSubCategory');
        Route::group(['prefix' => '/product', 'as' => 'product.'], function () {
            Route::post('/create', 'store')->name('create');
            Route::get('/show/{id}', 'show')->name('show');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    }));

    Route::group(['middleware' => 'custom_permission:permission:manage codes', 'controller' => CodeController::class], (function () {
        Route::get('/codes', 'index');
        Route::get('/product/{product_id}/codes', 'ProductCodes');
        Route::group(['prefix' => '/code', 'as' => 'code.'], function () {
            Route::get('/get-products', 'getProducts');
            Route::post('/create', 'store')->name('create');
            Route::get('/show/{id}', 'show')->name('show');
            Route::post('/update/{id}', 'update')->name('update');
            Route::get('/delete/{id}', 'delete')->name('delete');
        });
    }));

    Route::group(['middleware' => 'custom_permission:permission:manage coupons', 'controller' => CouponController::class], (function () {
        Route::get('/coupons',  'index');
        Route::group(['prefix' => '/coupon', 'as' => 'coupon.'], function () {
            Route::get('/options',  'optionsCoupon');
            Route::post('/create',  'create');
            Route::get('/delete/{id}',  'delete');
            Route::post('/apply-coupon',  'applyCoupon')->withoutMiddleware('custom_permission:role:admin');
        });
    }));

    Route::group(['middleware' => 'custom_permission:permission:manage coupons', 'controller' => SliderController::class], (function () {
        Route::get('/sliders', 'index');
        Route::post('/slider/create', 'store');
        Route::get('/slider/{id}', 'delete');
    }));

    Route::group(['prefix' => '/order', 'middleware' => 'custom_permission:permission:manage orders', 'as' => 'order.', 'controller' => OrderController::class], function () {
        Route::withoutMiddleware(['custom_permission:permission:manage orders'])->group(function () {
            Route::post('/create', 'store')->name('create');
            Route::post('/pay', 'pay')->name('pay');
        });
        Route::get('/admin/get-orders', 'getAdminOrders');
        Route::get('/get-order-items/{id}', 'getOrderItems');
        Route::get('/orders-statistics', 'orderStatistics');
        Route::get('/orders-count-statistics', 'ordersCountStats');
        Route::post('/orders-count-statistics-manual', 'ordersCountStatsManual');
        Route::post('/refund/transaction', 'refundTransaction');
        Route::post('/refund', 'refundOrder');
        Route::post('/upload/proof-file', 'uploadProofFile');
    });

    Route::group(['prefix' => '/users', 'middleware' => 'custom_permission:permission:manage users', 'controller' => UserController::class], function () {
        Route::get('/', 'getAllCustomerUsers');
        Route::get('/all', 'getAllUsers');
        Route::get('/delete/{user_id}', 'deleteUser');
        Route::get('/get/permissions', 'getPermissions');
        Route::post('/create', 'createCustomerUser');
        Route::post('/update/{user}/permissions', 'updateUserPermissions');
    });

    Route::group(['prefix' => '/employees', 'middleware' => 'custom_permission:permission:manage users', 'controller' => EmployeeController::class], function () {
        Route::get('/', 'getAllEmployees');
        Route::post('/create', 'createEmployee');
        Route::get('/delete/{user_id}', 'deleteEmployee');
    });

    Route::get('/rating', [RatingController::class, 'all'])->middleware('custom_permission:permission:manage ratings');


    Route::group(['prefix' => '/tickets', 'controller' => TicketController::class], function () {
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::get('/{ticket}', 'show');

        // رسائل التذكرة
        Route::get('/{ticket}/all-replies', 'getAllReplies');
        Route::post('/{ticket}/reply', 'reply');

        // صلاحيات المشرف فقط
        Route::middleware('can:reply tickets')->group(function () {
            Route::get('/{ticket_id}/all-replies', 'getAllReplies');
            Route::get('/admin/tickets', 'adminIndex');
            Route::post('/{ticket}/status', 'updateStatus');
        });
    });



    Route::group(['prefix' => '/notifications', 'controller' => NotificationController::class], function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::get('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-multiple', [NotificationController::class, 'markMultipleAsRead']);
        Route::get('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // Site Status Management Routes - Admin routes (Require authentication and permissions)
    Route::group(['prefix' => '/site-status', 'middleware' => 'custom_permission:permission:manage site status', 'controller' => SiteStatusController::class], function () {
        Route::post('/update', 'updateStatus');
        Route::get('/available', 'getAvailableStatuses');
        Route::get('/toggle', 'toggleStatus');
    });

    // Session Timeout Management Routes
    Route::group(['prefix' => '/session-timeout', 'controller' => SessionTimeoutController::class], function () {
        // Public routes (for authenticated users)
        Route::middleware(['should_auth', 'auth:sanctum'])->group(function () {
            Route::get('/info', 'getUserSessionInfo');
            Route::post('/extend', 'extendUserSession');
            Route::post('/clear', 'clearUserSession');
            Route::post('/set', 'setUserSession');
        });

        // Admin routes (for managing timeout settings)
        Route::middleware(['should_auth', 'auth:sanctum', 'custom_permission:permission:manage settings'])->group(function () {
            Route::get('/settings', 'getTimeoutSettings');
            Route::post('/update', 'updateTimeout');
        });
    });

    // Abandoned Cart Management Routes
    Route::group(['prefix' => '/abandoned-carts', 'middleware' => ['should_auth', 'auth:sanctum', 'custom_permission:permission:manage abandoned carts'], 'controller' => AbandonedCartController::class], function () {
        Route::get('/', 'getAbandonedCarts');
        Route::get('/stats', 'getStats');
        Route::get('/settings', 'getSettings');
        Route::post('/send-notification', 'sendNotification');
        Route::post('/send-bulk-notifications', 'sendBulkNotifications');
        Route::post('/mark-recovered', 'markAsRecovered');
        Route::post('/trigger-processing', 'triggerProcessing');
    });

    // Suspicious Transaction Management Routes
    Route::group(['prefix' => '/suspicious-transactions', 'middleware' => ['should_auth', 'auth:sanctum', 'custom_permission:permission:manage orders'], 'controller' => SuspiciousTransactionController::class], function () {
        Route::get('/', 'getSuspiciousTransactions');
        Route::get('/stats', 'getStats');
        Route::get('/settings', 'getSettings');
        Route::get('/{id}', 'getSuspiciousTransaction');
        Route::post('/analyze', 'analyzeTransaction');
        Route::post('/review', 'reviewTransaction');
        Route::post('/bulk-review', 'bulkReview');
        Route::post('/export', 'export');
    });
});
