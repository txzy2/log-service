<?php

namespace App\Helpers;

use App\Helpers\Parsers\Parser;
use Carbon\Carbon;

class ServiceManager
{
    /**
     * initServiceObject - инициализация сервиса
     * 
     * @param string $service
     * @return object
     */
    public static function initServiceObject(string $service): object
    {
        $serviceName = "\\App\\Http\\Controllers\\DataManagers\\{$service}";
        $serviceObject = new $serviceName();

        return $serviceObject;
    }

    /**
     * getServiceParcer - получает объект парсера для указанного сервиса
     * 
     * @param string $service
     * @return object
     */
    public static function getServiceParcer(string $service): object
    {
        $serviceName = "\\App\\Helpers\\Parsers\\{$service}";
        $parcerServiceObject = new $serviceName();

        return $parcerServiceObject;
    }

    /**
     * returnParts - проверяет и возвращает части данных
     * 
     * @param array $data
     * @return array[]|array{data: array, success: bool}
     */
    public static function returnParts(array $data): array
    {
        $return = [
            'success' => false,
            'data' => $data,
        ];

        // Разбиваем строку на части, сразу сохраняем их.
        [$data['service'], $data['incident']['type']] = Parser::parceStr($data['service']);

        // Возвращаем успешный результат.
        $return['success'] = true;
        $return['data'] = $data;

        return $return;
    }
}