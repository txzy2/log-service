<?php

namespace App\Helpers;

use App\Helpers\Parsers\Parser;
use App\Models\Services;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ServiceManager extends Helpers {
    /**
     * initServiceObject - инициализация сервиса
     *
     * @param string $service
     * @return object
     */
    public static function initServiceObject(string $service): object {
        $serviceName = "\\App\\Http\\Controllers\\DataManagers\\{$service}";
        return new $serviceName();
    }

    /**
     * getServiceParser - получает объект парсера для указанного сервиса
     *
     * @param string $service
     * @return object|bool
     */
    public static function getServiceParser(string $service): object|bool {
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
    public static function prepareRequestData(array $data): array {
        $existService = Services::validateActiveService($data['service']);
        if (!$existService['success']) {
            return ['error' => $existService['message']];
        }

        return $existService['success'];
    }

}
