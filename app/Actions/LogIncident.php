<?php

namespace App\Actions;

use App\Helpers\Parsers\Parser;
use App\Models\Incident;
use App\Models\IncidentType;

class LogIncident {
    /**
     * logging - Метод фильтрации и логирования
     *
     * @param array $data
     * @return array
     */
    public static function writeOrSaveLog(array $data): array {
        [$data['code'], $data['message']] = Parser::parseStr($data['message']);
        $existType = IncidentType::where('code', $data['code'])->first();

        return match (true) {
            $existType === null => Incident::saveData($data),            // Сохраняем, если тип инцидента не найден
            default => Incident::processIncidentData($data, $existType), // Обновляем, если тип инцидента найден
        };
    }
}

