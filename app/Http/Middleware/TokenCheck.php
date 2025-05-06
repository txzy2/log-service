<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TokenCheck extends Controller
{
    private const ERROR_CLASS = __CLASS__;
    /**
     * validateSignature - валидация сигнатуры
     *
     * @param int $timestamp
     * @param string $signature
     * @param object $request
     * @return bool
     * @throws \Exception
     */
    private function validateSignature(int $timestamp, string $signature, object $request): bool
    {
        Log::channel('tokens')->info('SYSTEM TIMESTAMP', [time()]);
        if (abs(time() - $timestamp) > 250) {
            throw new \Exception('The token has expired');
        }

        $sign = hash_hmac(
            'sha256',
            $request->method() . $request->path() . $timestamp . $request->getContent(),
            config('app.services_token')
        );
        Log::channel('tokens')->info('SYSTEM SIGN', [$sign]);
        if (!hash_equals($sign, $signature)) {
            throw new \Exception('Invalid request signature');
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

        try {
            $this->validateSignature(
                (int) $request->header('X-Timestamp'),
                $request->header('X-Signature'),
                $request
            );
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::channel('tokens')->error(self::ERROR_CLASS . "::handle ERROR TO AUTH $error", $userData);
            return $this->sendError($error, 401);
        }

        Log::channel('tokens')->info(self::ERROR_CLASS . '::handle USER IS AUTH', $userData);
        return $next($request);
    }
}
