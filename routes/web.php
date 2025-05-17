<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/phpmyinfo', function () {
    phpinfo();
})->name('phpmyinfo');

require __DIR__ . '/auth.php';
