<?php

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Controllers\v1\ServicesController;
use App\Http\Middleware\ServicesTokenCheck;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestConroller::class, 'test']);

Route::prefix('v1')->middleware(ServicesTokenCheck::class)->group(function () {
    // Работа с логами
    Route::prefix('log')->group(function () {

        /*
        * ================================================================
        * Старый вариант, обсудить с Сергеем или с Леонидом
        * ================================================================
        *
        * Route::post('/', [LogController::class, 'sendLog'])->middleware(TokenCheck::class);
        *
        * Route::post('/report', [LogController::class, 'sendReport'])->middleware(ReportTokenCheck::class);
        * Route::post('/export', [LogController::class, 'exportLogs'])->middleware(ServicesTokenCheck::class);
        *
       */

        Route::post('/', [LogController::class, 'sendLog']);

        /*
        * ================================================================
        * TODO: Идеи для /report
        * ================================================================
        *
        * ВЫБОРКИ:
        * 1. Сортировка по "Источнику"
        * 2. Поиск по "Объект инцидента"
        * 3. Поиск по коду ошибки
        * 4. Сортировака по дате
        *
       */
        Route::post('/report', [LogController::class, 'sendReport']);
        Route::post('/export', [LogController::class, 'exportLogs']);
    });

    // Контроллеры для работы на фронтенде
    Route::prefix('services')->group(function () {
        Route::get('/', [ServicesController::class, 'getServices']);
        Route::post('/edit', [ServicesController::class, 'editService']);
        Route::post('/delete', [ServicesController::class, 'deleteService']);
    });
});
