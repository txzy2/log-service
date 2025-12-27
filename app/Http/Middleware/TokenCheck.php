<?php
namespace App\Http\Middleware;

use App\Traits\RespondsWithMessages;
use App\Values\SignaturePayload;
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
        if (abs(time() - $payload->timestamp) > self::TOKEN_TTL_SECONDS) {
            throw new \Exception('The token has expired');
        }

        $expected = hash_hmac(
            'sha256',
            $payload->method . $payload->path . $payload->timestamp . $payload->content,
            config('app.services_token')
        );

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
            'auth' => $request->header('Authorization'),
        ];

        Log::channel("debug")->info("user data", [
            "userData"=> $userData,
            "request"=> $request,
        ]);

        try {
            $payload = SignaturePayload::fromRequest($request);
            $this->checkSignature($payload);
        } catch (\InvalidArgumentException $e) {
            $error = $e->getMessage();
            Log::channel('tokens')->error(self::ERROR_CLASS . "::handle Invalid payload: $error", $userData);
            return $this->sendError('Invalid payload: ' . $e->getMessage(), 400);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::channel('tokens')->error(self::ERROR_CLASS . "::handle Exception: $error", $userData);
            return $this->sendError($error, 401);
        } 

        Log::channel('tokens')->info(self::ERROR_CLASS . '::handle USER IS AUTH', $userData);
        return $next($request);
    }
}
