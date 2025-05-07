<?php

namespace App\Models;

use App\Enums\SendTemplateType;
use App\Helpers\Parsers\Parser;
use App\Helpers\SenderManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Incident extends Model
{
    use HasFactory;

    private const ERROR_CLASS = __CLASS__;
    public $timestamps = false;
    protected $table = 'incident';
    protected $fillable = [
        'incident_object',
        'incident_text',
        'incident_object_alias',
        'incident_type_id',
        'source',
        'service',
        'date',
        'count',
    ];

    /**
     * saveData - сейвим логи, о которых мы ещё не знаем или просто на них не реагируем
     *
     * @param array $data
     * @return array
     */
    public static function saveData(array $data): array
    {
        $message = "Новая не отслеживаемая ошибка от {$data['service']}";
        Log::channel("unknown_errors")->warning(
            "Новая не отслеживаемая ошибка от WSPG: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        SenderManager::telegramSendMessage(
            self::ERROR_CLASS,
            $message,
            (string) $data['incident']['message'],
            ['Object' => $data['incident']['object']]
        );

        return [
            "success" => true,
            "message" => "Данные успешно сохранены",
        ];
    }

    /**
     * updateData - Проверяем есть ли для данного пользователя такая ошибка, если нет, то создаем новую
     *
     * @param array $data
     * @param mixed $incidentTypeId
     * @return array{message: string, success: bool}
     */
    public static function processIncidentData(array $data, object $incidentType): array
    {
        $incidentData = $data['incident'];
        $existIncident = self::firstOrNew(
            ['incident_object' => $incidentData['object']],
            [
                'incident_text' => $incidentData['message'],
                'incident_type_id' => $incidentType->id,
                'incident_object_alias' => json_encode($data['incident']['object_data']),
                'service' => $data['service'],
                'source' => $data['incident']['type'],
                'date' => $incidentData['date'],
                'count' => 1
            ]
        );

        if (!$existIncident->exists) {
            self::handleNewIncident($incidentType, $existIncident);
            return [
                'success' => true,
                'message' => 'Данные успешно сохранены и отправлены'
            ];
        }

        return self::handleExistingIncident($existIncident, $incidentType, $incidentData);
    }

    /**
     * handleNewIncident
     *
     * @param mixed $existIncident
     * @param mixed $incidentType
     * @param mixed $data
     * @return void
     */
    protected static function handleNewIncident(object $incidentType, object $data): void
    {
        if (!empty($incidentType->alias)) {
            $data->save();

            match (SendTemplateType::from($incidentType->alias)) {
                SendTemplateType::PUSH_MAIL => SenderManager::preparePushOrMail($data, $incidentType->send_template_id),
                default => null,
            };

            SenderManager::telegramSendMessage(
                self::ERROR_CLASS,
                "Новая ошибка от {$data->service} ({$data->source})",
                (string) $data->incident_text,
                [
                    'INCIDENT_TYPE' => $incidentType->type_name,
                    'CODE' => $incidentType->code,
                    'INCIDENT_OBJECT' => $data->incident_object,
                ]
            );
        }
    }

    /**
     * handleExistingIncident
     *
     * @param mixed $existIncident
     * @param mixed $incidentData
     * @return array{message: string, success: bool}
     */
    private static function handleExistingIncident(object $existIncident, object $incidentType, array $data): array
    {
        $parseDates = Parser::parseDates($existIncident->date, $data['date']);
        $lifecycle = $existIncident->incidentType->lifecycle;
        $existIncident->count++;

        if ($parseDates['prevDate']->diffInDays($parseDates['currentDate'], true) >= $lifecycle) {
            $existIncident->date = $parseDates['currentDate'];
            $existIncident->save();

            if (!empty($incidentType->alias)) {
                Log::channel('debug')->info(self::ERROR_CLASS . ' handleExistingIncident existIncident to array', [$existIncident->toArray()]);
                match (SendTemplateType::from($incidentType->alias)) {
                    SendTemplateType::PUSH_MAIL => SenderManager::preparePushOrMail($existIncident, $incidentType->send_template_id),
                    default => null,
                };
            }

            SenderManager::telegramSendMessage(
                self::ERROR_CLASS,
                "ОШИБКА ОБНОВИЛАСЬ ДЛЯ ({$existIncident->incident_object})",
                (string) $existIncident->incident_text,
                [
                    'SERVICE AND SOURCE' => $existIncident->service . "|" . $existIncident->source,
                    'count' => $existIncident->count
                ]
            );

            return [
                'success' => true,
                'message' => 'Данные успешно обновлены'
            ];
        }

        $existIncident->save();

        SenderManager::telegramSendMessage(
            self::ERROR_CLASS,
            "ДОБАВЛЯЛАСЬ РАНЕЕ",
            (string) $existIncident->incident_text,
            [
                'OBJECT' => $existIncident->incident_object,
                'COUNT' => $existIncident->count,
                'LIFECICLE' => $lifecycle,
                'NEXT_SEND_DATE' => Carbon::parse($existIncident->date)
                    ->addDays((int)$existIncident->incidentType->lifecycle)
                    ->format('d-m-Y')
            ]
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
     * @return JsonResponse|StreamedResponse
     */
    public static function exportLogs(array $data): JsonResponse|StreamedResponse
    {
        $query = self::query();

        if (Arr::has($data, 'date')) {
            $query->where('date', $data['date']);

            if (!$query->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Данные по дате не найдены'
                ], 404);
            }
        }

        if (Arr::has($data, 'service')) {
            $query->where('service', $data['service']);

            if (!$query->exists()) {
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

    /*
     * getIncidentDataByParams - получаем данные по параметрам
     *
     * @param array $data
     * @return array
     *
     * */
    public static function getIncidentDataByParams(array $data): array
    {
        $return = [
            "success" => false,
            "message" => "Данные не найдены",
            "data" => []
        ];

        $existService = Services::validateService($data['service']);
        if (!$existService['success']) {
            $return['message'] = $existService['message'];
            return $return;
        }

        $query = self::query()
            ->join('incident_type', 'incident.incident_type_id', '=', 'incident_type.id')
            ->select([
                'incident.id',
                'incident.incident_object',
                'incident.incident_text',
                'incident.source',
                'incident.date',
                'incident.count',
                'incident.service',
                'incident_type.type_name',
                'incident_type.code',
                'incident_type.lifecycle'
            ]);

        if (!empty($data['source'])) {
            $query->where("source", $data['source']);
        }

        if (!empty($data['service'])) {
            $query->where("service", $data['service']);
        }

        if (!empty($data['date'])) {
            $query->where("date", $data['date']);
        }

        if (!empty($data['code'])) {
            $query->where("code", $data['code']);
        }

        $returnData = $query->get()->toArray();

        if (!empty($returnData)) {
            $return['success'] = true;
            $return['message'] = "";

            $return['data'] = array_map(function ($item) {
                return [
                    "id" => $item['id'],
                    "code" => $item['code'],
                    "service" => $item['service'],
                    "source" => $item['source'],
                    "incident" => [
                        "object" => $item['incident_object'],
                        "text" => $item['incident_text'],
                    ],
                    "type" => $item['type_name'],
                    "count" => $item['count'],
                    "lifecycle" => $item['lifecycle'],
                    "date" => $item['date']
                ];
            }, $returnData);
        }

        return $return;
    }

    public function incidentType()
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }
}
