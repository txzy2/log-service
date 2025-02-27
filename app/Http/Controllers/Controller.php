<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * sendResponse - отправляет успешный ответ
     *
     * @param string $message
     * @param bool $result
     * @return JsonResponse
     */
    public function sendResponse(string $message = "", array $data = [], bool $result = true): JsonResponse
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

        return response()->json($response, 200);
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
