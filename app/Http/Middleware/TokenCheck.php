<?php

namespace App\Http\Middleware;

use App\Helpers\Init;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Validator;

class TokenCheck
{
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
        $parcedData = Init::returnParts($data);

        $checkResult = match ($parcedData['service']) {
            'WSPG' => $this->checkTokenForWsPg($data),
            'ADS' => false,
            default => false,
        };

        if (!$checkResult) {
            Log::channel("tokens")->info('\TokenCheck::sendResult TOKEN ERROR FOR', $userData);
            return response()->json(['success' => false, "message" => "Неверно указан сервис"]);
        }

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
