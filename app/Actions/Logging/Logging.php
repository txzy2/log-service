<?php

namespace App\Actions\Logging;

use App\Helpers\Parsers\Parser;
use App\Helpers\ServiceManager;
use App\Models\Incident;
use App\Models\IncidentType;

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
        $serviceMessageParser = ServiceManager::getServiceParser($data['incident']['type']);
        if ($serviceMessageParser !== false) {
            $parsedMessage = $serviceMessageParser->parse($data['incident']['message']);
            if (!$parsedMessage['success']) {
                \Illuminate\Support\Facades\Log::channel("debug")->info(self::ERROR_CLASS . "::logging PARSE ERROR", $parsedMessage);
                return $parsedMessage;
            }

            $data['incident']['message'] = $parsedMessage['message'];
            [$code] = Parser::parseStr($data['incident']['message']);

            $existType = IncidentType::where('code', $code)->first();
            return match (true) {
                $existType === null => Incident::saveData($data), // Сохраняем, если тип инцидента не найден
                default => Incident::updateOrCreateData($data, $existType), // Обновляем, если тип инцидента найден
            };
        }

        return [
            "success" => false,
            "message" => "Парсер для {$data['incident']['type']} не найден"
        ];
    }
}
