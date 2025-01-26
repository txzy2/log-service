<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * sendResponse - отправляет успешный ответ 
     * 
     * @param mixed $message
     * @param bool $result
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendResponse($message = "success", array $data = [], bool $result = true): JsonResponse
    {
        $response = [
            'success' => $result,
            'message' => $message,
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, 200);
    }

    /**
     * sendError - отправляет ошибку
     * 
     * @param mixed $error
     * @param mixed $code
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendError($error, $code = 404)
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
     * @param mixed $data
     */
    protected function unsetToken($data)
    {
        unset($data['token']);
        return $data;
    }
}
