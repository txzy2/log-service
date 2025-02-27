<?php

namespace App\Helpers\Parsers;

class VSK
{
    public function parse($message): array
    {
        $result = [
            'success' => false,
            'message' => ""
        ];

        return match (true) {
            isset($message['details']) => $this->parseError($message),
            default => $result,
        };
    }

    private function parseError($message): array
    {
        $default = [
            'success' => false,
            'message' => '',
            'code' => ''
        ];

        if (!is_array($message['details']) || empty($message['details'])) {
            return $default;
        }

        $details = $message['details'][0];
        if (!isset($details['metadata']['description']) || !isset($message['code'])) {
            return $default;
        }

        return [
            'success' => true,
            'message' => $message['code'] . "|" . $details['metadata']['description'],
            'code' => $message['code']
        ];
    }
}
