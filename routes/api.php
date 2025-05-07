<?php

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\IncidentController;
use App\Http\Controllers\v1\LogController;
use App\Http\Controllers\v1\ServicesController;
use App\Http\Middleware\TokenCheck;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestConroller::class, 'test']);

Route::prefix('v1')->middleware(TokenCheck::class)->group(function () {
    // Работа с логами
    Route::prefix('log')->group(function () {
        Route::post('/', [LogController::class, 'addLog']);
        Route::post('/report', [LogController::class, 'sendReport']);
    });

    // Работа с настройками инстдентов
    Route::prefix('incidents')->group(function () {

        Route::prefix('types')->group(function () {
            Route::post('/add', [IncidentController::class, 'addType']);
            // TODO: Сделать /edit
        });

        Route::prefix('services')->group(function () {
            Route::get('/', [ServicesController::class, 'getServices']);
            Route::post('/edit', [ServicesController::class, 'editService']);
            Route::post('/delete', [ServicesController::class, 'deleteService']);
        });
    });
});
