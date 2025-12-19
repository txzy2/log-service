<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Services extends BaseModel
{
    use HasFactory;

    protected $table = 'incident_services';

    protected $fillable = [
        'name',
        'active',
    ];

    public static function findService(string $service): ?Services
    {
        return Services::where('name', $service)->first();
    }

    /**
     * validateActiveService - выполняет проверку валидности сервиса на статус активности и существование
     *
     * @param string $service
     *
     * @return array
     */
    public static function validateActiveService(string $service): array
    {
        $existService = Services::where('name', $service)->where('active', 'Y')->first();
        if (! $existService) {
            Log::channel("debug")->error(static::getModelClass() . " SERVICE IS INACTIVE" . " ($service)");

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
