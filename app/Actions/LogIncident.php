<?php
namespace App\Actions;

use App\Helpers\ServiceManager;
use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Support\Facades\Log;

class LogIncident
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
            "message" => "",
        ];

        $prepredData = ServiceManager::prepareRequestData($data);
        Log::channel("debug")->info(self::ERROR_CLASS . ':addLog PARSED REQUEST', [$prepredData]);

        if (isset($prepredData['error'])) {
            $return['success'] = false;
            $return['message'] = $prepredData['error'];
            return $return;
        }

        $serviceMessageParser = ServiceManager::getServiceParser($prepredData['incident']['type']);
        $parsedMessage = $serviceMessageParser->parse($data['incident']['message']);

        if (!$parsedMessage['success']) {
            Log::channel("debug")->error(self::ERROR_CLASS . "::logging PARSE ERROR", $parsedMessage);
            $return['success'] = false;
            $return['message'] = "Ошибка парсинга сервиса";
            return $return;
        }

        $prepredData['incident']['message'] = $parsedMessage['message'];
        $existType = IncidentType::where('code', $parsedMessage['code'])->first();

        return match (true) {
            $existType === null => Incident::saveData($prepredData),            // Сохраняем, если тип инцидента не найден
            default => Incident::processIncidentData($prepredData, $existType), // Обновляем, если тип инцидента найден
        };
    }
}