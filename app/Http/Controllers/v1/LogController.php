<?php

namespace App\Http\Controllers\v1;

use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    /**
     * sendLog - главный контроллер логов, который распределяет запросы по сервисам
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendLog(Request $request)
    {
        // получаем данные и удаляем токен
        $data = parent::unsetToken($request->all());
        // парсим $data['service'] => [$service, $type]
        $parcedData = ServiceManager::returnParts($data);
        Log::channel("debug")->info('\LogController::sendLog REQUEST', $parcedData);

        $serviceObject = ServiceManager::initServiceObject($parcedData['data']['service']);
        $return = $serviceObject->logging($parcedData['data']);

        return $this->sendResponse($return);
    }

}