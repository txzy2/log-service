<?php

namespace App\Http\Controllers\v1;

use App\Actions\LogIncident;
use App\Enums\ErrorsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLogRequest;
use App\Jobs\WriteIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogController extends Controller {
    /**
     * addLog - главный контроллер логов, который распределяет запросы по сервисам
     *
     * @param StoreLogRequest $request
     * @return JsonResponse
     */
    public function addLog(StoreLogRequest $request): JsonResponse {
        Log::channel('debug')->info(static::getControllerClass() . ':addLog RAW REQUEST', [$request->validated()]);

        WriteIncident::dispatch($request->toIncidentData())->onQueue('writeIncidentLog');
        return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
    }

    /**
     * testAddLog - тестовый контроллер для логов (Идет не через очередь)
     *
     * @param StoreLogRequest $request
     * @return JsonResponse
     */
    public function testAddLog(StoreLogRequest $request): JsonResponse {
        Log::channel('debug')->info(static::getControllerClass() . ':testAddLog RAW REQUEST', [$request->validated()]);

        try {
            $result = LogIncident::writeOrSaveLog($request->toIncidentData());
            if (!$result['success']) {
                return $this->sendError($result['message'], ErrorsEnum::BAD_REQUEST->value);
            }
            return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
        } catch (Throwable $e) {
            Log::channel('debug')->error(static::getControllerClass() . '::testAddLog Controller EXCEPTION', [$e->getMessage()]);
            return $this->sendError($e->getMessage(), ErrorsEnum::INTERNAL_ERROR->value);
        }
    }
}
