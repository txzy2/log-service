<?php

namespace App\Http\Controllers\v1;

use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    /**
     * sendLog - главный контроллер логов, который распределяет запросы по сервисам
     *
     * @param Request $request
     * @return mixed|JsonResponse
     */
    public function sendLog(Request $request): JsonResponse
    {
        $data = parent::unsetToken($request->all());
        $parsedData = ServiceManager::returnParts($data);
        Log::channel("debug")->info('\LogController::sendLog REQUEST', $parsedData);

        $serviceObject = ServiceManager::initServiceObject($parsedData['data']['service']);
        $return = $serviceObject->logging($parsedData['data']);

        Log::channel("debug")->info('\LogController::sendLog RESULT SAVING', $return);

        return $this->sendResponse($return['message']);
    }

    /**
     * sendReport - контроллер для формирования отчетов по логам
     *
     * @param Request $request
     * @return mixed|JsonResponse
     */
    public function sendReport(Request $request): JsonResponse
    {
        $data = parent::unsetToken($request->all());
        Log::channel("debug")->info('\LogController::sendReport REQUEST', $data);

        if (array_key_exists('date', $data)) {
            $checkWithDate = Incident::where('service', $data['service'])->where('date', $data['date'])->get()->toArray();
            Log::channel("debug")->info('\LogController::sendReport RESULT BY DATE', $checkWithDate);
            return $this->sendResponse($checkWithDate ?: 'За этот день нет данных');
        }

        $checkWithoutDate = Incident::where('service', $data['service'])->get()->toArray();
        return $this->sendResponse("", $checkWithoutDate);
    }

    /**
     * exportLogs - контроллер для экспорта логов
     *
     * @param Request $request
     * @return mixed|JsonResponse
     */
    public function exportLogs(Request $request): mixed
    {
        $data = $request->all();
        $validator = Validator::make(
            $data,
            [
                'date' => 'nullable|date_format:Y-m-d',
                'service' => 'nullable|string',
            ],
            [
                'date.date_format' => 'Неверный формат даты',
                'service.string' => 'Сервис должен быть строкой',
            ]
        );

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }

        return Incident::exportLogs($data);
    }
}
