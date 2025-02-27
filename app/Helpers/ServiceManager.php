<?php

namespace App\Helpers;

use App\Helpers\Parsers\Parser;

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
    public static function getServiceParser(string $service): object
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
        [$data['service'], $data['incident']['type']] = Parser::parceStr($data['service']);

        return [
            'success' => true,
            'data' => $data
        ];
    }

}
