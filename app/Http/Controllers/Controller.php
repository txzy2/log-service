<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * sendResponse - отправляет успешный ответ
     *
     * @param string $message
     * @param array $data
     * @param bool $result
     * @return JsonResponse
     */
    public function sendResponse(string $message = "", array $data = [], bool $result = true, int $code = 200): JsonResponse
    {
        $response = [
            'success' => $result
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }

    /**
     * sendError - отправляет ошибку
     *
     * @param string $error
     * @param int $code
     * @return JsonResponse
     */
    public function sendError(string $error, int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        return response()->json($response, $code);
    }

    /**
     * unsetToken - удаляет токен
     *
     * @param array $data
     * @return array
     */
    protected function unsetToken(array $data): array
    {
        unset($data['token']);
        return $data;
    }
}
