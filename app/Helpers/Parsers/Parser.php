<?php

namespace App\Helpers\Parsers;

use Carbon\Carbon;

class Parser
{
    /**
     * parceStr - парсим строку с "|" в массив
     * 
     * @param string $str
     * @return bool|string[]
     */
    public static function parceStr(string $str): array
    {
        if (strpos($str, '|') === false) {
            return [$str, ''];
        }

        return explode('|', $str);
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
