<?php

namespace App\Http\Controllers\v1;

use App\Actions\LogIncident;
use App\Enums\ErrorsEnum;
use App\Enums\LevelsEnum;
use App\Http\Controllers\Controller;
use App\Jobs\WriteIncident;
use App\Models\Incident;
use App\Values\IncidentData;
use App\Values\SendReportFilterData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

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
            Log::channel("debug")->warning(static::getControllerClass() . "::addLog VALIDATION ERROR", $validate->errors()->all());
            return $this->sendError($validate->errors()->first(), ErrorsEnum::VALIDATION_ERROR->value);
        }

        WriteIncident::dispatch(IncidentData::fromArray($data))->onQueue("writeIncidentLog");
        return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
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
        Log::channel("debug")->info(static::getControllerClass() . '::sendReport REQUEST', $data);
        $validate = Validator::make(
            $data,
            [
                'service' => 'nullable|string',
                "code" => "nullable|string",
                'date' => 'nullable|string',
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
            ]
        );

        if ($validate->fails()) {
            Log::channel("debug")->warning(static::getControllerClass() . "::sendReport VALIDATION ERROR", $validate->errors()->all());
            return $this->sendError($validate->errors(), 400);
        }

        $return = Incident::getIncidentDataByParams(SendReportFilterData::fromArray($data));
        return match ($return['success']) {
            true => $this->sendSuccess($return['message'], $return['data']),
            default => $this->sendError($return['message'], 400),
        };
    }

    /**
     * testAddLog - тестовый контроллер для логов (Идет не через очередь)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testAddLog(Request $request): JsonResponse
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
            Log::channel("debug")->warning(static::getControllerClass() . "::testAddLog VALIDATION ERROR", $validate->errors()->all());
            return $this->sendError($validate->errors()->first(), ErrorsEnum::VALIDATION_ERROR->value);
        }

        try {
            $result = LogIncident::writeOrSaveLog(IncidentData::fromArray($data));
            if(!$result["success"]) {
                return $this->sendError($result["message"], ErrorsEnum::BAD_REQUEST->value);
            }
            return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
        } catch (Throwable $e) {
            Log::channel("debug")->info($this->getControllerClass() . "::testAddLog Controller EXCEPTUON", [$e->getMessage()]);
            return $this->sendError($e->getMessage(), ErrorsEnum::INTERNAL_ERROR->value);
        }
    }
}
