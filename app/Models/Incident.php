<?php

namespace App\Models;

use App\Enums\LevelsEnum;
use App\Helpers\Parsers\Parser;
use App\Helpers\SenderManager;
use App\Models\IncidentType;
use App\Values\SendReportFilterData;
use App\Values\TelegramSendData;
use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Incident extends BaseModel
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'incident';
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

    protected $casts = [
        'additionalFields' => 'array',
    ];

    /**
     * Создаёт Incident из массива данных (из FormRequest)
     * (Information Expert - объект знает, как себя создать)
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self {
        $incident = new self();
        $incident->fill([
            'level' => $data['level'],
            'service' => $data['service'],
            'message' => $data['message'],
            'domain' => $data['domain'],
            'action' => $data['action'],
            'function' => $data['function'],
            'file' => $data['file'] ?? '',
            'class' => $data['class'] ?? '',
            'date' => $data['date'],
            'hash_sum' => $data['hash_sum'],
            'additionalFields' => $data['additionalFields'] ?? null,
        ]);
        
        return $incident;
    }

    /**
     * Генерирует хэш инцидента
     *
     * @return string
     */
    protected function generateHash(): string {
        return hash(
            'sha256',
            $this->service . $this->action . $this->function . $this->level
        );
    }

    /**
     * Проверяет валидность хэша
     *
     * @return bool
     */
    protected function isValidHash(): bool {
        return $this->hash_sum === $this->generateHash();
    }

    /**
     * Парсит код и сообщение из message
     *
     * @return array [code, message]
     */
    protected function parseCodeAndMessage(): array {
        if (LevelsEnum::from($this->level) !== LevelsEnum::INFO) {
            return Parser::parseStr($this->message);
        }
        
        return ['', $this->message];
    }

    /**
     * Обрабатывает инцидент согласно его типу
     * (Information Expert - объект знает, как себя обработать)
     *
     * @return array
     */
    public function process(): array {
        [$code, $message] = $this->parseCodeAndMessage();
        $existType = IncidentType::where('code', $code)->first();
        $this->message = $message;

        return match (true) {
            $existType === null => $this->saveAsUnknown(),
            default => $this->processWithType($existType),
        };
    }

    /**
     * saveAsUnknown - сохраняем логи, о которых мы ещё не знаем
     * (Information Expert - объект знает, как сохранить себя как неизвестный)
     *
     * @return array
     */
    protected function saveAsUnknown(): array {
        Log::channel('unknown_errors')->warning(
            "Новая не отслеживаемая ошибка от {$this->service}: " . json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        $this->incident_type_id = null;
        $this->count = 1;
        $this->save();

        return [
            'success' => true,
            'message' => 'Данные успешно сохранены',
        ];
    }

    /**
     * processWithType - обрабатываем инцидент с известным типом
     * (Information Expert - объект знает, как обработать себя с типом)
     *
     * @param IncidentType $incidentType
     * @return array{message: string, success: bool}
     */
    protected function processWithType(IncidentType $incidentType): array {
        if (!$this->isValidHash()) {
            Log::channel('debug')->error(static::getModelClass() . ' invalid hash', [
                'service' => $this->service,
                'hash' => $this->hash_sum
            ]);
            return [
                'success' => false,
                'message' => 'Контрольная сумма не совпадает',
            ];
        }

        try {
            $existingIncident = static::where('hash_sum', $this->hash_sum)->first();
            if (!$existingIncident) {
                $this->incident_type_id = $incidentType->id;
                $this->count = 1;
                $this->save();
                
                $this->handleNewIncident($incidentType);

                return [
                    'success' => true,
                    'message' => 'Данные успешно сохранены и отправлены',
                ];
            }

            return $this->handleExistingIncident($existingIncident, $incidentType);
        } catch (Exception $e) {
            Log::channel('debug')->error('EXCEPTION save', [$e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Ошибка при сохранении: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * handleNewIncident - обрабатывает новый инцидент (отправка уведомлений)
     *
     * @param IncidentType $incidentType
     * @return void
     */
    protected function handleNewIncident(IncidentType $incidentType): void {
        Log::channel("debug")->info("New incident created", [
            'service' => $this->service,
            'type' => $incidentType->type_name
        ]);
        
        if (!empty($incidentType->id)) {
            SenderManager::preparePushOrMail($this, $incidentType);
            
            SenderManager::telegramSendMessage(
                new TelegramSendData(
                    static::getModelClass(),
                    "Новая ошибка от {$this->service}",
                    (string) $this->incident_text,
                    [
                        'INCIDENT_TYPE' => $incidentType->type_name,
                        'CODE' => $incidentType->code,
                        'INCIDENT_OBJECT' => $this->hash_sum,
                    ]
                )
            );
        }
    }

    /**
     * handleExistingIncident - обрабатывает существующий инцидент
     *
     * @param Incident $existIncident
     * @param IncidentType $incidentType
     * @return array{message: string, success: bool}
     */
    protected function handleExistingIncident(Incident $existIncident, IncidentType $incidentType): array {
        $parseDates = Parser::parseDates($existIncident->date, $this->date);
        $lifecycle = $existIncident->incidentType->lifecycle;
        $existIncident->count++;

        if ($parseDates['prevDate']->diffInDays($parseDates['currentDate'], true) >= $lifecycle) {
            $existIncident->date = $parseDates['currentDate'];
            $existIncident->save();

            if (!empty($incidentType->alias)) {
                SenderManager::preparePushOrMail($existIncident, $incidentType->send_template_id);
            }

            SenderManager::telegramSendMessage(new TelegramSendData(
                static::getModelClass(),
                "ОШИБКА ОБНОВИЛАСЬ ДЛЯ ({$existIncident->hash_sum})",
                (string) $existIncident->incident_text,
                [
                    'SERVICE AND SOURCE' => $existIncident->service . '|' . $existIncident->source,
                    'count' => $existIncident->count,
                ]
            ));

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

    /**
     * applyFilerByParam - Применение по полям
     *
     * @param SendReportFilterData $params
     * @param mixed $query
     * @param string $paramKey
     * @param string $column
     * @return void
     */
    private static function applyFilerByParam(
        SendReportFilterData $params,
        $query,
        string $paramKey,
        string $column
    ): void {
        if (isset($params->$paramKey) && !empty($params->$paramKey)) {
            if ($column === 'date') {
                $query->whereDate($column, $params->$paramKey);
            } else {
                $query->where($column, $params->$paramKey);
            }
        }
    }

    /*
     * getIncidentDataByParams - получаем данные по параметрам
     *
     * @param array $data
     * @return array
     *
     * */
    public static function getIncidentDataByParams(SendReportFilterData $params): array
    {
        $return = [
            'success' => false,
            'message' => 'Данные не найдены',
            'data' => [],
        ];

        //TODO: сделать offset и limit

        $query = static::query()
            ->join('incident_type', 'incident.incident_type_id', '=', 'incident_type.id')
            ->select([
                'incident.uuid',
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

        static::applyFilerByParam($params, $query, 'service', 'service');
        static::applyFilerByParam($params, $query, 'date', 'date');
        static::applyFilerByParam($params, $query, 'code', 'code');

        $returnData = $query->get()->toArray();
        Log::channel('debug')->info('return report data from DB', $returnData);

        if (!empty($returnData)) {
            $return['success'] = true;
            $return['message'] = '';

            $return['data'] = array_map(function ($item) {
                return [
                    'id' => $item['uuid'],
                    'code' => $item['code'],
                    'service' => $item['service'],
                    'action' => $item['action'],
                    'incident' => [
                        'message' => $item['message'],
                        'domain' => $item['domain'],
                    ],
                    'type' => $item['type_name'],
                    'count' => $item['count'],
                    'lifecycle' => $item['lifecycle'],
                    'date' => $item['date'],
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
