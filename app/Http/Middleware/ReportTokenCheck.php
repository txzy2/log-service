<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class ReportTokenCheck extends Controller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validate = Validator::make(
            $request->all(),
            [
                'token' => 'required|string',
                'date' => 'required|date_format:d-m-Y H:i:s'
            ],
            [
                '*.required' => 'Поле :attribute обязательно для заполнения',
                'date.date_format' => 'Неверный формат даты',
            ]
        );

        if ($validate->fails()) {
            return $this->sendError($validate->errors(), Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->all();
        $sign = hash('sha256', config("app.report_token") . $data['date'] . config("app.report_token"), false);
        \Illuminate\Support\Facades\Log::channel("tokens")->info("REPORT CHECK TOKEN SIGH", [$sign]);
        $res = $sign == $data['token'];

        if (!$res) {
            return $this->sendError("Неверный токен", Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
