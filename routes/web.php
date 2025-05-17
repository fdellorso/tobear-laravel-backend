<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExecuteArtisanCommandController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/phpmyinfo', function () {
    phpinfo();
})->name('phpmyinfo');

Route::get('/artisan/{name_of_command}', ExecuteArtisanCommandController::class);

require __DIR__ . '/auth.php';
