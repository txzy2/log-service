<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function sendResponse($message = "success", bool $result = true, )
    {
        $response = [
            'success' => $result,
            'data' => [
                "message" => $message
            ]
        ];

        return response()->json($response, 200);
    }


    public function sendError($error, $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        return response()->json($response, $code);
    }

    protected function unsetToken($data)
    {
        unset($data['token']);

        return $data;
    }
}
