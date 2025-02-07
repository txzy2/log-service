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
        $data = parent::unsetToken($request->all());
        $parcedData = ServiceManager::returnParts($data);
        Log::channel("debug")->info('\LogController::sendLog REQUEST', $parcedData);

        $serviceObject = ServiceManager::initServiceObject($parcedData['data']['service']);
        $return = $serviceObject->logging($parcedData['data']);

        Log::channel("debug")->info('\LogController::sendLog RESULT SAVING', $return);

        return $this->sendResponse($return['message']);
    }

    /**
     * sendReport - контроллер для формирования отчетов по логам
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendReport(Request $request)
    {
        $data = parent::unsetToken($request->all());
        $parcedData = ServiceManager::returnParts($data);

        $serviceObject = ServiceManager::initServiceObject($parcedData['data']['service']);
        $return = $serviceObject->report($parcedData['data']);

        if (!$return['success']) {
            return $this->sendResponse('Данные не найдены');
        }

        return $this->sendResponse('SUCCESS', $return['message']);
    }
}
