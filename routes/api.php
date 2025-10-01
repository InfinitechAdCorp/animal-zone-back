<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\AdminController;


use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Seller registration (public)
Route::post('/seller/register', [SellerController::class, 'register']);

// Admin routes (should be protected with auth middleware)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/sellers', [AdminController::class, 'getSellerApplications']);
    Route::get('/sellers/{id}', [AdminController::class, 'getSellerApplication']);
    Route::post('/sellers/{id}/approve', [AdminController::class, 'approveSeller']);
    Route::post('/sellers/{id}/reject', [AdminController::class, 'rejectSeller']);
    Route::get('/statistics', [AdminController::class, 'getStatistics']);
    Route::get('/sellers/{id}/documents/{documentType}', [AdminController::class, 'viewDocument']);
});
