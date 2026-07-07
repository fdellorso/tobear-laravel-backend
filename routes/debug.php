<?php

use App\Http\Controllers\ExecuteArtisanCommandController;
use Illuminate\Support\Facades\Route;

Route::middleware('debug-token')->group(function () {
    Route::get('/serverphpinfo', function () {
        phpinfo();
    })->name('serverphpinfo');

    Route::get('/laravelversion', function () {
        return ['Laravel' => app()->version()];
    })->name('laravelversion');

    Route::get('/artisan/{name}', ExecuteArtisanCommandController::class);

    Route::post('/artisan/migrate', [ExecuteArtisanCommandController::class, 'migrate']);
});
