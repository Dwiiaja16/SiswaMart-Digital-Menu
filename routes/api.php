<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicCatalogController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('/categories', [PublicCatalogController::class, 'categories']);
    Route::get('/products', [PublicCatalogController::class, 'products']);
    Route::get('/products/{id}', [PublicCatalogController::class, 'show']);
    Route::post('/products/{id}/reviews', [PublicCatalogController::class, 'storeReview']);
});