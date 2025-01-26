<?php

namespace App\Http\Controllers\DataManagers;

use App\Http\Controllers\Controller;

class WSPG extends Controller
{
    public function logging(): string
    {
        return "WSPG";
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
        $sign = hash('sha256', $incident["object"] . $incident["date"] . config("app.key"), false);

        return $sign === $data["token"];
    }
}