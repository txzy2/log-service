<?php

namespace App\Http\Controllers\DataManagers;

use App\Http\Controllers\Controller;

class WSPG extends Controller
{
    /**
     * Публичный метод для проверки токена
     *
     * @param array $data Массив с данными запроса
     * @return array{success: bool, message: string} Результат проверки токена
     */
    public function validateToken(array $data): array
    {
        return $this->checkToken($data);
    }

    /**
     * checkToken - проверяет валидность токена для сервиса WSPG
     * Формирует подпись на основе данных инцидента и сверяет с переданным токеном
     *
     * @param array $data Массив с данными запроса
     * @return array{success: bool, message: string} Результат проверки токена
     */
    private function checkToken(array $data): array
    {
        $incident = $data["incident"];
        $message = is_array($incident['message'])
            ? json_encode($incident['message'], JSON_UNESCAPED_UNICODE)
            : $incident['message'];
        $sign = hash('sha256', implode('', [$incident['object'], $incident['date'], config('app.key'), $message]));

        return [
            'success' => $sign === $data['token'],
            'message' => $sign === $data['token'] ? '' : 'Неверный токен'
        ];
    }
}
