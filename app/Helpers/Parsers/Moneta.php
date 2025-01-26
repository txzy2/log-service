<?php

namespace App\Helpers\Parsers;

class Moneta
{
    public function parse(array $message): array
    {
        $result = [
            'success' => false,
            'message' => ""
        ];

        return match (true) {
            isset($message['error']) => $this->parceError($message['error']),
            default => $result,
        };
    }

    private function parceError(array $message): array
    {
        $default = [
            'success' => false,
            'message' => '',
            'code' => ''
        ];
        $parse = $message['Envelope']['Body']['fault'] ?? null;

        // Если структура не соответствует ожидаемой, возвращаем $default
        if (
            empty($parse) ||
            !isset($parse['faultstring'], $parse['detail']['faultDetail'])
        ) {
            return $default;
        }

        return [
            'success' => true,
            'message' => $parse['detail']['faultDetail'] . "|" . $parse['faultstring'],
            'code' => $parse['detail']['faultDetail']
        ];
    }
}