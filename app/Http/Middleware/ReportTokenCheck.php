<?php

namespace App\Http\Middleware;

use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use App\Models\Services;
use App\Models\Incident;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class ReportTokenCheck extends Controller
{
    private const ERROR_MESSAGE = "<b>MODULE ERROR: <i>ReportTokenCheck::class</i></b>";

    /**
     * handle - проверяет токен для формирования отчета
     * 
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
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
                'service' => 'nullable|string',
                'date' => 'nullable|date_format:Y-m-d'
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
                'date.date_format' => 'Неверный формат даты',
            ]
        );

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->messages(), Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->all();

        $existService = Services::where('name', $data['service'])->first();
        if (!$existService || $existService->active === "N") {
            ServiceManager::telegramSendMessage(self::ERROR_MESSAGE . "\n\nВведен неверный сервис или сервис не активен" . " ({$data['service']})");
            return $this->sendError("Введен неверный сервис или сервис не активен", Response::HTTP_UNAUTHORIZED);
        }

        $sign = hash('sha256', config("app.report_token") . $data['service'] . config("app.report_token"), false);
        Log::channel("tokens")->info("REPORT CHECK TOKEN SIGH", [$sign]);
        $res = $sign == $data['token'];

        if (!$res) {
            Log::channel("tokens")->info(self::ERROR_MESSAGE . " ({$data['service']})", $userData);
            return $this->sendError("Неверный токен", Response::HTTP_UNAUTHORIZED);
        }

        Log::channel("tokens")->info("REPORT TOKEN IS VALID, USER IS AUTHORIZED", $userData);
        return $next($request);
    }
}
