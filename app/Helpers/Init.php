<?php

namespace App\Helpers;

class Init
{
    public static function initServiceObject(string $service): object
    {
        $serviceName = "\\App\\Http\\Controllers\\DataManagers\\{$service}";
        $paymentServiceObject = new $serviceName();

        return $paymentServiceObject;
    }

    public static function returnParts(array $data): array
    {
        $parts = explode('|', $data['service']);

        $data['service'] = $parts[0];
        $data['incident']['type'] = $parts[1];

        return $data;
    }

}