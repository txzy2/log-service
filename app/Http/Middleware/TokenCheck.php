<?php
/*
 * =====================================
 * NOTE: UNUSED
 * =====================================
 */

namespace App\Http\Middleware;

use App\Helpers\SenderManager;
use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use App\Models\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TokenCheck extends Controller
{
    private const ERROR_CLASS = __CLASS__;

    /**
     * Проверяет валидность токена для запроса
     *
     * @param array $data Массив с данными запроса
     * @return array{success: bool, message: string} Результат проверки токена
     */
    public function handle(Request $request, \Closure $next)
    {
        $userData = [
            'ip' => $request->ip(),
            'userAgent' => $request->header('user-agent'),
            'auth' => $request->header('Authorization')
        ];

        $validate = Validator::make(
            $request->all(),
            [
                'token' => 'required|string',
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

        $data = $request->all();

        $parcedData = ServiceManager::returnParts($data);
        if (!$parcedData['success']) {
            Log::channel("tokens")->info(self::ERROR_CLASS . " ({$data['service']})", $userData);
            return $this->sendError("Ошибка парсинга сервиса. Передан неверный сервис", 400);
        }

        $checkResult = $this->tokenValidate($parcedData['data']);

        if (!$checkResult['success']) {
            Log::channel("tokens")->info(self::ERROR_CLASS . " ({$data['service']})", $userData);
            SenderManager::telegramSendMessage(
                self::ERROR_CLASS,
                "\n{$checkResult['message']}: <code>{$data['service']}</code>"
            );
            return $this->sendResponse($checkResult['message'], [], false);
        }

        Log::channel('tokens')->info("USER IS AUTHORIZED", $userData);
        return $next($request);
    }

    /**
     * Проверяет токен для указанного сервиса
     *
     * @param array $data Массив с данными сервиса
     * @return array{success: bool, message: string}
     */
    private function tokenValidate(array $data): array
    {
        $return = [
            'success' => false,
            'message' => ""
        ];

        $service = $data['service'];

        $existService = Services::where('name', $service)->first();
        if (!$existService) {
            $return['message'] = "Введен неверный сервис";
            return $return;
        }

        if ($existService->active === "N") {
            Log::channel("tokens")->info(self::ERROR_CLASS . "SERVICE IS INACTIVE" . " ({$service})", $data);
            $return['message'] = "Сервис не активен";
            return $return;
        }

        $serviceObject = ServiceManager::initServiceObject($existService->name);
        $result = $serviceObject->validateToken($data);

        $return = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        return $return;
    }
}
