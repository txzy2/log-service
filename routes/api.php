<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\TokenCheck;

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Middleware\ReportTokenCheck;

Route::get('/test', [TestConroller::class, 'test']);

Route::group(['prefix' => 'v1'], function () {
    Route::post('/add', [LogController::class, 'sendLog'])->middleware(TokenCheck::class);

    Route::post('/report', [LogController::class, 'sendReport'])->middleware(ReportTokenCheck::class);
});
