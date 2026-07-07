<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/api/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['web', 'auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

require __DIR__.'/debug.php';

Route::fallback(function () {
    $path = public_path('app/index.html');
    if (! file_exists($path)) {
        return redirect(config('app.frontend_url'));
    }

    return response(file_get_contents($path));
});

// require __DIR__ . '/auth.php';
