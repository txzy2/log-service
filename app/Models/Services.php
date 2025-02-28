<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Services extends Model
{
    private const ERROR_CLASS = __CLASS__;

    protected $table = 'incident_services';

    protected $fillable = [
        'name',
        'active',
    ];

    public static function findService(string $service): ?Services
    {
        return Services::where('name', $service)->first();
    }

    public static function validateService(string $service): array
    {
        $existService = Services::where('name', $service)->first();

        if (!$existService) {
            return [
                'success' => false,
                'message' => "Введен неверный сервис",
            ];
        }

        if ($existService->active === 'N') {
            Log::channel("debug")->error(self::ERROR_CLASS . " SERVICE IS INACTIVE" . " ($service)");

            return [
                'success' => false,
                'message' => "Сервис не активен",
            ];
        }

        return [
            'success' => true,
            'message' => '',
        ];
    }
}
