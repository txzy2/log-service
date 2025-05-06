<?php

namespace App\Actions\Logging;

use App\Helpers\Parsers\Parser;
use App\Helpers\ServiceManager;
use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Support\Facades\Log;

class Logging
{
    private const ERROR_CLASS = __CLASS__;

    /**
     * logging - Метод фильтрации и логирования
     *
     * @param array $data
     * @return array
     */
    public static function writeOrSaveLog(array $data): array
    {
        $return = [
            "success" => true,
            "message" => ""
        ];

        $prepredData = ServiceManager::prepareRequestData($data);
        Log::channel("debug")->info(self::ERROR_CLASS . ':addLog PARSED REQUEST', [$prepredData]);

        if (isset($prepredData['error'])) {
            $return['message'] = $prepredData['message'];
            return $return;
        }

        $serviceMessageParser = ServiceManager::getServiceParser($prepredData['incident']['type']);
        $parsedMessage = $serviceMessageParser->parse($data['incident']['message']);

        if (!$parsedMessage['success']) {
            Log::channel("debug")->info(self::ERROR_CLASS . "::logging PARSE ERROR", $parsedMessage);
            $return['message'] = "Ошибка парсинга сервиса";
            return $return;
        }

        $prepredData['incident']['message'] = $parsedMessage['message'];
        [$code] = Parser::parseStr($parsedMessage['message']);

        $existType = IncidentType::where('code', $code)->first();
        $return['message'] = match (true) {
            $existType === null => Incident::saveData($prepredData)['message'], // Сохраняем, если тип инцидента не найден
            default => Incident::updateOrCreateData($prepredData, $existType)['message'], // Обновляем, если тип инцидента найден
        };

        return $return;
    }
}
