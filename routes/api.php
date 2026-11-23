<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicCatalogController;
use App\Http\Controllers\Api\Seller\SellerAuthController;
use App\Http\Controllers\Api\Seller\SellerStandController;
use App\Http\Controllers\Api\Seller\SellerProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Catalog API
Route::prefix('v1')->group(function () {
    Route::get('/categories', [PublicCatalogController::class, 'categories']);
    Route::get('/products', [PublicCatalogController::class, 'products']);
    Route::get('/products/{id}', [PublicCatalogController::class, 'show']);
    Route::post('/products/{id}/reviews', [PublicCatalogController::class, 'storeReview']);
});

// REST API Role Penjual (Seller)
Route::prefix('seller')->group(function () {
    // 1. Autentikasi Penjual (Public)
    Route::post('/login', [SellerAuthController::class, 'login']);

    // Protected Routes (auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [SellerAuthController::class, 'logout']);
        Route::get('/me', [SellerAuthController::class, 'me']);

        // 2. Dashboard & Status Stand
        Route::get('/dashboard', [SellerStandController::class, 'dashboard']);
        Route::patch('/stand/status', [SellerStandController::class, 'updateStatus']);

        // 3. Profil Stand (multipart/form-data)
        Route::post('/stand/profile', [SellerStandController::class, 'updateProfile']);

        // 4. CRUD Produk Penjual
        Route::get('/products', [SellerProductController::class, 'index']);
        Route::post('/products', [SellerProductController::class, 'store']);
        Route::match(['post', 'put'], '/products/{id}', [SellerProductController::class, 'update']);
        Route::patch('/products/{id}/stock', [SellerProductController::class, 'updateStock']);
        Route::delete('/products/{id}', [SellerProductController::class, 'destroy']);
    });
});
