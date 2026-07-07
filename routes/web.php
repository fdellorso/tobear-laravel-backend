<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/api/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['web', 'auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

require __DIR__.'/debug.php';

Route::get('/{any}', function () {
    $path = public_path('app/index.html');

    return file_exists($path)
        ? response(file_get_contents($path))
        : redirect(config('app.frontend_url'));
})->where('any', '^(?!api|assets|css|js).*$');

// require __DIR__ . '/auth.php';
