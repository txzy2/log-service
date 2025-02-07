<?php

namespace App\Http\Controllers\DataManagers;

use App\Helpers\Parsers\Parser;
use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentType;
use Carbon\Carbon;

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
     * report - отправляет отчет
     * 
     * @param array $data
     * @return array{success: bool, message: string}
     */
    public function report(array $data): array
    {
        $return = [
            'success' => false,
            'message' => 'Не удалось получить данные',
        ];

        if (array_key_exists('date', $data)) {
            $checkWithDate = Incident::where('service', $data['service'])->where('date', $data['date'])->get()->toArray();
            return [
                'success' => count($checkWithDate) > 0,
                'message' => $checkWithDate,
            ];
        }

        // Если дата не указана, только тогда делаем запрос без даты
        $checkWithoutDate = Incident::where('service', $data['service'])->get()->toArray();
        return [
            'success' => count($checkWithoutDate) > 0,
            'message' => $checkWithoutDate,
        ];
    }

    /**
     * checkToken - проверяет валидность токена для сервиса WSPG
     * Формирует подпись на основе данных инцидента и сверяет с переданным токеном
     *
     * @param array $data Массив с данными запроса
     * @return array{success: bool, message: string} Результат проверки токена
     */
    public function checkToken(array $data): array
    {
        $incident = $data["incident"];
        $message = is_array($incident['message'])
            ? json_encode($incident['message'], JSON_UNESCAPED_UNICODE)
            : $incident['message'];

        $sign = hash('sha256', implode('', [$incident['object'], $incident['date'], config('app.key'), $message]));
        \Illuminate\Support\Facades\Log::channel("tokens")->info("WSPG CHECK TOKEN SIGH", [$sign]);

        return [
            'success' => $sign === $data['token'],
            'message' => $sign === $data['token'] ? '' : 'Неверный токен'
        ];
    }
}
