<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServicesTokenCheck extends Controller
{
    /**
     * Handle - проверка токена по заголовкам
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $userData = [
            'ip' => $request->ip(),
            'userAgent' => $request->header('user-agent'),
            'auth' => $request->header('Authorization')
        ];

        $validated = Validator::make($request->headers->all(), [
            'x-timestamp' => 'required',
            'x-signature' => 'required',
        ], [
            '*.required' => 'Заголовок :attribute обязателен для запроса'
        ]);

        if ($validated->fails()) {
            return $this->sendError($validated->errors()->first(), 401);
        }

        $timestamp = (int)$request->header('X-Timestamp');
        Log::channel("tokens")->info("ServicesTokenCheck::handle TIMESTAMPS", [
            'systemTime' => time()
        ]);

        // Проверяем актуальность временной метки (например, 5 минут)
        if (abs(time() - intval($timestamp)) > 300) {
            Log::channel("tokens")
                ->error("ServicesTokenCheck::handle TIMESTAMP EXPIRED", [$userData]);
            return response()->json([
                'success' => false,
                'message' => 'Истек срок действия токена'
            ], 401);
        }

        $sign = hash(
            'sha256',
            config('app.services_token') . $timestamp . config('app.services_token')
        );
        Log::channel("tokens")->info("ServicesTokenCheck::handle SIGNS", [
            'sign SYSTEM' => $sign,
        ]);

        // Проверяем подпись
        if (!hash_equals($sign, $request->header('X-Signature'))) {
            Log::channel("tokens")
                ->error("ServicesTokenCheck::handle INVALID SIGNATURE", [$userData]);
            return response()->json([
                'success' => false,
                'message' => 'Неверная подпись запроса'
            ], 401);
        }

        Log::channel("tokens")->info("ServicesTokenCheck::handle USER IS AUTH", [$userData]);
        return $next($request);
    }
}
