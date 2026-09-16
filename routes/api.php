<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\General\SettingController;
use App\Http\Controllers\General\CouponController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\ProductFeatureController;
use App\Http\Controllers\Product\WarrantyController;
use App\Http\Controllers\Content\ReviewController;
use App\Http\Controllers\Content\QuestionController;
use App\Http\Controllers\Content\TicketController;
use App\Http\Controllers\User\PermissionController;
use App\Http\Controllers\General\ReportController;
use App\Http\Controllers\General\NotificationController;
use App\Http\Controllers\Order\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductController::class, 'index']);
Route::get('/product-filters', [ProductController::class, 'getFilters']);
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
Route::get('/products/{productId}/questions', [QuestionController::class, 'index']);

Route::get('/products/{id}/{name?}', [ProductController::class, 'show']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/settings', [SettingController::class, 'index']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}/features', [ProductFeatureController::class, 'getFeaturesByCategory']);

Route::get('/warranties', [WarrantyController::class, 'index']);

Route::post('/register', [AuthController::class, 'registerUser']);
Route::post('/login', [AuthController::class, 'loginUser'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'sanctum.stateful'])->group(function () {

    // --- User Dashboard ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard-stats', [DashboardController::class, 'index']);
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::get('/user-permissions', [PermissionController::class, 'index']);

    // --- Orders (History & Management) ---
    Route::get('/user/orders', [OrderController::class, 'index']);
    Route::get('/user/orders/{id}', [OrderController::class, 'show']);

    // --- Coupons ---
    Route::post('/coupons/apply', [CouponController::class, 'apply']);
    Route::get('/user/coupons', [CouponController::class, 'userCoupons']);

    // Admin CRUD for Coupons
    Route::apiResource('admin/coupons', CouponController::class);

    // --- Support Tickets ---
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply']);

    // --- Notifications (Fixed Order) ---
    Route::get('/notifications/counts', [NotificationController::class, 'getUnreadCounts']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/by-type', [NotificationController::class, 'getNotificationsByType']);
    Route::apiResource('notifications', NotificationController::class);

    // --- Product Reviews & Reactions & Reports ---
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    Route::post('/reviews/{review}/react', [ReviewController::class, 'react']);
    Route::post('/reviews/{review}/report', [ReportController::class, 'storeReviewReport']);

    // --- Product Questions & Reactions ---
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::put('/questions/{question}', [QuestionController::class, 'update']);
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);
    Route::post('/questions/{question}/react', [QuestionController::class, 'react']);

    // --- Admin Review Management ---
    Route::get('/admin/reviews', [ReviewController::class, 'adminIndex']);
    Route::patch('/admin/reviews/{review}/approval', [ReviewController::class, 'toggleApproval']);
    Route::patch('/admin/media/{media}/approval', [ReviewController::class, 'toggleMediaApproval']);

    // --- Admin Question Management ---
    Route::get('/admin/questions', [QuestionController::class, 'adminIndex']);
    Route::patch('/admin/questions/{question}/approval', [QuestionController::class, 'toggleApproval']);

    // --- User Interactions ---
    Route::get('/user/reviews', [ReviewController::class, 'userReviews']);
    Route::get('/user/questions', [ReviewController::class, 'userQuestions']);

    // --- User Management ---
    Route::get('/users/deleted', [UserController::class, 'deletedUsers']);
    Route::post('/users/{id}/restore', [UserController::class, 'restore']);
    Route::delete('/users/{id}/force-delete', [UserController::class, 'forceDelete']);
    Route::post('/users/{user}/promote', [UserController::class, 'promote']);
    Route::post('/users/{user}/demote', [UserController::class, 'demote']);
    Route::put('/users/{id}/permissions', [UserController::class, 'updatePermissions']);

    // --- Edit User Profile & Password ---
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);

    Route::apiResource('users', UserController::class);

    Route::apiResource('categories', CategoryController::class)->except(['index']);

    Route::apiResource('warranties', WarrantyController::class);

    Route::apiResource('products', ProductController::class)->except(['index', 'show', 'edit']);

    Route::post('/settings', [SettingController::class, 'update']);
});
