<?php

namespace App\Http\Middleware;

use App\DTO\SignaturePayload;
use App\Traits\RespondsWithMessages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TokenCheck
{
    use RespondsWithMessages;

    private const ERROR_CLASS = __CLASS__;
    private const TOKEN_TTL_SECONDS = 250;

    /**
     * Проверяет подпись запроса
     *
     * @param SignaturePayload $payload
     * @return void
     * @throws \Exception
     */
    private function checkSignature(SignaturePayload $payload): void
    {
        Log::channel('tokens')->info('SYSTEM TIMESTAMP', [time()]);

        if (abs(time() - $payload->timestamp) > self::TOKEN_TTL_SECONDS) {
            throw new \Exception('The token has expired');
        }

        $expected = hash_hmac(
            'sha256',
            $payload->method . $payload->path . $payload->timestamp . $payload->content,
            config('app.services_token')
        );

        Log::channel('tokens')->info('SYSTEM SIGN', [$expected]);

        if (!hash_equals($expected, $payload->signature)) {
            throw new \Exception('Invalid request signature');
        }
    }

    /**
     * handle — главный метод мидлвари
     *
     * @param Request $request
     * @param Closure $next
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
            $payload = new SignaturePayload(
                (int) $request->header('X-Timestamp'),
                $request->header('X-Signature'),
                $request->method(),
                $request->path(),
                $request->getContent()
            );

            $this->checkSignature($payload);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::channel('tokens')->error(self::ERROR_CLASS . "::handle ERROR TO AUTH $error", $userData);
            return $this->sendError($error, 401);
        }

        Log::channel('tokens')->info(self::ERROR_CLASS . '::handle USER IS AUTH', $userData);
        return $next($request);
    }
}
