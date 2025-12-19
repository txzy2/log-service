<?php

namespace App\Models;

use App\Enums\SendTemplateType;
use App\Helpers\Parsers\Parser;
use App\Helpers\SenderManager;
use App\Values\IncidentData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Incident extends BaseModel
{
    use HasFactory;

    public $timestamps  = false;
    protected $table    = 'incident';
    protected $fillable = [
        'domain',
        'service',
        'message',
        'class',
        'function',
        'action',
        'file',
        'additionalFields',
        'incident_type_id',
        'date',
        'count',
        'hash_sum',
        'level',
    ];

    /**
     * saveData - сейвим логи, о которых мы ещё не знаем или просто на них не реагируем
     *
     * @param IncidentData $data
     * @return array
     */
    public static function saveData(IncidentData $data): array
    {
        Log::channel("unknown_errors")->warning(
            "Новая не отслеживаемая ошибка от {$data->service}: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        Log::channel("debug")->info("saveData type data", [\gettype($data)]);

        $additionalFields = (object) [];
        if (! empty($data->additionalFields)) {
            $additionalFields = json_encode($data->additionalFields);
        }

        static::create( [
            'level' => $data->level,
            'message' => $data->message,
            'domain' => $data->domain,
            'service' => $data->service,
            'class' => $data->class,
            'incident_type_id' => null,
            'function' => $data->function,
            'action' => $data->action,
            'file' => $data->file,
            'additionalFields' => $additionalFields,
            'date' => $data->date,
            'count' => 1,
        ]);

        return [
            "success" => true,
            "message" => "Данные успешно сохранены",
        ];
    }

    /**
     * updateData - Проверяем есть ли для данного пользователя такая ошибка, если нет, то создаем новую
     *
     * @param IncidentData $data
     * @param mixed $incidentTypeId
     * @return array{message: string, success: bool}
     */
    public static function processIncidentData(IncidentData $data, object $incidentType): array
    {
        $additionalFields = (object) [];
        if (!empty($data->additionalFields)) {
            $additionalFields = json_encode($data->additionalFields);
        }
        if (!$data->isValidHash()) {
            Log::channel('debug')->info(static::getModelClass() . "invalid hash", []);
            return [
                "success" => false,
                "message" => "Контрольная сумма не совпадает",
            ];
        }

        $createNewIncident = static::firstOrNew(['hash_sum' => $data->hashSum], [
            'level' => $data->level,
            'message' => $data->message,
            'domain' => $data->domain,
            'service' => $data->service,
            'class' => $data->class,
            'incident_type_id' => $incidentType->id,
            'function' => $data->function,
            'action' => $data->action,
            'file' => $data->file,
            'additionalFields' => $additionalFields,
            'date' => $data->date,
            'count' => 1,
        ]);

        if (! $createNewIncident->exists) {
            static::handleNewIncident($incidentType, $createNewIncident);
            return [
                'success' => true,
                'message' => 'Данные успешно сохранены и отправлены',
            ];
        }

        return static::handleExistingIncident($createNewIncident, $incidentType, $data);
    }

    /**
     * handleNewIncident
     *
     * @param mixed $existIncident
     * @param IncidentType $incidentType
     * @param Incident $data
     * @return void
     */
    protected static function handleNewIncident(IncidentType $incidentType, Incident $data): void
    {
        // Log::channel('debug')->info(message: "handleNewIncident", ['data' => $data, 'incidentType' => $incidentType]);
        if (! empty($incidentType->alias)) {
            $data->save();

            match (SendTemplateType::from($incidentType->alias)) {
                SendTemplateType::PUSH_MAIL => SenderManager::preparePushOrMail($data, $incidentType->send_template_id),
                default                     => null,
            };

            SenderManager::telegramSendMessage(
                static::getModelClass(),
                "Новая ошибка от {$data->service} ({$data->source})",
                (string) $data->incident_text,
                [
                    'INCIDENT_TYPE' => $incidentType->type_name,
                    'CODE' => $incidentType->code,
                    'INCIDENT_OBJECT' => $data->hash_sum,
                ]
            );
        }
    }

    /**
     * handleExistingIncident
     *
     * @param object $existIncident
     * @param object $incidentType
     * @param IncidentData $data
     * @return array{message: string, success: bool}
     */
    private static function handleExistingIncident(object $existIncident, object $incidentType, IncidentData $data): array
    {
        $parseDates = Parser::parseDates($existIncident->date, $data->date);
        $lifecycle  = $existIncident->incidentType->lifecycle;
        $existIncident->count++;

        Log::channel("debug")->info("handleExistingIncident", [
            "Incident"=> $incidentType,
            "data" => $data,
            "existIncident" => $existIncident
        ]);

        if ($parseDates['prevDate']->diffInDays($parseDates['currentDate'], true) >= $lifecycle) {
            $existIncident->date = $parseDates['currentDate'];
            $existIncident->save();

            if (!empty($incidentType->alias)) {
                Log::channel('debug')->info(static::getModelClass() . '::handleExistingIncident existIncident to array', [$existIncident->toArray()]);
                match (SendTemplateType::from($incidentType->alias)) {
                    SendTemplateType::PUSH_MAIL => SenderManager::preparePushOrMail($existIncident, $incidentType->send_template_id),
                    default => null,
                };
            }

            SenderManager::telegramSendMessage(
                static::getModelClass(),
                "ОШИБКА ОБНОВИЛАСЬ ДЛЯ ({$existIncident->hash_sum})",
                (string) $existIncident->incident_text,
                [
                    'SERVICE AND SOURCE' => $existIncident->service . "|" . $existIncident->source,
                    'count' => $existIncident->count,
                ]
            );

            return [
                'success' => true,
                'message' => 'Данные успешно обновлены',
            ];
        }

        $existIncident->save();

        return [
            'success' => true,
            'message' => "Ошибка уже отправлялась ID ошибки: {$existIncident->id}",
        ];
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
            "data"    => [],
        ];

        //TODO: сделать offset и limit

        $existService = Services::validateActiveService($data['service']);
        if (! $existService['success']) {
            $return['message'] = $existService['message'];
            return $return;
        }

        $query = static::query()
            ->join('incident_type', 'incident.incident_type_id', '=', 'incident_type.id')
            ->select([
                'incident.id',
                'incident.message',
                'incident.class',
                'incident.action',
                'incident.domain',
                'incident.date',
                'incident.count',
                'incident.service',
                'incident_type.type_name',
                'incident_type.code',
                'incident_type.lifecycle',
            ]);

        // if (!empty($data['source'])) {
        //     $query->where("source", $data['source']);
        // }

        if (! empty($data['service'])) {
            $query->where("service", $data['service']);
        }

        if (! empty($data['date'])) {
            $query->where("date", "=", $data['date']);
        }

        if (! empty($data['code'])) {
            $query->where("code", $data['code']);
        }

        $returnData = $query->get()->toArray();
        Log::channel("debug")->info("return report data from DB", $returnData);

        if (! empty($returnData)) {
            $return['success'] = true;
            $return['message'] = "";

            $return['data'] = array_map(function ($item) {
                return [
                    "id"        => $item['id'],
                    "code"      => $item['code'],
                    "service"   => $item['service'],
                    "action"    => $item['action'],
                    "incident"  => [
                        "message" => $item['message'],
                        "domain"  => $item['domain'],
                    ],
                    "type"      => $item['type_name'],
                    "count"     => $item['count'],
                    "lifecycle" => $item['lifecycle'],
                    "date"      => $item['date'],
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
