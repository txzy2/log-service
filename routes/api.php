<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Middleware\ServiceCheck;
use App\Http\Middleware\TokenCheck;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestConroller::class, 'test']);

Route::prefix('v1')->group(function () {
    Route::prefix('log')->middleware(ServiceCheck::class)->group(function () {
        Route::post('/', [LogController::class, 'addLog'])->middleware(TokenCheck::class);
    });

    Route::get('/report', [ReportController::class, 'report'])->middleware(TokenCheck::class);
});

Route::fallback(fn () => response()->json([
    'success' => false,
    'message' => 'Not found',
], 404));
