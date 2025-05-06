<?php

namespace App\Helpers;

use App\Helpers\Parsers\Parser;
use App\Models\Services;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ServiceManager
{
    private const ERROR_CLASS = __CLASS__;

    /**
     * initServiceObject - инициализация сервиса
     *
     * @param string $service
     * @return object
     */
    public static function initServiceObject(string $service): object
    {
        $serviceName = "\\App\\Http\\Controllers\\DataManagers\\{$service}";
        return new $serviceName();
    }

    /**
     * getServiceParser - получает объект парсера для указанного сервиса
     *
     * @param string $service
     * @return object|bool
     */
    public static function getServiceParser(string $service): object|bool
    {
        $serviceName = "\\App\\Helpers\\Parsers\\{$service}";
        if (class_exists($serviceName)) {
            return new $serviceName();
        }

        return false;
    }
    /**
     * prepareRequestData - метод подготовки и валидации сервиса
     *
     * @param array $data
     * @return array|JsonResponse
     */
    public static function prepareRequestData(array $data): array
    {
        $parsedData = static::returnParts($data);
        if (!$parsedData['success']) {
            Log::channel("debug")->info(self::ERROR_CLASS . "::prepareRequestData ({$data['service']})", $data);
            return ['error' => "Ошибка парсинга сервиса"];
        }

        $existService = Services::validateService($parsedData['data']['service']);
        if (!$existService['success']) {
            return ['error' => $existService['message']];
        }

        return $parsedData['data'];
    }

    /**
     * returnParts - проверяет и возвращает наименования сервиса и тип инцидента
     *
     * @param array $data
     * @return array
     */
    public static function returnParts(array $data): array
    {
        if (!isset($data['service']) || !isset($data['incident'])) {
            return [
                'success' => false,
                'data' => $data,
                'message' => 'Отсутствуют необходимые данные'
            ];
        }

        [$data['service'], $data['incident']['type']] = Parser::parseStr($data['service']);
        return [
            'success' => true,
            'data' => $data
        ];
    }
}
