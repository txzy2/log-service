<?php

namespace App\Helpers\Parsers;

class Moneta
{
    /**
     * parse - парсит сообщение
     *
     * @param array $message
     * @return array{success: bool, message: string}
     */
    public function parse(array $message): array
    {
        $result = [
            'success' => false,
            'message' => ""
        ];

        return match (true) {
            isset($message['error']) => $this->parseError($message['error']),
            default => $result,
        };
    }

    /**
     * parceError - парсит ошибку
     *
     * @param array $message
     * @return array{success: bool, message: string}
     */
    private function parseError(array $message): array
    {
        $default = [
            'success' => false,
            'message' => '',
            'code' => ''
        ];
        $parse = $message['Envelope']['Body']['fault'] ?? null;

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
