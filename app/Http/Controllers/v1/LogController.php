<?php

namespace App\Http\Controllers\v1;

use App\Enums\ErrorsEnum;
use App\Enums\LevelsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLogRequest;
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
     * addLog - главный контроллер логов, который распределяет запросы по сервисам
     *
     * @param StoreLogRequest $request
     * @return JsonResponse
     */
    public function addLog(Request $request): JsonResponse
    {
        $data = $request->all();
        Log::channel('debug')->info(static::getControllerClass() . ':addLog RAW REQUEST', $data);
        $validate = Validator::make(
            $data,
            [
                'level'            => ['required', Rule::in(LevelsEnum::cases())],
                'service'          => 'required|string',
                'message'          => 'required|string',
                'domain'           => 'required|string',
                'action'           => 'required|string',
                'function'         => 'required|string',
                'additionalFields' => 'nullable|array',
                'file'             => 'required|string',
                'class'            => 'required|string',
                'date'             => 'required|date',
                'hash_sum'         => 'required|string',
                'demo'             => 'nullable|in:Y,N',
            ],
            [
                'required'               => 'Поле :attribute обязательно для заполнения',
                'additionalFields.array' => 'Неверный тип для additionalFields. ожидается массив',
                'level.in'               => 'Переданный статус не валиден',
            ],
        );

        if ($validate->fails()) {
            Log::channel('debug')->warning(static::getControllerClass() . '::sendReport VALIDATION ERROR', $validate->errors()->all());
            return $this->sendError($validate->errors(), 400);
        }

        if (!empty($data['demo']) && $data['demo'] === 'Y') {
            return $this->processTestRequest($data);
        } else {
            return $this->processWithQueue($data);
        }

    }

    /**
     * processTestRequest - Тестовый запрос
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processTestRequest(array $data): JsonResponse
    {
        $incident = Incident::fromArray($data);
        $result = $incident->process();

        if (!$result['success']) {
            return $this->sendError($result['message'], ErrorsEnum::BAD_REQUEST->value);
        }

        return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
    }

    /**
     * processWithQueue - Боевой запрос
     *
     * @param array $data
     * @return JsonResponse
     */
    private function processWithQueue(array $data): JsonResponse
    {
        WriteIncident::dispatch($data)->onQueue('writeIncidentLog');
        return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
    }
}
