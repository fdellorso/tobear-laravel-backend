<?php

use App\Http\Controllers\V1\AlbumController;
use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/myimages/{image}/delete', [ImageController::class, 'destroy']);

    Route::apiResource('/myimages', ImageController::class)->only([
        'index',
        'store',
        'destroy'
    ]);
});

Route::prefix('v1')->group(function () {
    Route::apiResource('album', AlbumController::class);
});

require __DIR__ . '/auth.php';
