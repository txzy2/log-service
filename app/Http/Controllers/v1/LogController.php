<?php

namespace App\Http\Controllers\v1;

use App\Actions\LogIncident;
use App\Enums\ErrorsEnum;
use App\Enums\LevelsEnum;
use App\Http\Controllers\Controller;
use App\Jobs\WriteIncident;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LogController extends Controller
{
    /**
     * sendLog - главный контроллер логов, который распределяет запросы по сервисам
     *
     * @param Request $request
     * @return mixed|JsonResponse
     */
    public function addLog(Request $request): JsonResponse
    {
        $data = $request->all();
        Log::channel("debug")->info(static::getControllerClass() . ':addLog RAW REQUEST', [$data]);
        $validate = Validator::make(
            $data,
            [
                'level' => ['required', Rule::in(LevelsEnum::cases())],
                'service' => 'required|string',
                'message' => 'required|string',
                'domain' => 'required|string',
                'action' => 'required|string',
                'function' => 'required|string',
                'additionalFields' => 'nullable|array',
                'file' => 'required|string',
                'class' => 'required|string',
                'date' => 'required',
                'hash_sum' => 'required|string',
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
                'additionalFields.array' => 'Неверный тип для additionalFields. ожидается массив',
                'level.in' => 'Переданный статус не валиден',
            ]
        );

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->first(), ErrorsEnum::VALIDATION_ERROR->value);
        }

        WriteIncident::dispatch($data)->onQueue("writeIncidentLog");
        // $isWrited = LogIncident::writeOrSaveLog($data);
        // Log::channel('debug')->info("is log writed?", $isWrited);
        return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
    }

    /**
     * sendReport - контроллер для формирования отчетов по логам
     *
     * @param Request $request
     * @return mixed|JsonResponse
     */
    public function sendReport(Request $request): JsonResponse {
        $data = $request->all();
        Log::channel("debug")->info(static::getControllerClass() . '::sendReport REQUEST', $data);
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
            return $this->sendError($validate->errors(), 400);
        }

        $return = Incident::getIncidentDataByParams($data);
        Log::channel('debug')->info(static::getControllerClass() . '::sendReport RESULT DATA', $return['data']);
        return match ($return['success']) {
            true => $this->sendSuccess($return['message'], $return['data']),
            default => $this->sendError($return['message'], 400),
        };
    }
}
