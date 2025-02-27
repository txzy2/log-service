<?php

namespace App\Http\Controllers\v1;

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
    public function sendLog(Request $request): JsonResponse
    {
        $validate = Validator::make(
            $request->all(),
            [
                'service' => 'required|string',
                'incident' => 'required|array',
                'incident.object' => 'required|string',
                'incident.date' => 'required|date_format:d-m-Y',
                'incident.message' => 'required|array',
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
            ]
        );

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        $data = parent::unsetToken($request->all());
        $parsedData = ServiceManager::returnParts($data);
        if (!$parsedData['success']) {
            Log::channel("debug")->info(self::ERROR_CLASS . " ({$data['service']})", $data);
            return $this->sendError("Ошибка парсинга сервиса. Передан неверный сервис", 400);
        }

        $data = $parsedData['data'];
        Log::channel("debug")->info('\LogController::sendLog REQUEST', $parsedData['data']);
        
        $existService = Services::validateService($data['service']);
        if (!$existService['success']) {
            return $this->sendError($existService['message'], 400);
        }

        $serviceObject = ServiceManager::initServiceObject($data['service']);
        $return = $serviceObject->logging($data);

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
        $validate = Validator::make(
            $request->all(),
            [
                'service' => 'nullable|string',
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

        $data = $request->all();
        Log::channel("debug")->info('\LogController::sendReport REQUEST', $data);

        $existService = Services::validateService($data['service']);
        if (!$existService['success']) {
            return $this->sendError($existService['message'], 400);
        }

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

        $existService = Services::validateService($data['service']);
        if (!$existService['success']) {
            return $this->sendError($existService['message'], 400);
        }

        return Incident::exportLogs($data);
    }
}
