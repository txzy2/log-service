<?php

namespace App\Helpers\Parsers;

use Carbon\Carbon;

class Parser
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
     * parceDates - парсим даты
     *
     * @param string $prevDate
     * @param string $currentDate
     * @return array{currentDate: Carbon, prevDate: Carbon}
     */
    public static function parceDates(string $prevDate, string $currentDate): array
    {
        $prevDate = Carbon::parse($prevDate)->startOfDay();
        $currentDate = Carbon::parse($currentDate)->startOfDay();

        return [
            'prevDate' => $prevDate,
            'currentDate' => $currentDate
        ];
    }
}
