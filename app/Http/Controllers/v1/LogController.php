<?php

namespace App\Http\Controllers\v1;

use App\Actions\Logging\Logging;
use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Services;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    private const ERROR_CLASS = __CLASS__;

    /**
     * sendLog - главный контроллер логов, который распределяет запросы по сервисам
     *
     * @param Request $request
     * @return mixed|JsonResponse
     */
    public function addLog(Request $request): JsonResponse
    {
        $data = $request->all();
        Log::channel("debug")->info(self::ERROR_CLASS . ':addLog RAW REQUEST', [$data]);
        $validate = Validator::make(
            $data,
            [
                'service' => 'required|string',
                'incident' => 'required',
                'incident.object' => 'required|string',
                'incident.date' => 'required|date_format:d-m-Y|after_or_equal:today',
                'incident.message' => 'required|array',
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
                'incident.date.after_or_equal' => 'Переданная дата не может быть меньше текущей даты',
            ]
        );

        if ($validate->fails()) {
            return $this->sendError($validate->errors(), 400);
        }

        $parsedData = ServiceManager::prepareRequestData($data);
        if (isset($parsedData['error'])) {
            return $this->sendError($parsedData['error'], 400);
        }
        Log::channel("debug")->info(self::ERROR_CLASS . ':addLog PARSED REQUEST', [$parsedData]);

        // $serviceObject = ServiceManager::initServiceObject($parsedData['service']);
        // $return = $serviceObject->logging($parsedData);
        $return = Logging::writeOrSaveLog($parsedData);

        Log::channel("debug")->info(self::ERROR_CLASS . '::addLog RESULT SAVING', $return);
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
        $data = $request->all();
        Log::channel("debug")->info(self::ERROR_CLASS . '::sendReport REQUEST', $data);
        $validate = Validator::make(
            $data,
            [
                'service' => 'required|string',
                'source' => "nullable|string",
                "code" => "nullable|string",
                'date' => 'nullable|date_format:Y-m-d'
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
                'date.date_format' => 'Неверный формат даты',
            ]
        );

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        $return = Incident::getIncidentDataByParams($data);
        Log::channel('debug')->info(self::ERROR_CLASS . '::sendReport RESULT DATA', $return['data']);
        return $this->sendResponse($return['message'], $return['data'], $return['success']);
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
        Log::channel('debug')->info(self::ERROR_CLASS . '::exportLogs REQUEST', $data);
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

        $existService = Services::validateService($data['service']);
        if (!$existService['success']) {
            return $this->sendError($existService['message'], 200);
        }

        return Incident::exportLogs($data);
    }
}
