<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    abort(404);
});

Route::get('/docs', function () {
    return view('redoc');
});

Route::fallback(function () {
    abort(404);
});
