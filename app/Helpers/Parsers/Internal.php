<?php

namespace App\Helpers\Parsers;

class Internal
{
    public function parse(array $message): array
    {
        \Illuminate\Support\Facades\Log::channel('debug')->info('parse req', $message);

        if (
            empty($message) ||
            empty($message['code']) ||
            empty($message['type']) ||
            empty($message['message'])
        ) {
            return [
                'success' => false,
                'message' => "Ошибка парсинга",
                'code' => ''

            ];
        }

        return [
            'success' => true,
            'message' => $message['code'] . "|" . $message['message'],
            'code' => $message['code']
        ];
    }
}
