<?php

namespace App\Http\Controllers\v1;

use App\Actions\LogIncident;
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
                'incident.date' => 'required|date_format:Y-m-d|after_or_equal:today',
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

        return $this->sendSuccess(LogIncident::writeOrSaveLog($data)['message']);
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
        return $this->sendSuccess($return['message'], $return['data'], $return['success']);
    }
}
