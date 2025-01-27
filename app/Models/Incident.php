<?php

namespace App\Models;

use App\Helpers\Parsers\Parser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    protected $table = 'incident';
    protected $fillable = [
        'incident_object',
        'incident_text',
        'incident_type_id',
        'source',
        'date',
        'count',
    ];

    public $timestamps = false;

    public function incidentType()
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }

    /**
     * saveData - сейвыим логи, о которых мы ещё не знаем или просто на них не реагируем
     * 
     * @param array $data
     * @return void
     */
    public function saveData(array $data)
    {

    }

    /**
     * updateData - Проверяем есть ли для данного ползователя такая ошибка, если нет, то создаем новую
     * 
     * @param array $data
     * @param mixed $incidentTypeIc
     * @return array{message: string, success: bool}
     */
    public static function updateData(array $data, $incidentTypeIc): array
    {
        // TODO: Проверим есть ли для данного ползователя такая ошибка 

        $result = [
            'success' => false,
            'message' => 'Не удалось создать/обновить данные',
        ];

        $incidentData = $data['incident'];
        $existIncident = self::where('incident_object', $incidentData['object'])->first();
        if (!$existIncident) {
            $existIncident = new self();
            $existIncident->incident_object = $incidentData['object'];
            $existIncident->incident_text = $incidentData['message'];
            $existIncident->incident_type_id = $incidentTypeIc;
            $existIncident->source = $incidentData['type'];
            $existIncident->date = $incidentData['date'];
            $existIncident->count = 1;
            $existIncident->save();

            // TODO: Сделать отправку через helper SenderManager

            $result['success'] = true;
            $result['message'] = 'Данные успешно сохранены и отправлены';
        }

        $parceDates = Parser::parceDates($incidentData['date'], $existIncident->date);
        $diffInYears = $parceDates['currentDate']->diffInYears($parceDates['prevDate'], true);
        $lifecyrcle = $existIncident->incidentType->lifecycle;

        $diffInYears = (int) $diffInYears;
        if ($diffInYears < $lifecyrcle) {
            $result['message'] = "Ошибка уже отправлялась ID ошибки: {$existIncident->id}";
        } else {
            $existIncident->count++;
            $existIncident->save();

            // TODO: Сделать отправку через helper SenderManager

            $result['success'] = true;
            $result['message'] = 'Данные успешно обновлены';
        }

        return $result;

    }
}