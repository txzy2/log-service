<?php
namespace App\Models;

use App\Values\AddTypeData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncidentType extends BaseModel
{
    use HasFactory;

    protected $table = 'incident_type';

    protected $fillable = [
        'type_name',
        'send_template_id',
        'code',
        'lifecycle',
        'alias',
    ];

    public $timestamps = false;

    /**
     * Валидация и добавление нового типа инцидента.
     *
     * @param AddTypeData $data Данные для нового типа инцидента.
     * @return array Массив с результатом операции, включая статус и сообщение.
     */
    public static function validateAndAddType(AddTypeData $data): array
    {
        $return = [
            'success' => false,
            'data' => [],
            'message' => 'Такой тип ошибки уже существует',
        ];

        $existType = static::where('code', $data->code)->orWhere('type_name', $data->typeName)->first();
        if ($existType) {
            return $return;
        }

        $newType = static::create([
            'type_name' => $data->typeName,
            'code' => $data->code,
            'send_template_id' => $data->sendTemplateId ?? null,
            'lifecycle' => $data->lifecycle,
            'alias' => 'manager',
        ]);

        \Illuminate\Support\Facades\Log::channel('debug')->info(static::getModelClass() . '::validateAndAddType ADD RESULT', [$existType]);

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
