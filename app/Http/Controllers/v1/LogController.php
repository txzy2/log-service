<?php

namespace App\Http\Controllers\v1;

use App\Enums\ErrorsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLogRequest;
use App\Jobs\WriteIncident;
use App\Models\Incident;
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

        if(isset($request->demo) && !empty($request->demo) && $request->demo === 'Y') {
            return $this->proccessTestRequest($request);
        } else {
            return $this->proccessWithQueue($request);
        }
   
    }

    /**
     * proccessWithQueue - Боевой запрос
     *
     * @param StoreLogRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function proccessWithQueue(StoreLogRequest $request): JsonResponse {
        WriteIncident::dispatch($request->validated())->onQueue('writeIncidentLog');
        return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
    }

    /**
     * proccessTestRequest - Тестовый запрос
     *
     * @param StoreLogRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function proccessTestRequest(StoreLogRequest $request): JsonResponse {
        $incident = Incident::fromArray($request->validated());
        $result = $incident->process();

        if (!$result['success']) {
            return $this->sendError($result['message'], ErrorsEnum::BAD_REQUEST->value);
        }

        return $this->sendSuccess(ErrorsEnum::SUCCESS->getMessage());
    }
}
