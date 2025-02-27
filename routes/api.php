<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\TokenCheck;
use App\Http\Middleware\ServicesTokenCheck;

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Controllers\v1\ServicesController;
use App\Http\Middleware\ReportTokenCheck;

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
    Route::prefix('log')->group(function () {

        /*
        * ================================================================
        * Старый вариант, обсудить с Сергеем
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
