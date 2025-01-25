<?php

namespace App\Http\Middleware;

use App\Helpers\ServiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class TokenCheck
{
    private const LOG_MESSAGE = 'TokenCheck::sendResult TOKEN ERROR FOR';

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
                'incident.date' => 'required|date_format:d-m-Y',
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

        // Проверим что service имеет вид "service|type"
        $parcedData = ServiceManager::returnParts($data);
        if (!$parcedData['success']) {
            Log::channel("tokens")->info(self::LOG_MESSAGE, $userData);
            return response()->json(['success' => false, "message" => "Неверно указан сервис"]);
        }

        // Вызываем соответствующий метод проверки токена
        $checkResult = match ($parcedData['data']['service']) {
            'WSPG' => $this->checkTokenForWsPg($data),
            'ADS' => false,
            default => false,
        };

        if (!$checkResult) {
            Log::channel("tokens")->info(self::LOG_MESSAGE, $userData);
            return response()->json(['success' => false, "message" => "Неверный токен"]);
        }

        // Все ок, го некст
        Log::channel("tokens")->info('TokenCheck::sendResult USER IS AUTHORIZED', $userData);
        return $next($request);
    }

    /**
     * checkTokenForWsPg - проверка токена для платежного шлюза
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
