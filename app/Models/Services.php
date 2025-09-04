<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Services extends Model {
    use HasFactory;
    private const ERROR_CLASS = __CLASS__;

    protected $table = 'incident_services';

    protected $fillable = [
        'name',
        'active',
    ];

    public static function findService(string $service): ?Services {
        return Services::where('name', $service)->first();
    }

    public static function validateActiveService(string $service): array {
        $existService = Services::where('name', $service)->where('active', 'Y')->first();

        if (!$existService) {
            Log::channel("debug")->error(static::ERROR_CLASS . " SERVICE IS INACTIVE" . " ($service)");

            return [
                'success' => false,
                'message' => "Введен неверный сервис или не активен",
            ];
        }

        return [
            'success' => true,
            'message' => '',
        ];
    }
}
