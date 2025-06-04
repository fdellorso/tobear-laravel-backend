<?php

use App\Http\Controllers\V1\AlbumController;
use App\Http\Controllers\V1\ImageController;
use App\Http\Controllers\V1\ImageManipulationController;
use App\Http\Controllers\V1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('v1')->group(function () {
        Route::patch('/tasks/reorder', [TaskController::class, 'reorder']);
        Route::apiResource('tasks', TaskController::class);

        Route::post('/myimages/{image}/delete', [ImageController::class, 'destroy']);

        Route::apiResource('/myimages', ImageController::class)->only([
            'index',
            'store',
            'destroy'
        ]);

        Route::apiResource('album', AlbumController::class);

        Route::get('image', [ImageManipulationController::class, 'index']);
        Route::get('image/by-album/{album}', [ImageManipulationController::class, 'byAlbum']);
        Route::get('image/{image}', [ImageManipulationController::class, 'show']);
        Route::post('image/resize', [ImageManipulationController::class, 'resize']);
        Route::post('image/{image}/delete', [ImageManipulationController::class, 'destroy']);
    });
});

require __DIR__ . '/auth.php';
