<?php

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Middleware\TokenCheck;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestConroller::class, 'test']);

Route::group(['prefix' => 'v1', 'middleware' => [TokenCheck::class]], function () {
    Route::post('/add', [LogController::class, 'sendLog']);
});