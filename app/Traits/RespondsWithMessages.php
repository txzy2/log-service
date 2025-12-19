<?php

namespace App\Traits;

use App\Enums\ErrorsEnum;
use Illuminate\Http\JsonResponse;

trait RespondsWithMessages
{
    /**
     * Формирует и возвращает JSON-ответ с ошибкой.
     *
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function sendError(?string $message = null, int $code = ErrorsEnum::BAD_REQUEST->value): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? ErrorsEnum::getMessageByCode($code),
        ], $code);
    }

    /**
     * Формирует и возвращает JSON-ответ с успешным сообщением.
     *
     * @param string $message
     * @param mixed $data
     * @param int $code
     * @return JsonResponse
     */
    protected function sendSuccess(string $message, array $data = [], int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (! empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }
}
