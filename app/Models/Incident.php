<?php

namespace App\Models;

use App\Helpers\Parsers\Parser;
use App\Helpers\ServiceManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    private const ERROR_MESSAGE = "<b>MODULE ERROR: <i>IncidentModel::class</i></b>";
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
    public static function saveData(array $data): array
    {
        \Illuminate\Support\Facades\Log::channel("unknown_errors")
            ->info("НЕИЗВЕСТНАЯ ОШИБКА ОТ {$data['service']}", [$data['incident']['message']]);

        // TODO: Сделать отправку в тг бота

        return [
            "success" => true,
            "message" => "Данные успешно сохранены",
        ];
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
            $existIncident->service = $data['service'];
            $existIncident->source = $incidentData['type'];
            $existIncident->date = $incidentData['date'];
            $existIncident->count = 1;
            $existIncident->save();

            // TODO: Сделать отправку через helper SenderManager

            $result['success'] = true;
            $result['message'] = 'Данные успешно сохранены и отправлены';

            return $result;
        }

        $parceDates = Parser::parceDates($existIncident->date, $incidentData['date']);
        $diffInDays = $parceDates['prevDate']->diffInDays($parceDates['currentDate'], true);
        $lifecyrcle = $existIncident->incidentType->lifecycle;

        if ($diffInDays < $lifecyrcle) {
            $result['message'] = "Ошибка уже отправлялась ID ошибки: {$existIncident->id}";
            $existIncident->count++;
            $existIncident->save();

            ServiceManager::telegramSendMessage(
                self::ERROR_MESSAGE . "\n\n"
                    . "<b>Ошибка</b> <i>{$existIncident->incident_text}</i> уже отправлялась\n"
                    . "Object: <code>{$existIncident->incident_object}</code>\n"
                    . "Count: {$existIncident->count}"
            );
        } else {
            $existIncident->count++;
            $existIncident->date = $parceDates['currentDate'];
            $existIncident->save();

            // TODO: Сделать отправку через helper SenderManager

            $result['success'] = true;
            $result['message'] = 'Данные успешно обновлены';
        }

        return $result;
    }
}
