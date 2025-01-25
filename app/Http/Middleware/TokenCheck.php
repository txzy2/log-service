<?php

namespace App\Http\Middleware;

use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class TokenCheck extends Controller
{
    private const ERROR_MESSAGE = "TokenCheck::sendResult TOKEN ERROR";

    /**
     * handle - обрабатывает токен для записи лога
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
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
                'incident.date' => 'required|date_format:d-m-Y H:i:s',
                'incident.message' => 'required|string',
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
            Log::channel("tokens")->info(self::ERROR_MESSAGE . " ({$data['service']})", $userData);
            return response()->json(['success' => false, "message" => "Ошибка парсинга сервиса. Передан неверный сервис"], 400);
        }

        $checkResult = $this->checkToken($parcedData['data']['service'], $data);

        if (!$checkResult) {
            Log::channel("tokens")->info(self::ERROR_MESSAGE . " ({$data['service']})", $userData);
            return response()->json(['success' => false, "message" => "Неверно указан сервис"], 400);
        }

        Log::channel('tokens')->info("USER IS AUTHORIZED", $userData);
        return $next($request);
    }

    /**
     * Проверяет токен для указанного сервиса
     * 
     * @param string $service
     * @param array $data
     * @return bool
     */
    private function checkToken(string $service, array $data): bool
    {
        return match ($service) {
            'WSPG' => $this->checkTokenForWsPg($data),
            'ADS' => false, // TODO: Реализовать проверку для ADS
            default => false,
        };
    }

    /**
     * Проверка токена для платежного шлюза
     * 
     * @param array $data
     * @return bool
     */
    private function checkTokenForWsPg(array $data): bool
    {
        $incident = $data["incident"];
        $sign = hash('sha256', $incident["object"] . $incident["date"] . config("app.key"), false);

        return $sign === $data["token"];
    }

}