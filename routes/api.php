<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\TokenCheck;
use App\Http\Middleware\ServicesTokenCheck;

use App\Http\Controllers\TestConroller;
use App\Http\Controllers\v1\LogController;
use App\Http\Controllers\v1\ServicesController;
use App\Http\Middleware\ReportTokenCheck;

Route::get('/test', [TestConroller::class, 'test']);

Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'log'], function () {
        Route::post('/', [LogController::class, 'sendLog'])->middleware(TokenCheck::class); // Отправка лога 

        Route::post('/report', [LogController::class, 'sendReport'])->middleware(ReportTokenCheck::class); // Отправка отчета
    });

    Route::group(['prefix' => 'services', 'middleware' => ServicesTokenCheck::class], function () {
        Route::post('/', [ServicesController::class, 'addService']); // Добавление сервиса
        Route::get('/', [ServicesController::class, 'getServices']); // Получение всех доступных сервисов
    });
});


//TODO: 
// 2. Сделать роут для добавление в базу типа ошибки 