<?php

namespace App\Http\Controllers\DataManagers;

use App\Helpers\Parsers\Parser;
use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentType;

class WSPG extends Controller
{
    /**
     * logging - логируем инцидент
     * Получаем сырые данные, парсим и сохраняем
     * 
     * @param array $data
     * @return array{message: string, success: bool}
     */
    public function logging(array $data): array
    {
        $serviceMessageParser = ServiceManager::getServiceParcer($data['incident']['type']);
        $parcedMessage = $serviceMessageParser->parse($data['incident']['message']);

        if (!$parcedMessage['success']) {
            \Illuminate\Support\Facades\Log::channel("debug")->info("WSPG PARSE ERROR", $serviceMessageParser['data']);
        }
        $data['incident']['message'] = $parcedMessage['message'];
        [$code, $message] = Parser::parceStr($data['incident']['message']);

        $existType = IncidentType::where('code', $code)->first();
        $result = match (true) {
            $existType === null => Incident::saveData($data), // Сохраняем, если тип инцидента не найден
            default => Incident::updateData($data, $existType['id']), // Обновляем, если тип инцидента найден
        };

        return $result;
    }

    /**
     * checkToken - проверяет токен
     * 
     * @param array $data
     * @return bool
     */
    public function checkToken(array $data): array
    {
        $incident = $data["incident"];

        $message = is_array($incident['message'])
            ? json_encode($incident['message'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : $incident['message'];

        $sign = hash('sha256', $incident["object"] . $incident["date"] . config("app.key") . $message, false);
        \Illuminate\Support\Facades\Log::channel("tokens")->info("WSPG CHECK TOKEN SIGH", [$sign]);

        $res = $sign == $data['token'];
        \Illuminate\Support\Facades\Log::channel("tokens")->info("WSPG CHECK TOKEN RESULT", [$res]);
        return [
            'success' => $res,
            'message' => $res ? "" : "Неверный токен",
        ];
    }

}