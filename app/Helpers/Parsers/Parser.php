<?php

namespace App\Helpers\Parsers;

use Carbon\Carbon;

abstract class Parser {
    /**
     * parseStr - парсит строку на основе разделителя '|'
     *
     * @param string $str
     * @return array
     */
    public static function parseStr(string $str): array {
        if (empty($str)) {
            return ['', ''];
        }

        return strpos($str, '|') === false ? [$str, ''] : explode('|', $str);
    }

    /**
     * returnParts - проверяет и возвращает наименования сервиса и тип инцидента
     *
     * @param array $data
     * @return array
     */
    public static function returnParts(array $data): array {
        if (!isset($data['service']) || !isset($data['incident'])) {
            return [
                'success' => false,
                'data' => $data,
                'message' => 'Отсутствуют необходимые данные'
            ];
        }

        [$data['service'], $data['incident']['type']] = static::parseStr($data['service']);
        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * parseDates - парсим даты
     *
     * @param string $prevDate
     * @param string $currentDate
     * @return array{currentDate: Carbon, prevDate: Carbon}
     */
    public static function parseDates(string $prevDate, string $currentDate): array {
        $prevDate = Carbon::parse($prevDate)->startOfDay();
        $currentDate = Carbon::parse($currentDate)->startOfDay();

        return [
            'prevDate' => $prevDate,
            'currentDate' => $currentDate
        ];
    }

    /**
     * parse - парсит сообщение
     *
     * @param array $message
     *
     * @return array
     */
    abstract public function parse(array $message): array;

    /**
     * parseError - парсит сообщение ошибки
     *
     * @param array $message
     *
     * @return array
     */
    abstract protected function parseError(array $message): array;

}
