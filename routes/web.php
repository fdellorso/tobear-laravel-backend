<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExecuteArtisanCommandController;
// use App\Http\Controllers\FileController;
// use Illuminate\Support\Facades\Mail;

Route::get('/laravelversion', function () {
    return ['Laravel' => app()->version()];
})->name('laravelversion');

Route::get('/serverphpinfo', function () {
    phpinfo();
})->name('serverphpinfo');

Route::get('/artisan/{name_of_command}', ExecuteArtisanCommandController::class);

// Route::get('/assets/{filename}', [FileController::class, 'show']);

// Route::get('/test-email', function () {
//     try {
//         Mail::raw('Test SMTP x10', function ($msg) {
//             $msg->to('fdellorso@ymail.com')->subject('Prova SMTP');
//         });
//         return 'Email inviata con successo!';
//     } catch (\Exception $e) {
//         return 'Errore nell\'invio dell\'email: '.$e->getMessage();
//     }
// });

Route::get('/{any}', function () {
    return file_get_contents(public_path('app/index.html'));
})->where('any', '^(?!api|assets|css|js).*$');

// require __DIR__ . '/auth.php';
