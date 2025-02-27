<?php

namespace App\Models;

use App\Helpers\Parsers\Parser;
use App\Helpers\SenderManager;
use App\Helpers\ServiceManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public static function updateData(array $data, int $incidentTypeId): array
    {
        $incidentData = $data['incident'];
        $existIncident = self::firstOrNew(
            ['incident_object' => $incidentData['object']],
            [
                'incident_text' => $incidentData['message'],
                'incident_type_id' => $incidentTypeId,
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
        $now = Carbon::now();

        if ($parceDates['currentDate']->lt($now)) {
            return [
                'success' => false,
                'message' => "Текущая дата ($now) не соответствует переданной {$parceDates['currentDate']}"
            ];
        }

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

    /**
     * exportLogs - экспорт логов в csv
     *
     * @param array $data
     * @return JsonResponse
     */
    public static function exportLogs(array $data): JsonResponse|StreamedResponse
    {
        $query = self::query();

        if (Arr::has($data, 'date')) {
            $query->where('date', $data['date']);

            if(!$query->exists()){
                return response()->json([
                    'success' => false,
                    'message' => 'Данные по дате не найдены'
                ], 404);
            }
        }

        if (Arr::has($data, 'service')) {
            $query->where('service', $data['service']);

            if(!$query->exists()){
                return response()->json([
                    'success' => false,
                    'message' => 'Данные по сервису не найдены'
                ], 404);
            }
        }

        $logs = $query->get();

        $date = Arr::has($data, 'date')
            ? $data['date']
            : now()->format('Y-m-d');

        $service = Arr::has($data, 'service') && !empty($data['service'])
            ? $data['service'] . '_'
            : '';

        $fileName = "{$service}logs_{$date}.csv";

        $headers = [
            "Content-Type" => "text/csv; charset=windows-1251",
            "Content-Disposition" => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID',
                'Объект',
                'Текст',
                'Источник',
                'Сервис',
                'Повторения',
                'Тип ошибки',
                'Цикл жизни',
                'Дата'
            ], ';');

            foreach ($logs as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->incident_object,
                    $item->incident_text,
                    $item->source,
                    $item->service,
                    $item->count,
                    $item->incidentType->type_name,
                    $item->incidentType->lifecycle,
                    $item->date,
                ], ';');
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }
}
