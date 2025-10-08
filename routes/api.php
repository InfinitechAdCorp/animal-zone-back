<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Models\Category;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DeliveryInfoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
});


Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', [UserController::class, 'show']);
    Route::patch('/profile', [UserController::class, 'update']);
});

Route::middleware('auth:sanctum')->prefix('seller')->group(function () {
    Route::get('/products', [SellerController::class, 'getSellerProducts']);
    Route::patch('/products/{id}/status', [SellerController::class, 'updateProductStatus']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/delivery-info', [DeliveryInfoController::class, 'show']);
    Route::post('/delivery-info', [DeliveryInfoController::class, 'storeOrUpdate']);
});

Route::get('/product-categories', function () {
    return ProductCategory::all();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::patch('/cart/{id}', [CartController::class, 'update']); // ✅ for + / -
    Route::delete('/cart/{id}', [CartController::class, 'destroy']); // ✅ for delete
});

Route::get('/sellers/slug/{slug}', [SellerController::class, 'getSellerInfoBySlug']);
Route::get('/sellers/slug/{slug}/products', [SellerController::class, 'getSellerProductsBySlug']);

// Public: product categories & pet types
Route::get('/product-categories', function () {
    return ProductCategory::all();
});

Route::get('/pet-types', function () {
    return Category::all(); // ibabalik lahat ng rows sa `categories` table (Dog, Cat, Bird, etc.)
});


Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);


// Email verification
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['signed']) // wag auth:sanctum
    ->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent!']);
})->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');

// Public Auth Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);

// Protected Admin Routes
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/statistics', [AdminController::class, 'statistics']);

    // Seller management
    Route::get('/sellers', [SellerController::class, 'index']); 
    Route::get('/sellers/{id}', [SellerController::class, 'show']);
    Route::post('/sellers/{id}/approve', [SellerController::class, 'approve']);
    Route::post('/sellers/{id}/reject', [SellerController::class, 'reject']);

    // ✅ Product management (Admin only)
    Route::get('/products', [AdminController::class, 'products']); // list all products
 Route::patch('/products/{id}/status', [AdminController::class, 'updateProductStatus']);
 Route::get('/admin/products/pending', [AdminController::class, 'getPendingProducts']);


});

// Seller documents
Route::get('/sellers/{id}/documents/{type}', [SellerController::class, 'getDocument']);

// Seller registration
Route::post('/seller/register', [SellerController::class, 'register']);

// Authenticated user info
Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

// Seller product upload
Route::middleware('auth:sanctum')->prefix('seller')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
});
