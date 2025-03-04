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
     * validateSignature - валидация сигнатуры
     *
     * @param int $timestamp
     * @param string $signature
     * @param object $request
     * @throws \Exception
     * @return bool
     */
    private function validateSignature(int $timestamp, string $signature, object $request): bool
    {
        Log::channel('tokens')->info('SYSTEM TIMESTAMP', [time()]);
        if (abs(time() - $timestamp) > 600) {
            throw new \Exception('Истек срок действия токена');
        }

        $sign = hash_hmac(
            'sha256',
            $request->method() . $request->path() . $timestamp . $request->getContent(),
            config('app.services_token')
        );
        Log::channel('tokens')->info('SYSTEM SIGN', [$sign]);
        if (!hash_equals($sign, $signature)) {
            throw new \Exception('Неверная подпись запроса');
        }

        return true;
    }

    /**
     * handle - главный метод проверки сигнатуры
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $validated = Validator::make($request->headers->all(), [
            'x-timestamp' => 'required',
            'x-signature' => 'required',
        ], [
            '*.required' => 'Заголовок :attribute обязателен для запроса'
        ]);

        if ($validated->fails()) {
            return $this->sendError($validated->errors()->first(), 401);
        }

        try {
            $this->validateSignature(
                (int) $request->header('X-Timestamp'),
                $request->header('X-Signature'),
                $request
            );
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }

        return $next($request);
    }
}
