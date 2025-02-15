<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\TokenCheck;
use App\Http\Middleware\ServicesTokenCheck;

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Controllers\v1\ServicesController;
use App\Http\Middleware\ReportTokenCheck;

Route::get('/test', [TestConroller::class, 'test']);

Route::prefix('v1')->group(function () {
    Route::prefix('log')->group(function () {
        Route::post('/', [LogController::class, 'sendLog'])->middleware(TokenCheck::class); // Отправка лога 

        Route::post('/report', [LogController::class, 'sendReport'])->middleware(ReportTokenCheck::class); // Отправка отчета
    });

    Route::prefix('services')->middleware(ServicesTokenCheck::class)->group(function () {

        /*
        \ ================================================================
        \   Пока не понятно надо ли изспользовать этот роут, т.к пока что сервис трудно расширять,
        \   так как надо добавлять отдельные файлы с логикой для каждого нового сервиса
        \   ================================================================
        \
        \   Route::post('/', [ServicesController::class, 'addService']); 
        \ 
        */

        Route::get('/', [ServicesController::class, 'getServices']); 
        Route::post('/edit', [ServicesController::class, 'editService']);
    });
});
