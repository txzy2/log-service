<?php

namespace App\Models;

use App\Helpers\Parsers\Parser;
use App\Helpers\SenderManager;
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
        $incidentData = $data['incident'];
        $existIncident = self::firstOrNew(
            ['incident_object' => $incidentData['object']],
            [
                'incident_text' => $incidentData['message'],
                'incident_type_id' => $incidentTypeIc,
                'service' => $data['service'],
                'source' => $incidentData['type'],
                'date' => $incidentData['date'],
                'count' => 1
            ]
        );

        if (!$existIncident->exists) {
            $existIncident->save();
            SenderManager::preparePushOrMail($existIncident);

            return [
                'success' => true,
                'message' => 'Данные успешно сохранены и отправлены'
            ];
        }

        $parceDates = Parser::parceDates($existIncident->date, $incidentData['date']);
        $diffInDays = $parceDates['prevDate']->diffInDays($parceDates['currentDate'], true);

        $existIncident->count++;
        if ($diffInDays >= $existIncident->incidentType->lifecycle) {
            $existIncident->date = $parceDates['currentDate'];
            $existIncident->save();

            SenderManager::preparePushOrMail($existIncident);

            ServiceManager::telegramSendMessage(
                self::ERROR_MESSAGE . "\n\n"
                    . "<b>Данные ошибки</b> <i>{$existIncident->incident_text}</i> обновлены\n"
                    . "Object: <code>{$existIncident->incident_object}</code>\n"
                    . "Count: {$existIncident->count}"
            );

            return [
                'success' => true,
                'message' => 'Данные успешно обновлены'
            ];
        }
        $existIncident->save();

        ServiceManager::telegramSendMessage(
            self::ERROR_MESSAGE . "\n\n"
                . "<b>Ошибка</b> <i>{$existIncident->incident_text}</i> уже отправлялась\n"
                . "Object: <code>{$existIncident->incident_object}</code>\n"
                . "Count: {$existIncident->count}"
        );

        return [
            'success' => false,
            'message' => "Ошибка уже отправлялась ID ошибки: {$existIncident->id}"
        ];
    }
}
