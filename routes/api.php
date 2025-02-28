<?php

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Controllers\v1\ServicesController;
use App\Http\Middleware\ServicesTokenCheck;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestConroller::class, 'test']);

/*
* ================================================================
* REFACTORING: Добавил новый вариант токенизации, более простой
* ================================================================
*
* x-timestamp => Временная метка (UNIX)
* x-signature => Сгенерированная сигнатура
*
*/
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

        Route::post('/report', [LogController::class, 'sendReport']);
        Route::post('/export', [LogController::class, 'exportLogs']);
    });

    // Контроллеры для работы на фронтенде
    Route::prefix('services')->group(function () {

        /*
        * ================================================================
        * Пока не понятно надо ли использовать этот роут, т.к пока что сервис трудно расширять,
        * так как надо добавлять отдельные файлы с логикой для каждого нового сервиса
        * ================================================================
        *
        * Route::post('/', [ServicesController::class, 'addService']);
        *
        */

        Route::get('/', [ServicesController::class, 'getServices']);
        Route::post('/edit', [ServicesController::class, 'editService']);
    });
});
