<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    use HasFactory;

    private const ERROR_CLASS = __CLASS__;

    protected $table = 'incident_type';

    protected $fillable = [
        'type_name',
        'send_template_id',
        'code',
        'lifecycle',
        'alias'
    ];

    public $timestamps = false;

    /**
     * Валидация и добавление нового типа инцидента.
     *
     * @param array $data Данные для нового типа инцидента.
     * @return array Массив с результатом операции, включая статус и сообщение.
     */
    public static function validateAndAddType(array $data): array
    {
        $return = [
            'success' => false,
            'data' => [],
            'message' => 'Такой тип ошибки уже существует'
        ];

        $existType = self::where('code', $data['code'])
            ->orWhere('type_name', $data['type_name'])
            ->first();
        if ($existType) {
            return $return;
        }

        $newType = self::create([
            'type_name' => $data['type_name'],
            'code' => $data['code'],
            'send_template_id' => $data['send_template_id'] ?? null,
            'lifecycle' => $data['lifecycle'],
            'alias' => 'manager'
        ]);

        \Illuminate\Support\Facades\Log::channel('debug')->info(self::ERROR_CLASS . '::validateAndAddType ADD RESULT', [$existType]);

        $return['success'] = $newType ? true : false;
        $return['message'] = $newType ? '' : 'Ошибка сохранения типа';
        $return['data'] = $newType->toArray();

        return $return;
    }

    public function sendTemplate()
    {
        return $this->belongsTo(SendTemplate::class, 'send_template_id');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'incident_type_id');
    }
}
