<?php

namespace App\Helpers\Parsers;

use Carbon\Carbon;

abstract class Parser
{
    /**
     * parseStr - парсит строку на основе разделителя '|'
     *
     * @param string $str
     * @return array
     */
    public static function parseStr(string $str): array
    {
        if (empty($str)) {
            return ['', ''];
        }

        return strpos($str, '|') === false ? [$str, ''] : explode('|', $str);
    }

    /**
     * parseDates - парсим даты
     *
     * @param string $prevDate
     * @param string $currentDate
     * @return array{currentDate: Carbon, prevDate: Carbon}
     */
    public static function parseDates(string $prevDate, string $currentDate): array
    {
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
