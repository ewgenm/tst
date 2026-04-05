<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
})->where('path', '.*');

// Catch-all для Vue Router (SPA)
Route::get('/{path}', function () {
    return view('app');
})->where('path', '.*');
