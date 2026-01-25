<?php

namespace App\Actions;

use App\Models\Incident;
use App\Models\IncidentType;
use App\Values\IncidentData;

class LogIncident {
    /**
     * logging - Метод фильтрации и логирования
     *
     * @param IncidentData $data
     * @return array
     */
    public static function writeOrSaveLog(IncidentData $data): array {
        [$code, $message] = $data->parseCodeAndMessage();
        $existType = IncidentType::where('code', $code)->first();
        $data->setNewMessage($message);

        return match (true) {
            $existType === null => Incident::saveData($data), // Сохраняем, если тип инцидента не найден
            default => Incident::processIncidentData($data, $existType), // Обновляем, если тип инцидента найден
        };
    }
}
