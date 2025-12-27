<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Middleware\ServcieCheck;
use App\Http\Middleware\TokenCheck;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestConroller::class, 'test']);

// TODO: Раскомментировать после разработки
Route::prefix('v1')->group(function () {
    // Работа с логами
    Route::prefix('log')->middleware(ServcieCheck::class)->group(function () {
        Route::post('/', [LogController::class, 'addLog'])->middleware(TokenCheck::class);
        Route::post('/test', [LogController::class, 'testAddLog']);
    });

    Route::get('/report', [ReportController::class, 'send'])->middleware(TokenCheck::class);
});

Route::fallback(fn () => response()->json([
    'success' => false,
    'message' => 'Not found',
], 404));
