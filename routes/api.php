<?php

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\IncidentController;
use App\Http\Controllers\v1\LogController;
use App\Http\Controllers\v1\ServicesController;
use App\Http\Middleware\ServicesTokenCheck;
use App\Http\Middleware\ValidateService;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestConroller::class, 'test']);

Route::prefix('v1')->middleware(ServicesTokenCheck::class)->group(function () {
    // Работа с логами
    Route::prefix('log')->group(function () {

        /*
         * ================================================================
         * NOTE: Старый вариант, обсудить с Сергеем или с Леонидом
         * ================================================================
         *
         * Route::post('/', [LogController::class, 'sendLog'])->middleware(TokenCheck::class);
         *
         * Route::post('/report', [LogController::class, 'sendReport'])->middleware(ReportTokenCheck::class);
         * Route::post('/export', [LogController::class, 'exportLogs'])->middleware(ServicesTokenCheck::class);
         *
         */

        Route::post('/', [LogController::class, 'addLog']);
        Route::post('/report', [LogController::class, 'sendReport']);
        Route::post('/export', [LogController::class, 'exportLogs']);
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
