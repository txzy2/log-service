<?php

namespace App\Http\Controllers\DataManagers;

use App\Helpers\ServiceManager;
use App\Http\Controllers\Controller;

class WSPG extends Controller
{
    public function logging(array $data): string
    {
        $serviceMessageParser = ServiceManager::getServiceParcer($data['incident']['type']);
        $parcedMessage = $serviceMessageParser->parse($data['incident']['message']);

        if (!$parcedMessage['success']) {
            \Illuminate\Support\Facades\Log::channel("debug")->info("WSPG PARSE ERROR", $serviceMessageParser['data']);
            return $this->sendError('Не удалось распарсить сообщение', 400);
        }

        // TODO: 
        // 1. Проверить есть ли такой код в базе данных и его тип
        // 2. Если нет, то записать в базу данных
        // 3. Если есть, то проверить дату и если она истекла по жизненному циклу, то отправить и count++


        return $parcedMessage['message'];
    }

    /**
     * checkToken - проверяет токен
     * 
     * @param array $data
     * @return bool
     */
    public function checkToken(array $data): bool
    {
        $incident = $data["incident"];

        $message = is_array($incident['message'])
            ? json_encode($incident['message'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : $incident['message'];

        $sign = hash('sha256', $incident["object"] . $incident["date"] . config("app.key") . $message, false);
        \Illuminate\Support\Facades\Log::channel("tokens")->info("WSPG CHECK TOKEN SIGH", [$sign]);

        return $sign === $data["token"];
    }
}