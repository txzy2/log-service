<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Services extends BaseModel
{
    use HasFactory;

    protected $table = 'incident_services';

    protected $fillable = [
        'name',
        'active',
    ];

    public static function findService(string $service): ?Services {
        return Services::where('name', $service)->first();
    }

    /**
     * findActiveServiceByName - выполняет проверку валидности сервиса на статус активности и существование
     *
     * @param string $service
     *
     * @return bool
     */
    public static function findActiveServiceByName(string $service): bool {
        return Services::where('name', $service)->where('active', 'Y')->exists();
    }
}
